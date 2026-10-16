<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\Supply;
use App\Constants\Status;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SupplyController extends Controller
{
    public function index($userId = null)
    {   /*trx: transaction recipt deceipt develop from this section a
            dded to Sorder table and transaction(bonues type: 4). for tracking purpose
        */
        
        $pageTitle = 'Supplies';
        $supplies    = Supply::searchable(['trx', 'user:username', 'product:name']);
        if ($userId) {
            $supplies = $supplies->where('user_id', $userId);
        }
        $supplies = $supplies->with('product', 'user')->orderBy('id', 'desc')->paginate(getPaginate());

        $emptyMessage = 'supplies not found';
        return view('admin.stockist.supply', compact('pageTitle', 'supplies', 'emptyMessage'));
    }

    public function status(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:1,2'
        ]);

        $order   = Order::where('status', Status::ORDER_PENDING)->findOrFail($id);
        $product = $order->product;
        $user    = $order->user;

        if ($request->status == Status::ORDER_SHIPPED) {
            $order->status = Status::ORDER_SHIPPED;
            $details       = $product->name . ' product purchase';
            updateBV($user->id, $product->bv, $details);
            $template = 'ORDER_SHIPPED';
        } else {
            
            $order->status  = Status::ORDER_CANCELED;
            $user->balance += $order->total_price;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $order->user_id;
            $transaction->amount       = $order->total_price;
            $transaction->post_balance = $user->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $product->name . ' Order cancel';
            $transaction->trx          = $order->trx;
            $transaction->save();

            $product->quantity += $order->quantity;
            $product->save();

            $template = 'ORDER_CANCELED';
        }

        $order->save();

        notify($user, $template, [
            'product_name' => $product->name,
            'quantity'     => $request->quantity,
            'price'        => showAmount($product->price, currencyFormat: false),
            'total_price'  => showAmount($order->total_price, currencyFormat: false),
            'trx'          => $order->trx,
        ]);

        $notify[] = ['success', 'Product status updated successfully'];
        return back()->withNotify($notify);
    }
}
