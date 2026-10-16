<?php
// app/Http/Controllers/Admin/StockistOrderController.php

namespace App\Http\Controllers\Admin;
 
use App\Http\Controllers\Controller;
use App\Models\StockistOrder;
use App\Models\Stockist;
use App\Models\User;
use App\Models\StockistOrderItem;
use App\Models\Stockist_store;
use App\Models\Transaction;
use App\Models\StockistInventory;
use App\Models\Stockist_store_history;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockistOrderController extends Controller
{
   
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->admin = auth('admin')->user();
    }

    public function index(Request $request)
    {
        $pageTitle = 'Stockist Order';
        $query = StockistOrder::with(['stockist.user', 'items.product']);
        //dd($query);
        // Filter by status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order number or stockist name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('stockist', function($q) use ($search) {
                      $q->where('business_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function($q) use ($search) {
                            $q->where('username', 'like', "%{$search}%");
                        });
                });
            });
        }

        $orders = $query->latest()->paginate(20);

        $stats = [
            'pending' => StockistOrder::where('status', 'pending')->count(),
            'approved' => StockistOrder::where('status', 'approved')->count(),
            'processing' => StockistOrder::where('status', 'processing')->count(),
            'shipped' => StockistOrder::where('status', 'shipped')->count(),
            'delivered' => StockistOrder::where('status', 'delivered')->count(),
            'total' => StockistOrder::count()
        ];
 
        return view('admin.stockistorders.index', compact('orders', 'stats', 'pageTitle'));
    }

    public function show(StockistOrder $order)
    {
        $pageTitle = '';
        //dd($order);
        $order = $order->load(['stockist.user', 'items.product']);
        //dd($order);
        
        return view('admin.stockistorders.show', compact('order','pageTitle'));
    }
    public function approveOrder(StockistOrder $order)
    {
        //dd($order);
        if (!$order->canBeApproved()) {
             $notify[] = ['error', 'Order cannot be approved in its current status.'];
             return back()->withNotify($notify);
           
        }

        DB::beginTransaction();

        try {

            $order->markAsApproved();
            DB::commit();
            $notify[] = ['success', 'Order approved successfully!'];

        } catch (\Exception $e) {
            DB::rollBack();
            //throw $e;
            $notify[] = ['error', 'Error code AD103'];
            $notify[] = ['error', 'Failed to approve order: ' . $e->getMessage()];
           
        }
        return back()->withNotify($notify);
        
    }
   public function processOrder(StockistOrder $order)
    {
        if ($order->status !== 'approved') {
             $notify[] = ['error', 'Order can only be processed from approved status.'];
             return back()->withNotify($notify);

        }

        DB::beginTransaction();

        try {

             $order->update([
                'status' => 'processing'
            ]);
            DB::commit();
            $notify[] = ['success', 'Order marked as processing!'];

        } catch (\Exception $e) {
            DB::rollBack();
            //throw $e;
            $notify[] = ['error', 'Error code AD103'];
            $notify[] = ['error', 'Failed to process order: ' . $e->getMessage()];
           
        }
        return back()->withNotify($notify);


    }
 
    

    public function shipOrder(StockistOrder $order)
    {
        if (!$order->canBeShipped()) {
            $notify[] = ['error', 'Order cannot be shipped in its current status.'];
             return back()->withNotify($notify);
        }
        DB::beginTransaction();

        try {

             $order->markAsShipped();
            DB::commit();
            $notify[] = ['success','Order marked as shipped!'];

        } catch (\Exception $e) {
            DB::rollBack();
            //throw $e;
            $notify[] = ['error', 'Error code AD103'];
            $notify[] = ['error', 'Failed to ship order: ' . $e->getMessage()];
           
        }
        return back()->withNotify($notify);
    }

    public function deliverOrder(StockistOrder $order)
    {   
        if (!$order->canBeDelivered()) {
            $notify[] = ['error', 'Order cannot be delivered in its current status.'];
             return back()->withNotify($notify);
        }

        DB::beginTransaction();
        try {
            // Mark order as delivered
            $order->markAsDelivered();

            // Update stockist stores inventory
            foreach ($order->items as $item) {
                $userD = $order->stockist->user;

                $this->updateStockistInventory($order->id, $userD, $item); 

            }
 
            DB::commit();
            $notify[] = ['success','Order delivered and inventory updated successfully!'];

        } catch (\Exception $e) {
            DB::rollBack();
            $notify[] = ['error', 'Failed to deliver order: '];
            $notify[] = ['error', $e->getMessage()];
        }
        return back()->withNotify($notify);

    }
    public function cancelOrder(Request $request, StockistOrder $order)
    {
        if (!$order->canBeCancelled()) {
            $notify[] = ['error', 'Order cannot be cancelled in its current status.'];
             return back()->withNotify($notify);
        }

        DB::beginTransaction();
        try {
            // Refund stockist's wallet
            $order->stockist->addToWallet($order->grand_total);

            //add stockist transaction here..

            // Update order status
            $order->update(['status' => 'cancelled']);

            DB::commit();
            $notify[] = ['success','Order cancelled and amount refunded!'];
            

        } catch (\Exception $e) {
            DB::rollBack();
            $notify[] = ['error', 'Failed to cancel order: '];
            $notify[] = ['error', $e->getMessage()];
            
        }
        return back()->withNotify($notify);
    }


    private function updateStockistInventory($orderId, User $user, StockistOrderItem $item)
    {  

        //$order->stockist->user_id
        $quantity = $item->quantity;
        
        $product_sku = (int)$item->product->sku;
        $productId = $item->product->id;
        $userId = $user->id;

        $stockistStore = Stockist_store::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        $stockist = Stockist::where('user_id', $userId)->first();
        $stockist_id = $stockist->id;
        $stockist_store_type = (int)$stockist->store_type; 
             
        //StockistInventory;
        if ($stockistStore) {
            // Update existing stock 
            $stockistStore->incrementStock($quantity);
            //$stockistStore->incrementStockInventory($quantity);
        } else {
            //get the user stockist ID
           
            //$stockist_id = getStockistId($userId);
            
            // Create new stock entry
            $stockistStore = new Stockist_store();
           
            $stockistStore->user_id = $userId;
            $stockistStore->stockist_id = $stockist_id;
            $stockistStore->product_id = $productId;
            $stockistStore->quantity = $quantity;
            $stockistStore->is_active = true;
            $stockistStore->status = 1;
            $stockistStore->save();
        }

        // record the update 

        //Store in history for proper tracking
        $stockist_store_history =  new Stockist_store_history();
        $stockist_store_history->user_id = $userId;
        $stockist_store_history->stockist_store_id = $stockistStore->id;
        $stockist_store_history->quantity = $quantity;
        $stockist_store_history->admin_id = $this->admin->id;
        $stockist_store_history->save();
        
        if($stockist_store_type == 1){
            if ($product_sku == 1) {
                // 40 naira per product
                $amount = $quantity * 40;
                if ($user->ref_by) {
    
                    $user_ref = User::findOrFail($user->ref_by);
                    $user_ref->balance +=  $amount;
                    $user_ref->save();
                    //dd($user_ref);
    
                    $transaction               = new Transaction();
                    $transaction->amount       = $amount;
                    $transaction->user_id      = $user_ref->id;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '+';
                    $transaction->details      = 'Stockist purchase bonus received from '.$user->username.' purchasing '.$quantity.' QTY of '.$item->product->name;
                    $transaction->remark       = 'Stockist_purchase_commission';
                    $transaction->trx          = getTrx();
                    $transaction->post_balance = $user_ref->balance;
                    $transaction->save();
    
    
                }
    
            }
        } //end $stockist->store_type == 1
        //dd($item->product->name);

    }

    


}