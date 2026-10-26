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
use Inertia\Inertia;

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
            return to_route('shop.cart')->withNotify([['error', 'Your cart is empty.']]);
        }

        return Inertia::render('Shop/Checkout', [
            'lines' => collect($resolved['lines'])->map(fn (array $line) => [
                'id'        => $line['product']->id,
                'name'      => $line['product']->name,
                'sku'       => $line['product']->sku,
                'quantity'  => $line['quantity'],
                'lineTotal' => showAmount($line['line_total']),
            ])->values(),
            'subtotal'     => showAmount($resolved['subtotal']),
            'currency'     => (string) gs('cur_text'),
            'states'       => State::orderBy('name')->get(['id', 'name']),
            'cashOnPickup' => (bool) AffiliateSetting::current()->cash_on_pickup_enabled,
            'urls'         => [
                'cart'   => route('shop.cart'),
                'submit' => route('shop.checkout.store'),
            ],
        ])->withViewData(['pageTitle' => 'Checkout']);
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

        // Checked before the order exists. It used to be checked after: the
        // order was saved, its stock held and the cart emptied, and only then
        // was the buyer told online payment was off, with nothing left to pay for.
        if ($request->payment_method === AffiliateOrder::PAYMENT_PAYSTACK && !$this->paystackKey()) {
            return back()->withNotify([['error', 'Online payment is temporarily unavailable. Please choose Cash on Pickup.']]);
        }

        $resolved = $this->cart->resolve((int) $request->state_id);

        if (empty($resolved['lines'])) {
            return to_route('shop.cart')->withNotify([['error', 'Your cart is empty or the selected products are no longer available.']]);
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
            return back()->withNotify([['error', $e->getMessage()]]);
        }

        $this->cart->clear();

        if ($order->payment_method === AffiliateOrder::PAYMENT_CASH_ON_PICKUP) {
            return to_route('shop.order.success', $order->order_code);
        }

        // Its own GET page: the payment page used to be the answer to this
        // POST, so refreshing it sent the form again and placed a second order.
        return to_route('shop.order.pay', $order->order_code);
    }

    /** Paystack pop-up for an order that is still waiting for its online payment. */
    public function pay(string $orderCode)
    {
        $order = AffiliateOrder::where('order_code', $orderCode)->firstOrFail();

        if ($order->status !== AffiliateOrder::STATUS_PENDING || $order->payment_method !== AffiliateOrder::PAYMENT_PAYSTACK) {
            return to_route('shop.order.success', $order->order_code);
        }

        $key = $this->paystackKey();
        if (!$key) {
            return to_route('shop.order.success', $order->order_code)
                ->withNotify([['error', 'Online payment is temporarily unavailable. Please try again later.']]);
        }

        return Inertia::render('Shop/Pay', [
            'order'    => ['code' => $order->order_code, 'total' => getAmount($order->total_amount)],
            'paystack' => [
                'key'      => $key,
                'email'    => $order->buyer_email,
                'amount'   => (int) round($order->total_amount * 100),
                'currency' => 'NGN',
                'ref'      => $order->order_code,
                'metadata' => [
                    'payment_type' => 'affiliate_shop',
                    'order_code'   => $order->order_code,
                ],
            ],
            'urls' => [
                'callback' => route('shop.paystack.callback'),
                'script'   => 'https://js.paystack.co/v1/inline.js',
            ],
        ])->withViewData(['pageTitle' => 'Complete Payment']);
    }

    private function paystackKey(): ?string
    {
        $gateway = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();
        $account = $gateway ? json_decode($gateway->gateway_parameter) : null;

        return !empty($account->public_key) ? (string) $account->public_key : null;
    }
}
