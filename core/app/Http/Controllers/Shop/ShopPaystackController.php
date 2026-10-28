<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\AffiliateOrder;
use App\Models\GatewayCurrency;
use App\Services\AffiliateOrderService;
use Illuminate\Http\Request;

class ShopPaystackController extends Controller
{
    public function __construct(private AffiliateOrderService $orders)
    {
    }

   
    public function callback(Request $request)
    {
        $reference = $request->query('trxref') ?? $request->query('reference');
        $order     = $reference ? AffiliateOrder::where('order_code', $reference)->first() : null;

        if (!$order) {
            return to_route('shop.index')->withNotify([['error', 'We could not find that order.']]);
        }

        if ($order->status !== AffiliateOrder::STATUS_PENDING) {
            return redirect()->route('shop.order.success', $order->order_code);
        }

        $gateway = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();
        $account = $gateway ? json_decode($gateway->gateway_parameter) : null;

        if (!$account || empty($account->secret_key)) {
            return to_route('shop.order.success', $order->order_code)->withNotify([['error', 'Payment verification is temporarily unavailable.']]);
        }

        $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($order->order_code));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $account->secret_key]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $data = $result['data'] ?? null;

        if ($data && $data['status'] === 'success'
            && round($data['amount'] / 100, 2) >= round($order->total_amount, 2)
            && $data['currency'] === 'NGN'
        ) {
            $this->orders->markPaystackPaid($order, $order->order_code);
            return redirect()->route('shop.order.success', $order->order_code);
        }

        // Back to the order's payment page (the checkout is empty by now: the cart was
        // turned into this order), so the buyer can try again.
        return to_route('shop.order.pay', $order->order_code)
            ->withNotify([['error', 'We could not verify your payment. If you were charged, contact support with your reference: ' . $order->order_code]]);
    }
}
