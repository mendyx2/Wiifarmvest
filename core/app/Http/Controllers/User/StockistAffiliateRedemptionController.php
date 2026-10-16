<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AffiliateOrder;
use App\Services\AffiliateOrderService;
use Illuminate\Http\Request;

/**
 * Lets a Stockist redeem /shop guest orders in person — separate from
 * StockistController's internal-repurchase redemption flow (that one carries
 * unilevel/PV distribution against the buying member's own downline, which
 * doesn't apply here since /shop buyers are guests with no MLM tree).
 */
class StockistAffiliateRedemptionController extends Controller
{ 
    public function redeemForm()
    {
        $pageTitle = 'Redeem Affiliate Products';

        return view('Template::user.stockist.affiliate.redeem', compact('pageTitle'));
    }

    public function verify(Request $request)
    {
        $stockist = auth()->user()->stockist;

        if (!$stockist) {
            return response()->json(['success' => false, 'message' => 'Stockist profile not found'], 403);
        }

        $request->validate(['order_code' => 'required|string']);

        $order = AffiliateOrder::with('items.product:id,sku', 'state')
            ->where('order_code', trim($request->order_code))
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found']);
        }

        if (!$order->isReadyForPickup()) {
            return response()->json(['success' => false, 'message' => 'This order is not available for pickup (status: ' . $order->status . ').']);
        }

        if ((int) $order->state_id !== (int) $stockist->state_id) {
            return response()->json([
                'success' => false,
                'message' => 'This order is for a buyer in ' . ($order->state->name ?? 'another state') . '. You can only redeem orders for your own state.',
            ]);
        }

        return response()->json([
            'success' => true,
            'order'   => [
                'order_code'     => $order->order_code,
                'buyer_name'     => $order->buyer_name,
                'payment_method' => $order->payment_method,
                'total_amount'   => getAmount($order->total_amount),
                'total_display'  => showAmount($order->total_amount),
                'items'          => $order->items->map(fn ($i) => [
                    'product_name' => $i->product_name,
                    'sku'          => $i->product?->sku,
                    'quantity'     => $i->quantity,
                    'line_total'   => getAmount($i->line_total),
                    'line_display' => showAmount($i->line_total),
                ]),
            ],
        ]);
    }

    public function confirm(Request $request, AffiliateOrderService $orders)
    {
        $stockist = auth()->user()->stockist;

        if (!$stockist) {
            return response()->json(['success' => false, 'message' => 'Stockist profile not found'], 403);
        }

        $request->validate(['order_code' => 'required|string']);
        $order_code = trim($request->order_code);

        $order = AffiliateOrder::where('order_code', $order_code)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found']);
        }

        if ((int) $order->state_id !== (int) $stockist->state_id) {
            return response()->json(['success' => false, 'message' => 'This order is for a buyer in another state.']);
        }

        try { 
           
            $orders->confirmPickup($order, $stockist);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'message' => 'Order ' . $order->order_code . ' marked as picked up.']);
    }
}
