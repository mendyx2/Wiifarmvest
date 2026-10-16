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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
 
class StockistPosController extends Controller
{
    public function __construct(
        private readonly WelcomePackageService $welcomePackageService,
        private readonly StockistRebateService $rebates,
    ) {}

    public function index()
    {
        $pageTitle = 'POS';
        $stockist = auth()->user()->stockist;

        $inventory = Stockist_store::with('product')
            ->where('user_id', auth()->id())
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($item) use ($stockist) {
                $statePrice = ProductStatePrice::where('state_id', $stockist->state_id)
                    ->where('product_id', $item->product_id)
                    ->first();


                $item->unit_price = $statePrice ? (float) $statePrice->price : (float) ($item->product->price ?? 0);
                return $item;
            })
            ->filter(fn ($item) => $item->product !== null)
            ->values();

        return view('Template::user.stockist.pos', compact('inventory', 'stockist', 'pageTitle'));
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'payment_method'     => 'required|in:cash,welcome_pack',
            'welcome_code'       => 'required_if:payment_method,welcome_pack|nullable|string|size:10',
        ]);

        $stockist = auth()->user()->stockist;


        if (! $stockist) {
            return response()->json(['success' => false, 'message' => 'Stockist profile not found.'], 403);
        }
        if (!$stockist->is_active) {
            return response()->json(['success' => false, 'message' => 'Stockist  not active. Contact Admin.'], 403);
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

            return response()->json([
                'success'         => true,
                'message'         => 'Sale processed successfully.',
                'trx'             => $trx,
                'total'           => $cartTotal,
                'total_formatted' => showAmount($cartTotal, currencyFormat: false),
                'payment_method'  => $request->payment_method,
                'wallet_credited' => $walletCredit > 0
                    ? showAmount($walletCredit, currencyFormat: false)
                    : null,
                'items_count'     => count($lineItems),
            ]);

        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
            Log::error('POS checkout error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
