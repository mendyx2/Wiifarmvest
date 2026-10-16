<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackAffiliateClick;
use App\Models\AffiliateOrder;
use App\Models\AffiliateSetting;
use App\Models\GatewayCurrency;
use App\Models\State;
use App\Services\AffiliateOrderService;
use App\Services\ShopCartService;
use Illuminate\Http\Request;

class ShopCheckoutController extends Controller
{
    public function __construct(
        private ShopCartService $cart,
        private AffiliateOrderService $orders,
    ) {
    }

    public function index()
    {
        $resolved = $this->cart->resolve();

        if (empty($resolved['lines'])) {
            return redirect()->route('shop.cart')->with('error', 'Your cart is empty.');
        }

        $pageTitle = 'Checkout';
        $states    = State::orderBy('name')->get();
        $settings  = AffiliateSetting::current();

        return view('Template::shop.checkout', compact('pageTitle', 'resolved', 'states', 'settings'));
    }

    public function store(Request $request)
    {
        $settings = AffiliateSetting::current();

        $paymentMethods = ['paystack'];
        if ($settings->cash_on_pickup_enabled) {
            $paymentMethods[] = 'cash_on_pickup';
        }

        $request->validate([
            'name'           => 'required|string|max:191',
            'email'          => 'required|email|max:191',
            'phone'          => 'nullable|string|max:30',
            'state_id'       => 'required|exists:states,id',
            'payment_method' => 'required|in:' . implode(',', $paymentMethods),
            'website'        => 'prohibited', // honeypot — real users never fill this hidden field
        ]);

        $resolved = $this->cart->resolve((int) $request->state_id);

        if (empty($resolved['lines'])) {
            return back()->withInput()->with('error', 'Your cart is empty or the selected products are no longer available.');
        }

        $attribution = TrackAffiliateClick::attribution($request);

        try {
            $order = $this->orders->createOrder(
                [
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'phone'    => $request->phone,
                    'state_id' => $request->state_id,
                ],
                $resolved,
                $request->payment_method,
                $attribution,
                (string) $request->ip(),
                (string) $request->userAgent()
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->cart->clear();

        if ($order->payment_method === AffiliateOrder::PAYMENT_CASH_ON_PICKUP) {
            return redirect()->route('shop.order.success', $order->order_code);
        }

        $account = $this->paystackAccount();

        if (!$account || empty($account->public_key)) {
            return redirect()->route('shop.checkout')->with('error', 'Online payment is temporarily unavailable. Please choose Cash on Pickup.');
        }

        $pageTitle = 'Complete Payment';
        $paystack  = [
            'key'      => $account->public_key,
            'email'    => $order->buyer_email,
            'amount'   => (int) round($order->total_amount * 100),
            'currency' => 'NGN',
            'ref'      => $order->order_code,
            'callback_url' => route('shop.paystack.callback'),
            'metadata' => [
                'payment_type' => 'affiliate_shop',
                'order_code'   => $order->order_code,
            ],
        ];

        return view('Template::shop.pay', compact('pageTitle', 'order', 'paystack'));
    }

    private function paystackAccount(): ?object
    {
        $gateway = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();

        return $gateway ? json_decode($gateway->gateway_parameter) : null;
    }
}
