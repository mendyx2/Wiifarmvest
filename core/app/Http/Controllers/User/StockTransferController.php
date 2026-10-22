<?php

namespace App\Http\Controllers\User;

use App\Exceptions\StockTransferException;
use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Stockist;
use App\Models\Stockist_store;
use App\Services\StockTransferService;
use App\Support\ShopPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Stock transfers from the stockists' side. Mini stockists request stock and
 * follow their requests; Mega stockists see what's asked of them and confirm
 * or decline. All money and stock changes happen in StockTransferService.
 */
class StockTransferController extends Controller
{
    public function __construct(private StockTransferService $transfers)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $stockist  = $this->stockist();
        $isMega    = $stockist->isMega();
        $pageTitle = $isMega ? 'Stock Requests' : 'My Orders';
        $status    = array_key_exists((string) $request->status, StockTransfer::STATUSES) ? $request->status : null;

        $base = StockTransfer::where($isMega ? 'seller_stockist_id' : 'buyer_stockist_id', $stockist->id);

        $counts = (clone $base)->selectRaw('status, COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total')
            ->groupBy('status')->get()->keyBy('status');

        $transfers = (clone $base)
            ->with(['buyer.user:id,firstname,lastname,username', 'seller.user:id,firstname,lastname,username', 'items'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw('status = ? DESC', [StockTransfer::STATUS_PENDING])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // A Mini's orders from the company before Minis moved to buying from a Mega
        $companyOrders = $isMega ? 0 : $stockist->orders()->count();

        $stat = fn ($s) => ['count' => (int) ($counts->get($s)->n ?? 0), 'total' => showAmount($counts->get($s)->total ?? 0)];

        return Inertia::render('User/Stockist/Transfers/Index', [
            'isMega' => $isMega,
            'stats'  => [
                'pending'   => $stat(StockTransfer::STATUS_PENDING),
                'completed' => $stat(StockTransfer::STATUS_COMPLETED),
                'closed'    => (int) ($counts->get(StockTransfer::STATUS_DECLINED)->n ?? 0) + (int) ($counts->get(StockTransfer::STATUS_CANCELLED)->n ?? 0),
                'wallet'    => showAmount($stockist->wallet),
                'storeType' => (string) $stockist->store_type_name,
            ],
            'tabs' => collect(StockTransfer::STATUSES)->map(fn ($label, $key) => [
                'key'   => $key,
                'label' => $key === StockTransfer::STATUS_PENDING ? 'Pending' : $label,
                'count' => (int) ($counts->get($key)->n ?? 0),
                'url'   => route('user.stockist.transfers.index', ['status' => $key]),
            ])->values(),
            'status'    => $status,
            'transfers' => $transfers->through(function ($transfer) use ($isMega) {
                $other = $isMega ? $transfer->buyer : $transfer->seller;

                return [
                    'id'          => $transfer->id,
                    'code'        => $transfer->code,
                    'date'        => showDateTime($transfer->created_at, 'd M Y, h:i A'),
                    'other'       => $other?->business_name ?? '—',
                    'otherName'   => (string) $other?->user?->fullname,
                    'products'    => $transfer->items->count(),
                    'units'       => (int) $transfer->total_quantity,
                    'amount'      => showAmount($transfer->total_amount),
                    'pending'     => $transfer->isPending(),
                    'statusLabel' => $transfer->statusLabel(),
                    'statusClass' => $transfer->statusClass(),
                    'url'         => route('user.stockist.transfers.show', $transfer->id),
                ];
            }),
            'companyOrders' => $companyOrders,
            'urls' => [
                'index'         => route('user.stockist.transfers.index'),
                'create'        => route('user.stockist.transfers.create'),
                'companyOrders' => route('user.stockist.inventory.orders'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function create(Request $request)
    {
        $stockist = $this->stockist();
        if ($stockist->isMega()) {
            return to_route('user.stockist.transfers.index');
        }

        $pageTitle = 'Buy Stock from a Mega Stockist';
        $sellers   = $this->transfers->sellersFor($stockist);
        $seller    = $sellers->firstWhere('id', (int) $request->seller) ?? ($sellers->count() === 1 ? $sellers->first() : null);
        $catalogue = $seller ? $this->transfers->catalogue($seller) : collect();

        return Inertia::render('User/Stockist/Transfers/Create', [
            'state'   => (string) $stockist->state_name,
            'wallet'  => (float) $stockist->wallet,
            'walletText' => showAmount($stockist->wallet),
            'sellers' => $sellers->map(fn ($option) => [
                'id'       => $option->id,
                'name'     => $option->business_name ?: 'Mega stockist #' . $option->id,
                'contact'  => trim((string) $option->user?->fullname),
                'phone'    => $option->business_phone ?: $option->user?->mobile,
                'products' => (int) $option->products_in_stock,
                'url'      => route('user.stockist.transfers.create', ['seller' => $option->id]),
            ])->values(),
            'seller'    => $seller ? ['id' => $seller->id, 'name' => (string) $seller->business_name] : null,
            'catalogue' => $catalogue->map(fn ($row) => [
                'productId' => $row->product->id,
                'name'      => (string) $row->product->name,
                'sku'       => $row->product->sku,
                'image'     => ShopPresenter::image($row->product->thumbnail),
                'available' => (int) $row->available,
                'price'     => (float) $row->unit_price,
                'priceText' => showAmount($row->unit_price),
            ])->values(),
            'urls' => [
                'index' => route('user.stockist.transfers.index'),
                'store' => route('user.stockist.transfers.store'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'seller_id'    => 'required|integer',
            'quantities'   => 'required|array|max:' . StockTransferService::MAX_LINES,
            'quantities.*' => 'nullable|integer|min:0|max:' . StockTransferService::MAX_QUANTITY,
            'note'         => 'nullable|string|max:500',
        ]);

        $buyer  = $this->stockist();
        $seller = Stockist::find($request->seller_id);

        if (!$seller) {
            $notify[] = ['error', 'That Mega stockist was not found.'];
            return back()->withNotify($notify);
        }

        try {
            $transfer = $this->transfers->request($buyer, $seller, $request->quantities, $request->note);
        } catch (StockTransferException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', "Request {$transfer->code} sent. " . showAmount($transfer->total_amount) . ' is held until ' . $seller->business_name . ' confirms.'];
        return to_route('user.stockist.transfers.show', $transfer->id)->withNotify($notify);
    }

    public function show(int $id)
    {
        $stockist = $this->stockist();
        $transfer = StockTransfer::with(['items.product', 'buyer.user', 'seller.user', 'state'])
            ->involving($stockist)
            ->findOrFail($id);

        $isSeller  = (int) $transfer->seller_stockist_id === (int) $stockist->id;
        $pageTitle = 'Stock Request ' . $transfer->code;

        // For a pending request, what the Mega holds now and what they'd earn
        $onHand = collect();
        $rebate = null;
        if ($isSeller && $transfer->isPending()) {
            $onHand = Stockist_store::where('user_id', $stockist->user_id)
                ->whereIn('product_id', $transfer->items->pluck('product_id'))
                ->orderBy('id')->get()->unique('product_id')->pluck('quantity', 'product_id');
            $rebate = $this->transfers->rebatePreview($transfer);
        }

        $pending   = $transfer->isPending();
        $completed = $transfer->status === StockTransfer::STATUS_COMPLETED;
        $party     = fn ($s) => [
            'name'    => (string) $s->business_name,
            'contact' => trim((string) $s->user?->fullname),
            'phone'   => $s->business_phone ?: $s->user?->mobile,
        ];

        return Inertia::render('User/Stockist/Transfers/Show', [
            'isSeller' => $isSeller,
            'transfer' => [
                'id'          => $transfer->id,
                'code'        => $transfer->code,
                'date'        => showDateTime($transfer->created_at, 'd M Y, h:i A'),
                'pending'     => $pending,
                'completed'   => $completed,
                'statusLabel' => $transfer->statusLabel(),
                'statusClass' => $transfer->statusClass(),
                'units'       => (int) $transfer->total_quantity,
                'total'       => showAmount($transfer->total_amount),
                'rebateEarned'=> showAmount($transfer->rebate_amount),
                'sellerAfter' => showAmount($transfer->seller_balance_after),
                'buyerAfter'  => showAmount($transfer->buyer_balance_after),
                'trx'         => (string) $transfer->trx,
                'buyerNote'   => $transfer->buyer_note,
                'decisionNote'=> $transfer->decision_note,
                'closedBy'    => $transfer->closed_by,
                'closedAt'    => $transfer->closedAt() ? showDateTime($transfer->closedAt(), 'd M Y, h:i A') : null,
                'buyer'       => $party($transfer->buyer),
                'seller'      => $party($transfer->seller),
                'items'       => $transfer->items->map(fn ($item) => [
                    'id'        => $item->id,
                    'name'      => (string) $item->product_name,
                    'sku'       => $item->product?->sku,
                    'image'     => $item->product ? ShopPresenter::image($item->product->thumbnail) : null,
                    'quantity'  => (int) $item->quantity,
                    'price'     => showAmount($item->unit_price),
                    'total'     => showAmount($item->line_total),
                    // What the Mega holds now (pending request, seller view only)
                    'onHand'    => $isSeller && $pending ? (int) ($onHand[$item->product_id] ?? 0) : null,
                    // Stock before → after (completed transfer), from this stockist's side
                    'stockBefore' => $completed ? (int) ($isSeller ? $item->seller_stock_before : $item->buyer_stock_before) : null,
                    'stockAfter'  => $completed ? (int) ($isSeller ? $item->seller_stock_after : $item->buyer_stock_after) : null,
                ])->values(),
            ],
            'rebate' => $rebate !== null ? showAmount($rebate) : null,
            'urls'   => [
                'index'   => route('user.stockist.transfers.index'),
                'confirm' => route('user.stockist.transfers.confirm', $transfer->id),
                'decline' => route('user.stockist.transfers.decline', $transfer->id),
                'cancel'  => route('user.stockist.transfers.cancel', $transfer->id),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function confirm(int $id)
    {
        return $this->act($id, fn (StockTransfer $transfer, Stockist $stockist) => $this->transfers->confirm($transfer, $stockist),
            fn (StockTransfer $t) => "Done. {$t->total_quantity} unit(s) moved to {$t->buyer->business_name}; "
                . showAmount($t->total_amount) . ' added to your stockist wallet and ' . showAmount($t->rebate_amount) . ' rebate earned.');
    }

    public function decline(Request $request, int $id)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        return $this->act($id, fn (StockTransfer $transfer, Stockist $stockist) => $this->transfers->decline($transfer, $stockist, $request->reason),
            fn (StockTransfer $t) => "Request {$t->code} declined. The Mini stockist has been refunded.");
    }

    public function cancel(int $id)
    {
        return $this->act($id, fn (StockTransfer $transfer, Stockist $stockist) => $this->transfers->cancel($transfer, $stockist),
            fn (StockTransfer $t) => "Request {$t->code} cancelled. " . showAmount($t->total_amount) . ' is back in your stockist wallet.');
    }

    private function act(int $id, callable $action, callable $message)
    {
        $stockist = $this->stockist();
        $transfer = StockTransfer::involving($stockist)->findOrFail($id);

        try {
            $transfer = $action($transfer, $stockist);
        } catch (StockTransferException $e) {
            $notify[] = ['error', $e->getMessage()];
            return to_route('user.stockist.transfers.show', $id)->withNotify($notify);
        }

        $notify[] = ['success', $message($transfer)];
        return to_route('user.stockist.transfers.show', $id)->withNotify($notify);
    }

    private function stockist(): Stockist
    {
        return Stockist::where('user_id', auth()->id())->firstOrFail();
    }
}
