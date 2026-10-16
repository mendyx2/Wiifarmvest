<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Constants\Status;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index(?int $userId = null)
    {
        $pageTitle = 'Orders';
        $orders    = Order::searchable(['invoice_code', 'user:username']);
        if ($userId) {
            $orders = $orders->where('user_id', $userId);
        }
        $orders = $orders->with(['user', 'items.product'])->orderBy('id', 'desc')->paginate(getPaginate());

        $emptyMessage = 'Order not found';
        return view('admin.orders', compact('pageTitle', 'orders', 'emptyMessage'));
    }

    public function status(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:1,2',
        ]);

        $order = Order::where('status', Status::ORDER_PENDING)
            ->with(['items.product', 'user'])
            ->findOrFail($id);

        $user        = $order->user;
        $totalAmount = $order->total_amount;
        $itemCount   = $order->items->count();

        if ($request->status == Status::ORDER_SHIPPED) {
            $order->status = Status::ORDER_SHIPPED;

            notify($user, 'ORDER_SHIPPED', [
                'invoice_code' => $order->invoice_code,
                'item_count'   => $itemCount,
                'total_price'  => showAmount($totalAmount, currencyFormat: false),
                'trx'          => $order->trx,
            ]);
        } else {
            $order->status          = Status::ORDER_CANCELED;
            $user->product_wallet  += $totalAmount;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $order->user_id;
            $transaction->amount       = $totalAmount;
            $transaction->post_balance = $user->product_wallet;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = "Order {$order->invoice_code} cancelled — {$itemCount} item(s) refunded";
            $transaction->remark       = 'order_cancel_refund';
            $transaction->trx          = $order->trx;
            $transaction->save();

            foreach ($order->items as $item) {
                $item->product->increment('quantity', $item->quantity);
            }

            notify($user, 'ORDER_CANCELED', [
                'invoice_code' => $order->invoice_code,
                'item_count'   => $itemCount,
                'total_price'  => showAmount($totalAmount, currencyFormat: false),
                'trx'          => $order->trx,
            ]);
        }

        $order->save();

        $notify[] = ['success', 'Order status updated successfully'];
        return back()->withNotify($notify);
    }
}
