<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ProductStatePrice;
use App\Models\Stockist;
use App\Models\Stockist_store;
use App\Models\StockistRedemption;
use App\Models\Stransaction;
use App\Services\StockistRebateService;
use App\Services\WelcomePackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\ShopPresenter;
use Inertia\Inertia;
 
class StockistPosController extends Controller
{
    public function __construct(
        private readonly WelcomePackageService $welcomePackageService,
        private readonly StockistRebateService $rebates,
    ) {}

    public function index(Request $request)
    {
        $pageTitle = 'POS';
        $stockist  = auth()->user()->stockist;

        $inventory = Stockist_store::with('product')
            ->where('user_id', auth()->id())
            ->where('quantity', '>', 0)
            ->get()
            ->filter(fn ($item) => $item->product !== null)
            ->map(function ($item) use ($stockist) {
                $statePrice = ProductStatePrice::where('state_id', $stockist->state_id)
                    ->where('product_id', $item->product_id)
                    ->orderBy('id')
                    ->first();

                return [
                    'productId' => $item->product_id,
                    'name'      => (string) $item->product->name,
                    'sku'       => $item->product->sku,
                    'image'     => $item->product->thumbnail ? ShopPresenter::image($item->product->thumbnail) : null,
                    'stock'     => (int) $item->quantity,
                    // A sale is always charged at the state price. The old page showed
                    // the base price when there was none, then refused the sale.
                    'price'     => $statePrice ? (float) $statePrice->price : null,
                    'priceText' => $statePrice ? showAmount($statePrice->price) : null,
                ];
            })
            ->values();

        // "Verify" reloads this page with ?code=XXXXXXXXXX; the cart stays in the browser.
        $code         = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('code', '')));
        $welcome      = null;
        $welcomeError = null;
        if ($code !== '') {
            try {
                $package = $this->welcomePackageService->findByCode($code)->load('user');
                $welcome = [
                    'code'       => $package->code,
                    'amount'     => (float) $package->amount,
                    'amountText' => showAmount($package->amount),
                    'username'   => (string) @$package->user->username,
                    'fullname'   => (string) @$package->user->fullname,
                ];
            } catch (\RuntimeException $e) {
                $welcomeError = $e->getMessage();
            }
        }

        return Inertia::render('User/Stockist/Pos', [
            'businessName' => (string) ($stockist->business_name ?: auth()->user()->fullname),
            'wallet'       => showAmount($stockist->wallet),
            'inventory'    => $inventory,
            'isMega'       => $stockist->isMega(),
            'welcome'      => $welcome,
            'welcomeError' => $welcomeError,
            // The receipt of the sale just made (flashed by checkout()).
            'receipt'      => session('pos_receipt'),
            'urls'         => [
                'pos'      => route('user.stockist.pos.index'),
                'checkout' => route('user.stockist.pos.checkout'),
                'buy'      => route('user.stockist.transfers.create'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'payment_method'     => 'required|in:cash,welcome_pack',
            'welcome_code'       => 'required_if:payment_method,welcome_pack|nullable|string|size:10',
        ]);

        $stockist = auth()->user()->stockist;


        if (!$stockist->is_active) {
            return back()->withErrors(['pos' => 'Your stockist account is not active. Contact Admin.']);
        }

        DB::beginTransaction();
        try {
            $lineItems = [];
            $cartTotal = 0.0;
            $trx       = getTrx(12);

            foreach ($request->items as $line) {
                $productId = (int) $line['product_id'];
                $qty       = (int) $line['quantity'];

                $store = Stockist_store::with('product')
                    ->where('user_id', auth()->id())
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $store || $store->quantity < $qty) {
                    $name = $store?->product?->name ?? "Product #{$productId}";
                    throw new \RuntimeException("Insufficient stock for {$name}.");
                }

                // deduct the product from stockist store. 
                $store->quantity -= $qty;
                $store->save();



                $statePrice = ProductStatePrice::where('state_id', $stockist->state_id)
                    ->where('product_id', $productId)
                    ->orderBy('id')
                    ->first();

                if (! $statePrice) {
                    throw new \RuntimeException(
                        "No price configured for {$store->product->name} in your state. Contact admin."
                    );
                }

                $unitPrice  = (float) $statePrice->price;
                $lineTotal  = $unitPrice * $qty;
                $cartTotal += $lineTotal;
                $lineItems[] = [
                    'product_id'   => $productId,
                    'product_name' => $store->product->name,
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'subtotal'     => $lineTotal,
                    'store'        => $store,
                    'state_price'  => $statePrice,
                ];
            }
            
            // ── Payment ──────────────────────────────────────────────
            $walletCredit = 0.0;
            $paymentNote  = 'Cash';

            if ($request->payment_method === 'welcome_pack') {
                $package = $this->welcomePackageService->findByCode($request->welcome_code);

                if ((float) $package->amount < $cartTotal) {
                    throw new \RuntimeException(sprintf(
                        'Welcome package value (%s) is less than cart total (%s). Remove some item from the cart.',
                        showAmount($package->amount, currencyFormat: false),
                        showAmount($cartTotal, currencyFormat: false)
                    ));
                } 


                if ((float) $package->amount > $cartTotal) {
                    throw new \RuntimeException(sprintf(
                        'Welcome package value (%s) is greater than cart total (%s). Add more item to the cart.',
                        showAmount($package->amount, currencyFormat: false),
                        showAmount($cartTotal, currencyFormat: false)
                    ));
                } 

                $this->welcomePackageService->redeem($package, $stockist);

                // Stockist rebate per product: tier % on its state price × PV × rDollar
                foreach ($lineItems as $line) {
                    $walletCredit += $this->rebates->credit(
                        $stockist, $line['store']->product, $line['quantity'], $trx,
                        "Welcome pack #{$package->code}", $line['state_price']
                    );
                }
                $paymentNote = "Welcome Pack #{$package->code}";
            }

            // Stock was already deducted in the loop above, on the locked row

            


            // ── Redemption ledger ─────────────────────────────────────
            StockistRedemption::create([
                'stockist_id'        => $stockist->id,
                'type'               => $request->payment_method === 'welcome_pack'
                    ? StockistRedemption::TYPE_WELCOME_PACK
                    : StockistRedemption::TYPE_CASH,
                'trx'                => $trx,
                'reference_code'     => $request->payment_method === 'welcome_pack' ? $package->code : null,
                'welcome_package_id' => $package->id ?? null,
                'items'              => array_map(fn ($line) => [
                    'product_id'   => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'quantity'     => $line['quantity'],
                    'unit_price'   => $line['unit_price'],
                    'subtotal'     => $line['subtotal'],
                ], $lineItems),
                'quantity'    => array_sum(array_column($lineItems, 'quantity')),
                'amount'      => $cartTotal,
                'redeemed_at' => now(),
            ]);


            // ── Audit record ─────────────────────────────────────────
            $freshStockist    = $stockist->fresh();
            $stx              = new Stransaction();
            $stx->user_id     = auth()->id();
            $stx->amount      = $cartTotal;
            $stx->trx_type    = '+';
            $stx->details     = sprintf('POS Sale (%s) — %d item(s)', $paymentNote, count($lineItems));
            $stx->remark      = 'pos_sale';
            $stx->trx         = $trx;
            $stx->post_balance = $freshStockist->wallet;
            $stx->save();

            DB::commit();

            return to_route('user.stockist.pos.index')->with('pos_receipt', [
                'trx'     => $trx,
                'items'   => array_sum(array_column($lineItems, 'quantity')),
                'total'   => showAmount($cartTotal),
                'payment' => $request->payment_method === 'welcome_pack' ? 'Welcome Package' : 'Cash Payment',
                'rebate'  => $walletCredit > 0 ? showAmount($walletCredit) : null,
            ]);

        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->withErrors(['pos' => $e->getMessage()]);
        } catch (\Throwable $e) {
            DB::rollBack();
            // This used to re-throw before the return, so every unexpected
            // error became an error page.
            report($e);
            return back()->withErrors(['pos' => 'An unexpected error occurred. Please try again.']);
        }
    }
}
