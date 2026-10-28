<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockTransferException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAccessMiddleware;
use App\Models\StockTransfer;
use App\Models\Stransaction;
use App\Models\Transaction;
use App\Services\StockTransferService;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin view of stock transfers between stockists: the list, the full
 * breakdown of one transfer, and cancelling a request that is stuck.
 */
class StockTransferController extends Controller
{
    public function __construct(private StockTransferService $transfers)
    {
        parent::__construct();
    }

    /** Manage Stockist → Stock Transfers (Pages/Admin/Stockist/Transfers.tsx). */
    public function index(Request $request)
    {
        $pageTitle = 'Stock Transfers';
        $status    = array_key_exists((string) $request->status, StockTransfer::STATUSES) ? $request->status : '';
        $search    = trim((string) $request->search);
        $date      = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : '';
        $from      = $date($request->date_from);
        $to        = $date($request->date_to);

        $transfers = StockTransfer::with(['buyer.user:id,firstname,lastname,username', 'seller.user:id,firstname,lastname,username', 'state:id,name'])
            ->withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(fn ($q) => $q->where('code', 'like', $like)
                    ->orWhereHas('buyer', fn ($s) => $s->where('business_name', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('username', 'like', $like)))
                    ->orWhereHas('seller', fn ($s) => $s->where('business_name', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('username', 'like', $like))));
            })
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (StockTransfer $t) => [
                'id'       => $t->id,
                'code'     => (string) $t->code,
                'date'     => showDateTime($t->created_at, 'd M Y, h:i A'),
                'seller'   => ['name' => $t->seller?->business_name ?? '—', 'username' => $t->seller?->user?->username],
                'buyer'    => ['name' => $t->buyer?->business_name ?? '—', 'username' => $t->buyer?->user?->username],
                'state'    => $t->state?->name,
                'units'    => (int) $t->total_quantity,
                'products' => (int) $t->items_count,
                'amount'   => showAmount($t->total_amount),
                'rebate'   => $t->status === StockTransfer::STATUS_COMPLETED ? showAmount($t->rebate_amount) : null,
                'status'   => AdminPresenter::badge(ucfirst($t->status), $t->adminBadge()),
                'statusLabel' => $t->statusLabel(),
                'url'      => route('admin.stockist.transfers.show', $t->id),
            ]);

        $stats = StockTransfer::selectRaw('status, COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(rebate_amount), 0) AS rebate, COALESCE(SUM(total_quantity), 0) AS units')
            ->groupBy('status')->get()->keyBy('status');
        $n = fn ($key) => (int) ($stats[$key]->n ?? 0);

        return Inertia::render('Admin/Stockist/Transfers', [
            'transfers' => $transfers,
            'stats'     => [
                'pending'     => ['n' => $n('pending'), 'held' => showAmount($stats['pending']->total ?? 0)],
                'completed'   => ['n' => $n('completed'), 'total' => showAmount($stats['completed']->total ?? 0), 'units' => number_format((int) ($stats['completed']->units ?? 0)), 'rebate' => showAmount($stats['completed']->rebate ?? 0)],
                'closed'      => $n('declined') + $n('cancelled'),
            ],
            'statuses'  => collect(StockTransfer::STATUSES)->map(fn ($label, $key) => ['value' => $key, 'label' => $key === 'pending' ? 'Pending' : $label, 'count' => $n($key)])->values(),
            'filters'   => ['status' => $status, 'search' => $search, 'date_from' => $from, 'date_to' => $to],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** One transfer (Pages/Admin/Stockist/TransferDetail.tsx): both stockists, the stock, the money, the timeline. */
    public function show(int $id)
    {
        $transfer  = StockTransfer::with(['items.product', 'buyer.user', 'buyer.state', 'seller.user', 'seller.state', 'state', 'admin'])->findOrFail($id);
        $pageTitle = 'Stock Transfer ' . $transfer->code;
        $completed = $transfer->status === StockTransfer::STATUS_COMPLETED;

        // Every ledger row this transfer wrote, both wallets and the rebate
        $trx     = array_filter([$transfer->trx, $transfer->settle_trx]);
        $ledger  = Stransaction::whereIn('trx', $trx)->whereIn('remark', [
            StockTransferService::REMARK_PAYMENT, StockTransferService::REMARK_SALE, StockTransferService::REMARK_REFUND,
        ])->with('user:id,username')->orderBy('id')->get()->map(fn ($row) => [
            'wallet' => 'Stockist wallet', 'username' => $row->user?->username, 'details' => (string) $row->details,
            'plus' => $row->trx_type === '+', 'amount' => showAmount($row->amount), 'balance' => showAmount($row->post_balance),
        ]);
        $rebates = Transaction::whereIn('trx', $trx)->where('remark', 'stockist_rebate')->with('user:id,username')->orderBy('id')->get()->map(fn ($row) => [
            'wallet' => 'Stockist rebate', 'username' => $row->user?->username, 'details' => (string) $row->details,
            'plus' => true, 'amount' => showAmount($row->amount), 'balance' => showAmount($row->post_balance),
        ]);

        $party = fn ($s) => $s ? [
            'name'    => (string) $s->business_name,
            'owner'   => $s->user ? ['fullname' => (string) $s->user->fullname, 'username' => (string) $s->user->username, 'url' => route('admin.users.detail', $s->user->id)] : null,
            'phone'   => $s->business_phone ?: ($s->user?->mobile ?? '—'),
            'email'   => $s->business_email ?: ($s->user?->email ?? '—'),
            'state'   => ($s->state?->name ?? '—') . ' · ' . $s->store_type_name,
            'wallet'  => showAmount($s->wallet),
            'url'     => route('admin.stockist.details', $s->id),
        ] : null;
        $at = fn ($d) => $d ? showDateTime($d, 'd M Y, h:i:s A') : null;

        // Same rule the route enforces, so the button only shows to admins who can use it
        $canCancel = $transfer->isPending()
            && AdminAccessMiddleware::allows(auth('admin')->user(), 'admin.stockist.transfers.cancel', 'POST');

        return Inertia::render('Admin/Stockist/TransferDetail', [
            'transfer' => [
                'id'          => $transfer->id,
                'code'        => (string) $transfer->code,
                'status'      => $transfer->status,
                'badge'       => AdminPresenter::badge($transfer->statusLabel(), $transfer->adminBadge()),
                'state'       => $transfer->state?->name,
                'requested'   => $at($transfer->created_at),
                'closed'      => $at($transfer->closedAt()),
                'closedBy'    => match ($transfer->closed_by) { 'seller' => 'by the Mega stockist', 'buyer' => 'by the Mini stockist', 'admin' => 'by ' . ($transfer->admin?->name ?? 'admin'), default => '' },
                'amount'      => showAmount($transfer->total_amount),
                'units'       => number_format((int) $transfer->total_quantity),
                'rebate'      => $completed ? showAmount($transfer->rebate_amount) : null,
                'rebatePreview' => $transfer->isPending() ? showAmount($this->transfers->rebatePreview($transfer)) : null,
                'trx'         => (string) $transfer->trx,
                'settleTrx'   => (string) $transfer->settle_trx,
                'buyerAfter'  => showAmount($transfer->buyer_balance_after),
                'sellerAfter' => showAmount($transfer->seller_balance_after),
                'buyerNote'   => $transfer->buyer_note,
                'decisionNote' => $transfer->decision_note,
                'statusLabel' => $transfer->statusLabel(),
            ],
            'seller' => $party($transfer->seller),
            'buyer'  => $party($transfer->buyer),
            'items'  => $transfer->items->map(fn ($item) => [
                'id'          => $item->id,
                'name'        => (string) $item->product_name,
                'productId'   => $item->product_id,
                'quantity'    => number_format((int) $item->quantity),
                'unit'        => showAmount($item->unit_price),
                'line'        => showAmount($item->line_total),
                'rebate'      => $completed ? showAmount($item->rebate_amount) : null,
                'rebateRule'  => $completed && $item->rebate_percent !== null ? ($item->rebate_percent + 0) . '% of PV × ₦' . rDollar() : null,
                'seller'      => $item->seller_stock_before !== null ? [number_format($item->seller_stock_before), number_format($item->seller_stock_after)] : null,
                'buyer'       => $item->buyer_stock_before !== null ? [number_format($item->buyer_stock_before), number_format($item->buyer_stock_after)] : null,
            ])->values(),
            'ledger'    => $ledger->concat($rebates)->values(),
            'canCancel' => $canCancel,
            'urls'      => ['cancel' => route('admin.stockist.transfers.cancel', $transfer->id), 'index' => route('admin.stockist.transfers.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function cancel(Request $request, int $id)
    {
        $request->validate(['reason' => 'required|string|max:450']);
        $transfer = StockTransfer::findOrFail($id);

        try {
            $transfer = $this->transfers->adminCancel($transfer, auth('admin')->user(), $request->reason);
        } catch (StockTransferException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', "Request {$transfer->code} cancelled and " . showAmount($transfer->total_amount) . ' refunded to ' . $transfer->buyer->business_name . '.'];
        return back()->withNotify($notify);
    }
}
