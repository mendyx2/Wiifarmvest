<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Constants\Status;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $result = DB::transaction(function () use ($request, $id) {
            // Locked so two clicks can't both refund the same order.
            $order = Order::where('status', Status::ORDER_PENDING)
                ->with(['items.product', 'user', 'invoice.redemptions'])
                ->lockForUpdate()
                ->findOrFail($id);

            $user = $order->user;
            // Cart orders keep their total on the invoice; orders.total_amount
            // stays empty for them, so the old code refunded ₦0.
            $totalAmount = (float) ($order->invoice?->total_amount ?? $order->total_amount);
            $itemCount   = $order->items->count();

            if ($request->status == Status::ORDER_SHIPPED) {
                $order->status = Status::ORDER_SHIPPED;
                $order->save();

                return ['ORDER_SHIPPED', $order, $user, $itemCount, $totalAmount];
            }

            // Goods already handed over by a stockist can't be refunded.
            if ($order->invoice && $order->invoice->redemptions->isNotEmpty()) {
                return 'This order has already been (partly) collected from a stockist and cannot be cancelled.';
            }

            $order->status = Status::ORDER_CANCELED;
            $order->save();

            $user->product_wallet += $totalAmount;
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

            // No stock is put back: checkout never takes company stock (stock
            // leaves a stockist's store only when the invoice is redeemed).

            return ['ORDER_CANCELED', $order, $user, $itemCount, $totalAmount];
        });

        if (is_string($result)) {
            $notify[] = ['error', $result];
            return back()->withNotify($notify);
        }

        [$template, $order, $user, $itemCount, $totalAmount] = $result;
        notify($user, $template, [
            'invoice_code' => $order->invoice_code,
            'item_count'   => $itemCount,
            'total_price'  => showAmount($totalAmount, currencyFormat: false),
            'trx'          => $order->trx,
        ]);

        $notify[] = ['success', 'Order status updated successfully'];
        return back()->withNotify($notify);
    }
}
