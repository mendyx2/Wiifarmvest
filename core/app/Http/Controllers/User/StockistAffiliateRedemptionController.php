<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AffiliateOrder;
use App\Services\AffiliateOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Lets a Stockist redeem /shop guest orders in person — separate from
 * StockistController's internal-repurchase redemption flow (that one carries
 * unilevel/PV distribution against the buying member's own downline, which
 * doesn't apply here since /shop buyers are guests with no MLM tree).
 */
class StockistAffiliateRedemptionController extends Controller
{ 
    public function redeemForm(Request $request)
    {
        $pageTitle = 'Redeem Affiliate Products';
        $stockist  = auth()->user()->stockist;
        $code      = strtoupper(trim((string) $request->query('code', '')));
        $order     = null;
        $error     = null;

        // "Verify" reloads this page with ?code=AF-XXXXXXXX.
        if ($code !== '') {
            $found = $this->lookup($stockist, $code);
            if (is_string($found)) {
                $error = $found;
            } else {
                $order = [
                    'code'    => $found->order_code,
                    'buyer'   => (string) $found->buyer_name,
                    'payment' => $found->payment_method === 'paystack' ? 'Paid Online' : 'Cash on Pickup',
                    'total'   => showAmount($found->total_amount),
                    'items'   => $found->items->map(fn ($i) => [
                        'name'     => (string) $i->product_name,
                        'sku'      => $i->product?->sku,
                        'quantity' => (int) $i->quantity,
                        'total'    => showAmount($i->line_total),
                    ])->values(),
                ];
            }
        }

        return Inertia::render('User/Stockist/AffiliateRedeem', [
            'code'       => $code,
            'order'      => $order,
            'error'      => $error,
            'redeemUrl'  => route('user.stockist.affiliate.redeem'),
            'confirmUrl' => route('user.stockist.affiliate.confirm'),
        ])->withViewData(compact('pageTitle'));
    }

    public function confirm(Request $request, AffiliateOrderService $orders)
    {
        $request->validate(['order_code' => 'required|string']);
        $stockist = auth()->user()->stockist;
        $code     = strtoupper(trim($request->order_code));

        $order = $this->lookup($stockist, $code);
        if (is_string($order)) {
            return to_route('user.stockist.affiliate.redeem', ['code' => $code])->withErrors(['order' => $order]);
        }

        try {
            $orders->confirmPickup($order, $stockist);
        } catch (\RuntimeException $e) {
            return to_route('user.stockist.affiliate.redeem', ['code' => $code])->withErrors(['order' => $e->getMessage()]);
        }

        $notify[] = ['success', 'Order ' . $order->order_code . ' marked as picked up.'];
        return to_route('user.stockist.affiliate.redeem')->withNotify($notify);
    }

    /** The order this stockist may hand over, or why not. */
    private function lookup($stockist, string $code)
    {
        $order = AffiliateOrder::with('items.product:id,sku', 'state')->where('order_code', $code)->first();

        if (!$order) {
            return 'Order not found';
        }
        if (!$order->isReadyForPickup()) {
            return 'This order is not available for pickup (status: ' . $order->status . ').';
        }
        if ((int) $order->state_id !== (int) $stockist->state_id) {
            return 'This order is for a buyer in ' . ($order->state->name ?? 'another state') . '. You can only redeem orders for your own state.';
        }

        return $order;
    }
}
