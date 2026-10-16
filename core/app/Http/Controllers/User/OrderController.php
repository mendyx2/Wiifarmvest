<?php

namespace App\Http\Controllers\User;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WelcomePackage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $pageTitle       = 'Order Details';
        $orders          = auth()->user()
            ->orders()
            ->with(['items.product', 'invoice'])
            ->latest()
            ->paginate(10);
        $welcomePackages = WelcomePackage::where('user_id', auth()->id())->latest()->get();

        return view('Template::user.orders.index', compact('orders', 'pageTitle', 'welcomePackages'));
    }

    public function checkout()
    {
        $pageTitle = 'Checkout';
        $user      = auth()->user();
        $cart      = $user->cart;

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('user.cart.index')->with('error', 'Your cart is empty');
        }

        $cart->load('items.product');

        return view('Template::user.orders.checkout', compact('cart', 'pageTitle'));
    }

    public function processPayment(Request $request)
    {
        $authId = auth()->id();
        $cart   = auth()->user()->cart;

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty']);
        }

        $cartTotal = $cart->total_amount;
        $trx       = getTrx(10);

        DB::beginTransaction();
        try {
            // Lock the user row for the duration of the transaction to prevent
            // concurrent requests from double-spending the same product_wallet balance.
            $user = User::lockForUpdate()->findOrFail($authId);

            if ($user->product_wallet < $cartTotal) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Insufficient Re-purchase wallet balance']);
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

            return response()->json([
                'success'      => true,
                'invoice_code' => $invoice->invoice_code,
                'redirect_url' => route('user.orders.show', $order->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
        }
    }

    public function show(Order $order)
    {
        $pageTitle = 'Order Details';

        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $order->load(['items.product', 'invoice', 'user']);

        return view('Template::user.orders.show', compact('order', 'pageTitle'));
    }
}
