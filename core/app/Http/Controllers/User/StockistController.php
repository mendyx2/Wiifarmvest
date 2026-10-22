<?php
 //
namespace App\Http\Controllers\User;
use App\Models\User;
use App\Models\Order;
use App\Models\Matrix;
use App\Models\Stockist;
use App\Models\ProductStatePrice;
use App\Models\Invoice;
use App\Models\Product;
use App\Constants\Status;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Stockist_store;
use App\Models\StockistLocation;
use App\Models\InvoiceRedemption;
use App\Models\StockistRedemption;
use App\Models\Stockist_store_record;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Services\StockistRebateService;
use App\Services\UnilevelService;
use App\Services\WalletService;
use App\Models\RepurchasePv;

class StockistController extends Controller
{

   protected $unilevelService;
    
    public function __construct(UnilevelService $unilevelService, WalletService $walletService)
    {
        $this->unilevelService = $unilevelService;
        $this->walletService = $walletService;
    }

    /** Members: find stockists by city / state (?city=&state=). */
    public function findStockist(Request $request)
    {
        $pageTitle = "Search Stockist";
        $city      = trim((string) $request->query('city', ''));
        $state     = trim((string) $request->query('state', ''));
        $searched  = $city !== '' || $state !== '';

        $results = $searched ? StockistLocation::with(['stockist.services', 'stockist.user'])
            ->where('is_active', true)
            // Only stockists the admin has left active.
            ->whereHas('stockist', fn ($q) => $q->where('is_active', true))
            ->where(function ($query) use ($city, $state) {
                if ($city !== '') {
                    $query->orWhere('city', 'like', '%' . $city . '%');
                }
                if ($state !== '') {
                    $query->orWhere('state', 'like', '%' . $state . '%');
                }
            })
            ->limit(60)
            ->get()
            ->map(function ($location) {
                $stockist = $location->stockist;

                return [
                    'id'           => $location->id,
                    'businessName' => (string) $stockist->business_name,
                    'contact'      => trim((string) @$stockist->user->fullname) ?: (string) $stockist->business_name,
                    'phone'        => $stockist->business_phone,
                    'email'        => $stockist->business_email,
                    'verified'     => (bool) $stockist->is_verified,
                    'address'      => array_values(array_filter([
                        $location->address_line_1,
                        $location->address_line_2,
                        trim($location->city . ', ' . $location->state, ', '),
                        $location->country,
                        $location->postal_code ? 'Postal Code: ' . $location->postal_code : null,
                    ])),
                    'city'         => (string) $location->city,
                    'state'        => (string) $location->state,
                    'hours'        => $location->formatted_opening_hours,
                    'services'     => $stockist->services->pluck('service_name')->values(),
                    'directions'   => 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode((string) $location->full_address),
                ];
            })->values() : [];

        return Inertia::render('User/Stockist/Find', [
            'city'     => $city,
            'state'    => $state,
            'searched' => $searched,
            'results'  => $results,
            'findUrl'  => route('user.stockist.find'),
        ])->withViewData(compact('pageTitle'));
    }

    // Stockist dashboard methods (protected)
    public function dashboard(Request $request)
    {
        $pageTitle = "Stockist Dashboard";
        $user      = auth()->user();
        $stockist  = $user->stockist;

        $recentRedemptions = $stockist->redemptions()
            ->with(['invoice', 'user'])
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'today_redemptions' => $stockist->redemptions()->whereDate('created_at', today())->count(),
            'total_amount'      => $stockist->redemptions()->sum('total_amount'),
        ];

        // "Verify Invoice" reloads this page with ?invoice=CODE.
        $code    = trim((string) $request->query('invoice', ''));
        $lookup  = $code !== '' ? $this->lookupInvoice($stockist, $code) : null;
        $invoice = $lookup && !is_string($lookup) ? $lookup : null;

