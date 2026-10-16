<?php

namespace App\Http\Controllers\User;

use App\Exceptions\StockTransferException;
use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Stockist;
use App\Models\Stockist_store;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

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

        return view('Template::user.stockist.transfers.index', compact('pageTitle', 'stockist', 'isMega', 'transfers', 'counts', 'status', 'companyOrders'));
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

        return view('Template::user.stockist.transfers.create', compact('pageTitle', 'stockist', 'sellers', 'seller', 'catalogue'));
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
            return back()->withNotify($notify)->withInput();
        }

        try {
            $transfer = $this->transfers->request($buyer, $seller, $request->quantities, $request->note);
        } catch (StockTransferException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify)->withInput();
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

        return view('Template::user.stockist.transfers.show', compact('pageTitle', 'stockist', 'transfer', 'isSeller', 'onHand', 'rebate'));
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
