<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockTransferException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAccessMiddleware;
use App\Models\StockTransfer;
use App\Models\Stransaction;
use App\Models\Transaction;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

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

    public function index(Request $request)
    {
        $pageTitle = 'Stock Transfers';
        $status    = array_key_exists((string) $request->status, StockTransfer::STATUSES) ? $request->status : null;
        $search    = trim((string) $request->search);

        $transfers = StockTransfer::with(['buyer.user:id,firstname,lastname,username', 'seller.user:id,firstname,lastname,username', 'state:id,name'])
            ->withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(fn ($q) => $q->where('code', 'like', $like)
                    ->orWhereHas('buyer', fn ($s) => $s->where('business_name', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('username', 'like', $like)))
                    ->orWhereHas('seller', fn ($s) => $s->where('business_name', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('username', 'like', $like))));
            })
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(getPaginate())
            ->withQueryString();

        $stats = StockTransfer::selectRaw('status, COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(rebate_amount), 0) AS rebate, COALESCE(SUM(total_quantity), 0) AS units')
            ->groupBy('status')->get()->keyBy('status');

        return view('admin.stockist.transfers.index', compact('pageTitle', 'transfers', 'stats', 'status', 'search'));
    }

    public function show(int $id)
    {
        $transfer  = StockTransfer::with(['items.product', 'buyer.user', 'buyer.state', 'seller.user', 'seller.state', 'state', 'admin'])->findOrFail($id);
        $pageTitle = 'Stock Transfer ' . $transfer->code;

        // Every ledger row this transfer wrote, both wallets and the rebate
        $trx     = array_filter([$transfer->trx, $transfer->settle_trx]);
        $ledger  = Stransaction::whereIn('trx', $trx)->whereIn('remark', [
            StockTransferService::REMARK_PAYMENT, StockTransferService::REMARK_SALE, StockTransferService::REMARK_REFUND,
        ])->with('user:id,username')->orderBy('id')->get();
        $rebates = Transaction::whereIn('trx', $trx)->where('remark', 'stockist_rebate')->with('user:id,username')->orderBy('id')->get();

        $rebatePreview = $transfer->isPending() ? $this->transfers->rebatePreview($transfer) : null;

        // Same rule the route enforces, so the button only shows to admins who can use it
        $canCancel = $transfer->isPending()
            && AdminAccessMiddleware::allows(auth('admin')->user(), 'admin.stockist.transfers.cancel', 'POST');

        return view('admin.stockist.transfers.show', compact('pageTitle', 'transfer', 'ledger', 'rebates', 'rebatePreview', 'canCancel'));
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
