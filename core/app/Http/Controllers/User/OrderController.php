<?php

namespace App\Http\Controllers\User;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WelcomePackage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Constants\Status;
use App\Support\ShopPresenter;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Order Details';
        $user      = auth()->user();
        $status    = in_array($request->status, ['pending', 'redeemed', 'cancelled'], true) ? $request->status : '';
        $sort      = in_array($request->sort, ['oldest', 'amount_high', 'amount_low'], true) ? $request->sort : 'newest';
        $search    = trim((string) $request->search);

        // Counts over all the member's orders (the old page only counted the 10 on screen).
        $all   = $user->orders()->with('invoice:id,order_id,redeemed_at')->get(['id', 'status']);
        $stats = ['total' => $all->count(), 'pending' => 0, 'redeemed' => 0, 'cancelled' => 0];
        foreach ($all as $order) {
            $stats[ShopPresenter::orderStatus($order)['key']]++;
        }

        // Filtering and sorting now happen here, across every page; before,
        // the browser only filtered the 10 orders it was showing.
        $orders = $user->orders()
            ->with(['items.product', 'invoice', 'state'])
            ->when($search !== '', fn ($q) => $q->where('invoice_code', 'like', '%' . $search . '%'))
            ->when($status === 'pending', fn ($q) => $q->where('status', Status::ORDER_PENDING)
                ->whereDoesntHave('invoice', fn ($i) => $i->whereNotNull('redeemed_at')))
            ->when($status === 'cancelled', fn ($q) => $q->where('status', Status::ORDER_CANCELED))
            ->when($status === 'redeemed', fn ($q) => $q->where('status', '!=', Status::ORDER_CANCELED)
                ->where(fn ($r) => $r->where('status', '!=', Status::ORDER_PENDING)
                    ->orWhereHas('invoice', fn ($i) => $i->whereNotNull('redeemed_at'))));

        $orders = match ($sort) {
            'oldest'      => $orders->oldest('id'),
            'amount_high' => $orders->orderByDesc(Invoice::select('total_amount')->whereColumn('invoices.order_id', 'orders.id')->limit(1)),
            'amount_low'  => $orders->orderBy(Invoice::select('total_amount')->whereColumn('invoices.order_id', 'orders.id')->limit(1)),
            default       => $orders->latest('id'),
        };
        $orders = $orders->paginate(10)->withQueryString();

        $welcomePackages = WelcomePackage::where('user_id', $user->id)->latest()->get();

        return Inertia::render('User/Shop/Orders', [
            'orders' => $orders->through(function ($order) {
                $status       = ShopPresenter::orderStatus($order);
                $awaitsRedeem = $status['key'] === 'pending' && $order->invoice;

                return [
                    'id'          => $order->id,
                    'code'        => $order->invoice_code,
                    'date'        => showDateTime($order->created_at),
                    'state'       => (string) @$order->state->name,
                    'amount'      => showAmount($order->invoice?->total_amount, 2),
                    'status'      => $status,
                    'invoiceCode' => $order->invoice?->invoice_code,
                    'canCopy'     => (bool) $awaitsRedeem,
                    'itemCount'   => $order->items->count(),
                    'items'       => $order->items->take(3)->map(fn ($item) => [
                        'name'     => \Illuminate\Support\Str::limit((string) @$item->product->name, 20),
                        'sku'      => @$item->product->sku,
                        'image'    => @$item->product->thumbnail ? ShopPresenter::image($item->product->thumbnail) : null,
                        'quantity' => (int) $item->quantity,
                        'price'    => showAmount($item->price, 2),
                    ])->values(),
                    'url' => route('user.orders.show', $order->id),
                ];
            }),
            'stats'           => $stats,
            'filters'         => ['status' => $status, 'sort' => $sort, 'search' => $search],
            'welcomePackages' => $welcomePackages->map(fn ($wp) => [
                'id'         => $wp->id,
                'title'      => ucfirst((string) $wp->source) . ' Welcome Package',
                'amount'     => showAmount($wp->amount),
                'code'       => $wp->code,
                'redeemed'   => $wp->isRedeemed(),
                'redeemedAt' => $wp->redeemed_at?->format('M d, Y'),
            ]),
            'productsUrl' => route('user.products'),
        ])->withViewData(compact('pageTitle'));
    }

    public function checkout()
    {
        $pageTitle = 'Checkout';
        $user      = auth()->user();
        $cart      = $user->cart;

        if (!$cart->id || $cart->items->isEmpty()) {
            $notify[] = ['error', 'Your cart is empty'];
            return to_route('user.cart.index')->withNotify($notify);
        }

        $cart->load('items.product', 'items.productstate.state');
        $total  = (float) $cart->items->sum(fn ($item) => $item->price * $item->quantity); // same sum as Cart::updateTotal()
        $wallet = (float) ($user->product_wallet ?? 0);

        return Inertia::render('User/Shop/Checkout', [
            'items' => $cart->items->map(fn ($item) => [
                'id'        => $item->id,
                'name'      => (string) @$item->product->name,
                'sku'       => @$item->product->sku,
                'image'     => @$item->product->thumbnail ? ShopPresenter::image($item->product->thumbnail) : null,
                'state'     => (string) @$item->productstate->state->name,
                'quantity'  => (int) $item->quantity,
                'price'     => showAmount($item->price, 2),
                'lineTotal' => showAmount($item->price * $item->quantity),
            ])->values(),
            'total'      => showAmount($total, 2),
            'wallet'     => showAmount($wallet, 2),
            'canPay'     => $wallet >= $total,
            'shortfall'  => $wallet < $total ? showAmount($total - $wallet) : null,
            'payUrl'     => route('user.process.payment'),
            'cartUrl'    => route('user.cart.index'),
        ])->withViewData(compact('pageTitle'));
    }

    public function processPayment(Request $request)
    {
        $authId = auth()->id();
        $trx    = getTrx(10);

        DB::beginTransaction();
        try {
            // Lock the user row for the duration of the transaction to prevent
            // concurrent requests from double-spending the same product_wallet balance.
            $user = User::lockForUpdate()->findOrFail($authId);

            // Read the cart only now, under the lock. It used to be read before
            // the lock, so a double-click's second request waited here and then
            // paid again for the cart the first request had already emptied.
            $cart = Cart::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$cart || $cart->items->isEmpty()) {
                DB::rollBack();
                $notify[] = ['error', 'Your cart is empty'];
                return to_route('user.cart.index')->withNotify($notify);
            }
            $cart->updateTotal();
            $cartTotal = $cart->total_amount;

            if ($user->product_wallet < $cartTotal) {
                DB::rollBack();
                $notify[] = ['error', 'Insufficient Re-purchase wallet balance'];
                return back()->withNotify($notify);
            }

            $stateId = $cart->state_the_id;

            $order          = new Order();
            $order->user_id = $user->id;
            $order->state_id = $stateId;
            $order->status  = 0;
            $order->save();

            foreach ($cart->items as $cartItem) {
                $orderItem             = new OrderItem();
                $orderItem->order_id   = $order->id;
                $orderItem->product_id = $cartItem->product_id;
                $orderItem->quantity   = $cartItem->quantity;
                $orderItem->price      = $cartItem->price;
                $orderItem->save();
            }

            $invoice               = new Invoice();
            $invoice->invoice_code = $order->invoice_code;
            $invoice->order_id     = $order->id;
            $invoice->state_id     = $stateId;
            $invoice->items        = json_encode($cart->items->map(fn($item) => [
                'product_id'       => $item->product->id,
                'product_name'     => $item->product->name,
                'quantity'         => $item->quantity,
                'product_state_id' => $item->product_state_id,
                'price'            => $item->price,
            ]));
            
            $invoice->total_amount = $cartTotal;
            $invoice->save();

            $user->productDeductWallet($cartTotal); 

            productPurchase($user, $trx, $cartTotal);

            $order->trx = $trx;
            $order->save();

            $cart->items()->delete();
            $cart->updateTotal();

            DB::commit();

            $notify[] = ['success', 'Payment successful. Your invoice code is ' . $invoice->invoice_code];
            return to_route('user.orders.show', $order->id)->withNotify($notify);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            $notify[] = ['error', 'Payment failed. Please try again.'];
            return back()->withNotify($notify);
        }
    }

    public function show(Order $order)
    {
        $pageTitle = 'Order Details';

        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $order->load(['items.product', 'invoice', 'state']);
        $invoice    = $order->invoice;
        $redeemedAt = $invoice?->redeemed_at;

        // Wallet payment happens in the same request that places the order, so
        // both steps share its time (the old page made up "+5 minutes").
        $timeline = [
            ['icon' => 'la-shopping-cart', 'text' => __('Order Placed'), 'date' => showDateTime($order->created_at)],
            ['icon' => 'la-credit-card', 'text' => __('Payment Confirmed'), 'date' => showDateTime($order->created_at)],
        ];
        if ($redeemedAt) {
            $timeline[] = ['icon' => 'la-box-open', 'text' => __('Products Redeemed'), 'date' => showDateTime($redeemedAt)];
        }

        return Inertia::render('User/Shop/OrderShow', [
            'order' => [
                'code'       => $order->invoice_code,
                'state'      => (string) @$order->state->name,
                'status'     => ShopPresenter::orderStatus($order),
                'date'       => showDateTime($order->created_at),
                'redeemedAt' => $redeemedAt ? showDateTime($redeemedAt) : null,
                // Orders paid from the cart keep their total on the invoice
                // (orders.total_amount stays empty, so the old page showed 0).
                'total'      => showAmount($invoice?->total_amount ?? $order->total_amount, 2),
                'items'      => $order->items->map(fn ($item) => [
                    'id'          => $item->id,
                    'name'        => (string) @$item->product->name,
                    'sku'         => @$item->product->sku,
                    'description' => \Illuminate\Support\Str::limit(strip_tags((string) @$item->product->description), 80),
                    'image'       => @$item->product->thumbnail ? ShopPresenter::image($item->product->thumbnail) : null,
                    'quantity'    => (int) $item->quantity,
                    'price'       => showAmount($item->price, 2),
                    'lineTotal'   => showAmount($item->price * $item->quantity, 2),
                ])->values(),
                'invoiceCode' => $invoice?->invoice_code,
            ],
            'timeline'    => $timeline,
            'ordersUrl'   => route('user.orders.index'),
            'productsUrl' => route('user.products'),
        ])->withViewData(compact('pageTitle'));
    }
}
