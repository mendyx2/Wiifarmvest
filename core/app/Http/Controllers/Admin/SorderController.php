<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Sorder;
use App\Models\Stockist;
use App\Constants\Status;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Stockist_store;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Stockist_store_history;

class SorderController extends Controller
{
    public function index($userId = null)
    {
        $pageTitle = 'Stockist Orders ';
        $sorders    = Sorder::searchable(['trx', 'user:username', 'product:name']);
        if ($userId) {
            $sorders = $sorders->where('user_id', $userId);
        }
        $sorders = $sorders->with('product', 'user')->orderBy('id', 'desc')->paginate(getPaginate());
 
        $emptyMessage = 'Stockist Order not found';
        return view('admin.stockist.sorder', compact('pageTitle', 'sorders', 'emptyMessage'));
    }

    public function status(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:1,2,3'
        ]);
 
        //dd($id);
        // Checked 
        if($request->status == 1){
            //dd($request);           
            $sorder   = Sorder::where('status', Status::ORDER_PENDING)->with('product')->find($id);
            //dd($sorder);
            if (!$sorder) {
                $sorder1   = Sorder::where('status', 1)->find($id);

                if ($sorder1) {
                    $notify[] = ['error', 'Order has been confirmed already'];
                    return back()->withNotify($notify);
                }

                $notify[] = ['error', 'Operation Couldn\'t be completed Error: AD110'];
                return back()->withNotify($notify);
            }

            if ($sorder) {
                //dd($sorder);

                // Get the Stockist details
                $stockist = Stockist::where('user_id', $sorder->user_id)->first();
                //Ensure Stockist  available

                if (!$stockist) {
                    $notify[] = ['error', 'Stockist Details not found Error: AD111'];
                    return back()->withNotify($notify);
                }

                if ($stockist) {

                    $total_cost = (int)($sorder->product->price * $sorder->quantity);
                    $wallet = (int)$stockist->wallet;

                    if ($wallet < $total_cost ) {
                        $notify[] = ['error', 'Not Enough money in the stockist wallet '];
                        return back()->withNotify($notify);
                       // return to_route('admin.stockist.detail', $stockist->id)->withNotify($notify);
                    }
                    //dd($wallet);

                    if($wallet >= $total_cost  ){

                        $this->processPendingStockistRequest($stockist, $sorder, $total_cost);
                        $notify[] = ['success', 'Order process successfully'];
                        return back()->withNotify($notify);
                    }
                    //dd($total_cost);
                    //dd((int)$stockist->wallet);
                }

            }
         
        }elseif($request->status == 3){

            $sorder   = Sorder::where('status', Status::ORDER_CONFIRM)->find($id);

            // Ensure the admin confirmed this order
            if (!$sorder) {
                $notify[] = ['error', 'Stockist Order has to be confirmed First'];
                return back()->withNotify($notify);
            }

            if ($sorder) {
                // Get the Stockist details
                $stockist = Stockist::where('user_id', $sorder->user_id)->first();
                //Ensure Stockist  available
                if(!$stockist) {
                    $notify[] = ['error', 'Stockist Details not found'];
                    return back()->withNotify($notify);
                }
                if($stockist) {
                    $this->deliveredStockistGoods($sorder);  
                    $notify[] = ['success', 'Product delivered successfully'];
                    return back()->withNotify($notify);   
                }
            }
        }

        $notify[] = ['error', 'Something went wrong CODE AD102' ];
        return back()->withNotify($notify);
       
    } 

    protected function deliveredStockistGoods(Sorder $sorder)
    {        
        $stockist_store =Stockist_store::find($sorder->stockist_store_id);
        if (!$stockist_store) {
            $notify[] = ['error', 'Stockist Store does not exist  
                Code: AD101'];
            return back()->withNotify($notify);
        }

        DB::beginTransaction();
        try {

            $sorder->status = Status::ORDER_DELIVERED;
            $sorder->save();
            
 
            //Store in history for proper tracking
            $stockist_store_history =  new Stockist_store_history();
            $stockist_store_history->sorder_id = $sorder->id;
            $stockist_store_history->stockist_store_id = $stockist_store->id;
            $stockist_store_history->quantity_prev = $stockist_store->quantity;
            $stockist_store_history->save();


            $stockist_store->quantity += $sorder->quantity;
            $stockist_store->save();

            // process the stockist commission
            $this->stockistCommision($sorder);
            

            // End here
            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
            //return back()->withNotify($e);
        }

    }
    // Process commission award to upline
    protected function stockistCommision(Sorder $sorder){
        $user = User::findOrFail($sorder->user_id);
        $product = Product::find($sorder->product_id);
       
        /* 

        Get first upline
        check if the referral is a user (in future check if the referral is a user and is active) also check if the commission given to upline is greater than 0
        
        */
        $user_1gen = User::findOrFail($user->ref_by);

        // the money to be distributed
        $stockist_purchase = $product->stockist_purchase;

        if ($user_1gen && $stockist_purchase > 0) {
           
            $details = 
            '1st Gen Bonus received from stockist purchase '.$user_1gen->username;
            $remark ='stockist_purchase_commission';

            //function to process it
            stockistPurchase_commission($user_1gen, $details, $stockist_purchase, 40, $sorder->trx, $remark);

            //2nd Generation Process
            $user_2gen = User::find($user_1gen->ref_by);

            if ($user_2gen) {
                $details2 = 
                '2nd Gen Bonus received from stockist purchase from '.$user_1gen->username;
                

                //function to process it
                stockistPurchase_commission($user_2gen, $details2, $stockist_purchase, 26.7, $sorder->trx, $remark);

                //3rd Generation Process
                $user_3gen = User::find($user_2gen->ref_by);
                if ($user_3gen) {
                    $details3 ='3rd Gen Bonus received from stockist purchase from '.$user_1gen->username;
                

                    //function to process it
                    stockistPurchase_commission($user_3gen, $details3, $stockist_purchase, 20, $sorder->trx, $remark);

                    //4th Generation Process
                    $user_4gen = User::find($user_3gen->ref_by);
                    if ($user_4gen) {
                        $details4 ='4th Gen Bonus received from stockist purchase from '.$user_1gen->username;
                        //function to process it
                        stockistPurchase_commission($user_4gen, $details4, $stockist_purchase, 13.3, $sorder->trx, $remark);       
                    }  
                }

            }
        }
    }

    protected function processPendingStockistRequest(Stockist $stockist, $sorder, $total_cost)
    {   
        //dd($stockist);

        DB::beginTransaction();
        try{
            $trx     = getTrx();            
            //take the money form the stockist wallet
            $stockist->wallet -= $total_cost;
            $stockist->save(); // Save to tstore

            // Update the Stockist Order
            $sorder = Sorder::find($sorder->id);
            $sorder->trx = $trx;
            $sorder->price = $total_cost;
            $sorder->status = 1; //Status::ORDER_CONFIRM;
            $sorder->save();
            
            // Record it in transaction
            
            $details = "Stockist  product request confirmed";
            $user_id = $sorder->user_id;
            $post_balance = $stockist->wallet;
            $bonus_type = 5; //5 marked Stockist update. 
            $amount  = $total_cost;
            $remark = 'STOCKIST_ORDER_PURCHASE';


            stockistTransaction($user_id, $details, $amount, $remark, $trx, $bonus_type, $post_balance);

            
            // End here
            DB::commit();
            

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
            //return back()->withNotify($e);
        }

    }


       
}