        return Inertia::render('User/Stockist/Dashboard', [
            'fullname' => $user->fullname,
            'stats'    => [
                'rebate'  => showAmount($user->stockist_rebate),
                'wallet'  => showAmount($stockist->wallet),
                'today'   => $stats['today_redemptions'],
                'total'   => showAmount($stats['total_amount']),
            ],
            'recent' => $recentRedemptions->map(fn ($r) => [
                'id'       => $r->id,
                'invoice'  => (string) @$r->invoice->invoice_code,
                'customer' => (string) @$r->user->username,
                'amount'   => showAmount($r->total_amount),
                'date'     => showDateTime($r->created_at, 'M j, H:i'),
            ]),
            'code'         => $code,
            'invoice'      => $invoice ? $this->presentInvoice($invoice) : null,
            'invoiceError' => is_string($lookup) ? $lookup : null,
            'dashboardUrl' => route('user.stockist.dashboard'),
            'processUrl'   => route('user.stockist.process.redemption'),
            'historyUrl'   => route('user.stockist.history'),
        ])->withViewData(compact('pageTitle'));
    }

    /**
     * Find an invoice this stockist may redeem, or say why not.
     *
     * @return Invoice|string
     */
    private function lookupInvoice(Stockist $stockist, string $code, bool $lock = false)
    {
        $invoice = Invoice::with(['order', 'state', 'redemptions'])
            ->where('invoice_code', $code)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();

        if (!$invoice || !$invoice->order) {
            return 'Invoice not found';
        }
        // A cancelled order has been refunded to the member.
        if ((int) $invoice->order->status === Status::ORDER_CANCELED) {
            return 'This order was cancelled and refunded; it cannot be redeemed.';
        }
        if ($invoice->is_fully_redeemed) {
            return 'This invoice has been fully redeemed';
        }
        if ($invoice->state_id != $stockist->state_id) {
            return 'This invoice is not for your state. It is for stockists in ' . (@$invoice->state->name ?: 'another state') . '.';
        }

        return $invoice;
    }

    /** The invoice as the dashboard shows it: per product, ordered / redeemed / remaining. */
    private function presentInvoice(Invoice $invoice): array
    {
        $original  = json_decode($invoice->items, true) ?? [];
        $remaining = $invoice->remaining_items;
        $skus      = Product::skuMap(array_column($original, 'product_id'));

        return [
            'code'     => $invoice->invoice_code,
            'customer' => (string) @$invoice->order->user->username,
            'items'    => collect($original)->map(fn ($item) => [
                'productId' => (int) $item['product_id'],
                'name'      => (string) ($item['product_name'] ?? ''),
                'sku'       => $skus[$item['product_id']] ?? null,
                'ordered'   => (int) $item['quantity'],
                'remaining' => (int) ($remaining[$item['product_id']] ?? 0),
                'redeemed'  => (int) $item['quantity'] - (int) ($remaining[$item['product_id']] ?? 0),
            ])->values(),
        ];
    }

    public function processRedemption(Request $request)
    {
        $stockist = auth()->user()->stockist;
        $user     = auth()->user();

        $request->validate([
            'invoice_code'     => 'required|string',
            'redeemed_items'   => 'required|array',
            'redeemed_items.*' => 'nullable|integer|min:0',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $back = to_route('user.stockist.dashboard', ['invoice' => $request->invoice_code]);

        DB::beginTransaction();
        try {
            // The invoice row is locked, so two stockists (or a double click)
            // can't both hand over the same remaining items.
            $invoice = $this->lookupInvoice($stockist, $request->invoice_code, lock: true);
            if (is_string($invoice)) {
                throw new \DomainException($invoice);
            }

            $trx            = $invoice->order->trx;
            $remainingItems = $invoice->remaining_items;
            $totalAmount    = 0;
            $redeemedItems  = [];
            $user_dist      = $invoice->order->user;

            foreach ($request->redeemed_items as $productId => $quantity) {
                $quantity = (int) $quantity;
                if ($quantity <= 0 || !isset($remainingItems[$productId])) {
                    continue;
                }

                $product = Product::find($productId);
                if (!$product) {
                    throw new \DomainException("Product not found: {$productId}");
                }
                if ($quantity > $remainingItems[$productId]) {
                    throw new \DomainException("You can hand over at most {$remainingItems[$productId]} of {$product->name}.");
                }

                $stockist_store = Stockist_store::where('user_id', $user->id)->where('product_id', $productId)->lockForUpdate()->first();
                if (!$stockist_store) {
                    throw new \DomainException("You don't have {$product->name} in your store.");
                }
                if ($quantity > $stockist_store->quantity) {
                    $diff = $quantity - $stockist_store->quantity;
                    throw new \DomainException("Your stock of {$product->name} is {$stockist_store->quantity}; you need {$diff} more.");
                }

                $product_state_price = ProductStatePrice::where('state_id', (int) $stockist->state_id)->where('product_id', $product->id)->first();
                if (!$product_state_price) {
                    throw new \DomainException("Contact admin to set up your state price for {$product->name}.");
                }

                $totalAmount    += $product_state_price->price * $quantity;
                $redeemedItems[] = [
                    'product_id'   => $productId,
                    'product_name' => $product->name,
                    'quantity'     => $quantity,
                    'price'        => $product_state_price->price,
                ];

                // 1.) take the goods out of the stockist's store
                $stockist_store->quantity -= $quantity;
                $stockist_store->save();

                // 2.) record this movement in Stockist_store_record
                $this->stockistStore($productId, $invoice->id, $product_state_price->price, $quantity);

                // 3.) Stockist rebate: the Mega or Micro % set on this product's
                // price for the stockist's state, applied to its PV in naira
                // (same rule as welcome packs and affiliate pickups)
                app(StockistRebateService::class)->credit(
                    $stockist, $product, $quantity, $trx, 'Invoice ' . $invoice->invoice_code, $product_state_price
                );

                // 4.) unilevel bonus for the buyer's sponsor
                if ($user_dist->ref_by > 0) {
                    $user_fG = User::find($user_dist->ref_by);
                    $this->unilevelService->process($invoice, $user_fG, $product, $quantity, $trx);
                }

                // 5.) product PV for the repurchase award, shared up the tree
                $this->recordRepurchasePv($user_dist, $product, $quantity);
            }

            if (empty($redeemedItems)) {
                throw new \DomainException('Enter a quantity for at least one item.');
            }

            $redemption = InvoiceRedemption::create([
                'invoice_id'     => $invoice->id,
                'stockist_id'    => $stockist->id,
                'user_id'        => $invoice->order->user_id,
                'redeemed_items' => $redeemedItems,
                'total_amount'   => $totalAmount,
                'notes'          => $request->notes,
                'redeemed_at'    => now(),
            ]);

            // Unified redemption ledger (accounting/reporting only)
            StockistRedemption::create([
                'stockist_id'      => $stockist->id,
                'type'             => StockistRedemption::TYPE_INVOICE,
                'trx'              => $trx,
                'reference_code'   => $invoice->invoice_code,
                'invoice_id'       => $invoice->id,
                'customer_user_id' => $invoice->order->user_id,
                'items'            => $redeemedItems,
                'quantity'         => array_sum(array_column($redeemedItems, 'quantity')),
                'amount'           => $totalAmount,
                'notes'            => $request->notes,
                'redeemed_at'      => now(),
            ]);

            // Pay the stockist for the goods handed over (atomic SQL increment).
            $stockist->addToWallet($totalAmount);
            $stockist->refresh();
            stockistTransaction($stockist, $user, $trx, $totalAmount);

            $fully = $invoice->load('redemptions')->is_fully_redeemed;
            if ($fully) {
                $invoice->is_fully_redeemed = true;
                $invoice->fully_redeemed_at = now();
                $invoice->redeemed_at       = now();
                $invoice->save();

                $invoice->order->status = Status::ORDER_COLLECTED;
                $invoice->order->save();
            }

            DB::commit();
        } catch (\DomainException $e) {
            DB::rollBack();
            $notify[] = ['error', $e->getMessage()];
            return $back->withNotify($notify);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            $notify[] = ['error', 'The redemption could not be processed. Please try again.'];
            return $back->withNotify($notify);
        }

        $notify[] = ['success', $fully
            ? 'Full redemption processed. All items have been handed over.'
            : 'Partial redemption processed. The customer can collect the remaining items at any stockist.'];
        return $back->withNotify($notify);
    }

    protected function stockistStore($productId, $invoiceId, $productPrice, $quantity){
        $stockist_store_record = new Stockist_store_record();
        $stockist_store_record->user_id    =  auth()->id();
        $stockist_store_record->product_id =  $productId;
        $stockist_store_record->invoice_id =  $invoiceId;
        $stockist_store_record->price      =  $productPrice;
        $stockist_store_record->quantity   =  $quantity;
        $stockist_store_record->save();
    }
    
    
    /** Safety bound on the upline walk — real sponsorship chains never get close to this. */
    protected const MAX_UPLINE_PV_LEVELS = 50;

    protected function recordRepurchasePv(User $user, Product $product, int $quantity): void
    {
        $pvEarned = round((float)($product->pv ?? 0) * $quantity, 2);
        if ($pvEarned <= 0) {
            return;
        }

        $record = RepurchasePv::lockForUpdate()->firstOrNew(['user_id' => $user->id]);
        $record->total_pv = round((float)($record->total_pv ?? 0) + $pvEarned, 2);
        $record->save();

        // Distribute repurchase PV upline through the binary matrix tree
        $this->shareRepurchasePvUpline($user, $pvEarned);
    }

    /**
     * Share the same repurchase PV up the binary matrix's parent route
     * (matrices.parent_id chain, stage 1) rather than the sponsorship
     * (ref_by) chain. Only uplines who have subscribed to a project
     * ($user->project_id is not null) receive the PV; users without one
     * are skipped, but the chain keeps walking upward past them regardless.
     */
    protected function shareRepurchasePvUpline(User $user, float $pvEarned): void
    {
        if ($pvEarned <= 0) {
            return;
        }

        $matrix      = Matrix::where('user_id', $user->id)->where('stage_id', 1)->first();
        $childUserId = $user->id;
        $levels      = 0;

        while ($matrix && $matrix->parent_id && $levels < self::MAX_UPLINE_PV_LEVELS) {
            $matrix = Matrix::find($matrix->parent_id);
            if (!$matrix) {
                break;
            }

            $current = User::find($matrix->user_id);

            if ($current && $current->project_id !== null) {
                $uplineRecord = RepurchasePv::lockForUpdate()->firstOrNew(['user_id' => $current->id]);
                $uplineRecord->total_pv = round((float)($uplineRecord->total_pv ?? 0) + $pvEarned, 2);
                $uplineRecord->save();

                // Same left/right resolution used by updateProductPV(), so this
                // shared PV lands in pv_logs against the correct leg.
                $position = $matrix->left == $childUserId ? 1 : 2;
                pvLog($current->id, $pvEarned, $position, '+', "Repurchase PV shared from {$user->username}'s purchase");
            }

            $childUserId = $matrix->user_id;
            $levels++;
        }
    }
    

    

    public function redemptionsReport(Request $request)
    {
        $pageTitle = 'Redemption Report';
        $stockist  = auth()->user()->stockist;

        $grouped = StockistRedemption::where('stockist_id', $stockist->id)
            ->selectRaw('type, SUM(quantity) as qty, SUM(amount) as amt, COUNT(*) as cnt')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $totals = [];
        foreach (StockistRedemption::$types as $type => $label) {
            $totals[$type] = [
                'label' => $label,
                'qty'   => (int) ($grouped[$type]->qty ?? 0),
                'amt'   => (float) ($grouped[$type]->amt ?? 0),
                'cnt'   => (int) ($grouped[$type]->cnt ?? 0),
            ];
        }
        $grandQty = array_sum(array_column($totals, 'qty'));
        $grandAmt = array_sum(array_column($totals, 'amt'));

        $type = array_key_exists((string) $request->type, StockistRedemption::$types) ? $request->type : null;

        $redemptions = StockistRedemption::where('stockist_id', $stockist->id)
            ->when($type, fn ($q) => $q->ofType($type))
            ->with('customer')
            ->latest('redeemed_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('User/Stockist/Redemptions', [
            'totals' => collect($totals)->map(fn ($t, $key) => [
                'type'   => $key,
                'label'  => $t['label'],
                'amount' => showAmount($t['amt']),
                'qty'    => $t['qty'],
                'count'  => $t['cnt'],
                'url'    => route('user.stockist.redemptions', ['type' => $key]),
            ])->values(),
            'grandAmount' => showAmount($grandAmt),
            'grandQty'    => $grandQty,
            'type'        => $type,
            'typeLabel'   => $type ? StockistRedemption::$types[$type] : null,
            'redemptions' => $redemptions->through(fn ($r) => [
                'id'        => $r->id,
                'type'      => $r->type,
                'typeLabel' => $r->type_label,
                'reference' => $r->reference_code ?: ($r->trx ?: '—'),
                'customer'  => $r->customer ? $r->customer->fullname : ($r->buyer_name ?: null),
                'qty'       => (int) $r->quantity,
                'amount'    => showAmount($r->amount),
                'date'      => $r->redeemed_at ? showDateTime($r->redeemed_at, 'M j, Y H:i') : '',
            ]),
            'allUrl'       => route('user.stockist.redemptions'),
            'dashboardUrl' => route('user.stockist.dashboard'),
        ])->withViewData(compact('pageTitle'));
    }

    public function redemptionHistory(Request $request)
    {
        $pageTitle = 'Redemption History';
        $stockist  = auth()->user()->stockist;

        $range  = in_array($request->range, ['today', 'week', 'month', 'custom'], true) ? $request->range : 'all';
        $sort   = in_array($request->sort, ['oldest', 'amount_high', 'amount_low'], true) ? $request->sort : 'newest';
        $search = trim((string) $request->search);
        $from   = $range === 'custom' ? $request->date('from') : null;
        $to     = $range === 'custom' ? $request->date('to') : null;

        // Filters run here, over every redemption; the old page only filtered
        // and counted the 10 on screen.
        $query = $stockist->redemptions()
            ->when($range === 'today', fn ($q) => $q->whereDate('created_at', today()))
            ->when($range === 'week', fn ($q) => $q->where('created_at', '>=', now()->startOfWeek()))
            ->when($range === 'month', fn ($q) => $q->where('created_at', '>=', now()->startOfMonth()))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('invoice', fn ($i) => $i->where('invoice_code', 'like', "%{$search}%"))
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%"))));

        $query = match ($sort) {
            'oldest'      => $query->oldest('id'),
            'amount_high' => $query->orderByDesc('total_amount'),
            'amount_low'  => $query->orderBy('total_amount'),
            default       => $query->latest('id'),
        };

        if ($request->query('export') === 'csv') {
            return $this->exportRedemptions((clone $query)->with(['invoice', 'user'])->get());
        }

        $stats = [
            'count'    => (clone $query)->count(),
            'value'    => showAmount((clone $query)->sum('total_amount')),
            'today'    => $stockist->redemptions()->whereDate('created_at', today())->count(),
            'invoices' => (clone $query)->distinct('invoice_id')->count('invoice_id'),
        ];

        $redemptions = $query->with(['invoice', 'user'])->paginate(10)->withQueryString();

        // Redeemed items are stored as JSON snapshots: one query for their SKUs
        $items = fn ($redemption) => collect(is_string($redemption->redeemed_items) ? json_decode($redemption->redeemed_items, true) : $redemption->redeemed_items)
            ->map(fn ($item) => is_string($item) ? json_decode($item, true) : $item);
        $skus = Product::skuMap($redemptions->getCollection()->flatMap(fn ($r) => $items($r)->pluck('product_id')));

        return Inertia::render('User/Stockist/History', [
            'redemptions' => $redemptions->through(fn ($r) => [
                'id'       => $r->id,
                'invoice'  => (string) @$r->invoice->invoice_code,
                'customer' => trim((string) @$r->user->fullname) ?: (string) @$r->user->username,
                'username' => (string) @$r->user->username,
                'amount'   => showAmount($r->total_amount),
                'date'     => showDateTime($r->created_at, 'F j, Y \a\t g:i A'),
                'short'    => showDateTime($r->created_at, 'M j, Y H:i'),
                'notes'    => $r->notes,
                'items'    => $items($r)->map(fn ($item) => [
                    'name'     => (string) ($item['product_name'] ?? 'Product'),
                    'sku'      => $skus[$item['product_id'] ?? 0] ?? null,
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'price'    => showAmount($item['price'] ?? 0),
                    'subtotal' => showAmount(($item['quantity'] ?? 0) * ($item['price'] ?? 0)),
                ])->values(),
            ]),
            'stats'        => $stats,
            'filters'      => ['range' => $range, 'sort' => $sort, 'search' => $search, 'from' => $from?->format('Y-m-d') ?? '', 'to' => $to?->format('Y-m-d') ?? ''],
            'dashboardUrl' => route('user.stockist.dashboard'),
        ])->withViewData(compact('pageTitle'));
    }

    /** CSV of the filtered redemptions (all pages, not just the one on screen). */
    private function exportRedemptions($redemptions)
    {
        $filename = 'redemptions-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($redemptions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Redemption ID', 'Invoice Code', 'Customer', 'Amount', 'Date', 'Items']);
            foreach ($redemptions as $r) {
                $items = is_string($r->redeemed_items) ? json_decode($r->redeemed_items, true) : $r->redeemed_items;
                fputcsv($out, [
                    $r->id,
                    @$r->invoice->invoice_code,
                    @$r->user->username,
                    getAmount($r->total_amount),
                    $r->created_at->format('Y-m-d H:i'),
                    count($items ?? []),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function profile()
    {
        $pageTitle = 'Stockist Profile';
        $stockist  = auth()->user()->stockist->load(['services', 'primaryLocation']);
        $location  = $stockist->primaryLocation;
        $hours     = (array) ($location?->opening_hours ?? []);

        return Inertia::render('User/Stockist/Profile', [
            'active'  => (bool) $stockist->is_active,
            'basic'   => [
                'business_name'                => (string) $stockist->business_name,
                'business_registration_number' => (string) $stockist->business_registration_number,
                'business_email'               => (string) $stockist->business_email,
                'business_phone'               => (string) $stockist->business_phone,
                'website'                      => (string) $stockist->website,
                'business_description'         => (string) $stockist->business_description,
            ],
            'location' => $location ? [
                'address_line_1' => (string) $location->address_line_1,
                'address_line_2' => (string) $location->address_line_2,
                'city'           => (string) $location->city,
                'state'          => (string) $location->state,
                'postal_code'    => (string) $location->postal_code,
                'country'        => (string) ($location->country ?: 'Nigeria'),
                'phone'          => (string) $location->phone,
                'email'          => (string) $location->email,
            ] : null,
            // A day with no times is closed; a day never set defaults to 08:00–17:00.
            'hours' => collect(self::DAYS)->map(fn ($day) => [
                'day'   => $day,
                'open'  => array_key_exists($day, $hours) ? ($hours[$day]['open'] ?? null) : '08:00',
                'close' => array_key_exists($day, $hours) ? ($hours[$day]['close'] ?? null) : '17:00',
            ]),
            'services' => $stockist->services->isNotEmpty()
                ? $stockist->services->map(fn ($s) => ['name' => (string) $s->service_name, 'description' => (string) $s->description])->values()
                : [
                    ['name' => 'Product Redemption', 'description' => 'Redeem customer products using invoice codes'],
                    ['name' => 'Customer Support', 'description' => 'Provide support and assistance to customers'],
                ],
            'urls' => [
                'basic'     => route('user.stockist.profile.basic-info'),
                'location'  => route('user.stockist.profile.location'),
                'hours'     => route('user.stockist.profile.opening-hours'),
                'services'  => route('user.stockist.profile.services'),
                'dashboard' => route('user.stockist.dashboard'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function updateBasicInfo(Request $request)
    {
        $data = $request->validate([
            'business_name'                => 'required|string|max:255',
            'business_email'               => 'required|email|max:255',
            'business_phone'               => 'required|string|max:20',
            'business_description'         => 'nullable|string|max:1000',
            'website'                      => 'nullable|url|max:255',
            'business_registration_number' => 'nullable|string|max:100',
        ]);

        auth()->user()->stockist->update($data);

        $notify[] = ['success', 'Basic information updated successfully!'];
        return back()->withNotify($notify);
    }

    public function updateLocation(Request $request)
    {
        $data = $request->validate([
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city'           => 'required|string|max:255',
            'state'          => 'required|string|max:255',
            'country'        => 'required|string|max:255',
            'postal_code'    => 'nullable|string|max:20',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
        ]);

        $stockist = auth()->user()->stockist;
        if ($stockist->primaryLocation) {
            $stockist->primaryLocation->update($data);
        } else {
            $stockist->locations()->create($data + ['is_primary' => true, 'is_active' => true]);
        }

        $notify[] = ['success', 'Location information updated successfully!'];
        return back()->withNotify($notify);
    }

    public function updateOpeningHours(Request $request)
    {
        $request->validate([
            'opening_hours'                     => 'required|array',
            'opening_hours.*.closed'            => 'boolean',
            'opening_hours.*.open'              => 'nullable|required_unless:opening_hours.*.closed,true|date_format:H:i',
            'opening_hours.*.close'             => 'nullable|required_unless:opening_hours.*.closed,true|date_format:H:i',
        ], [
            'opening_hours.*.open.required_unless'  => 'Set an opening time for every open day (or mark the day closed).',
            'opening_hours.*.close.required_unless' => 'Set a closing time for every open day (or mark the day closed).',
            'opening_hours.*.*.date_format'         => 'Times must look like 09:00.',
        ]);

        $location = auth()->user()->stockist->primaryLocation;
        if (!$location) {
            // Before, this said "updated successfully" and saved nothing.
            $notify[] = ['error', 'Save your business location first; opening hours belong to it.'];
            return back()->withNotify($notify);
        }

        // Closed days are stored without times (the old form could not save them).
        $hours = [];
        foreach (self::DAYS as $day) {
            $row          = $request->input("opening_hours.$day", []);
            $closed       = filter_var($row['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $hours[$day]  = ['open' => $closed ? null : ($row['open'] ?? null), 'close' => $closed ? null : ($row['close'] ?? null)];
        }
        $location->update(['opening_hours' => $hours]);

        $notify[] = ['success', 'Opening hours updated successfully!'];
        return back()->withNotify($notify);
    }

    public function updateServices(Request $request)
    {
        $data = $request->validate([
            'services'               => 'required|array|min:1',
            'services.*.name'        => 'required|string|max:255',
            'services.*.description' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data) {
            $stockist = auth()->user()->stockist;
            $stockist->services()->delete();
            foreach ($data['services'] as $service) {
                $stockist->services()->create([
                    'service_name' => $service['name'],
                    'description'  => $service['description'] ?? null,
                    'is_active'    => true,
                ]);
            }
        });

        $notify[] = ['success', 'Services updated successfully!'];
        return back()->withNotify($notify);
    }

    


    

    
   
    

    
}
