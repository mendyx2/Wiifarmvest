<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Pin;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EpinController extends Controller
{
    public function epin()
    { 
        $general = gs();
         //dd($general);
        if(auth()->id() == 2)
            abort(404);
        if($general->epin_status != Status::ENABLE){
           // abort(404);
        }
        $pageTitle = "Recharge Your Wallet";
        $pins = Pin::where('generate_user_id', auth()->id())->orderBy('status')->searchable(['pin'])->filter(['status'])->latest()->paginate(getPaginate(30));
        $Totalpins = Pin::where('generate_user_id', auth()->id())->count();
          //dd($Totalpins);
        return view('Template::user.epin_recharge', compact('pageTitle', 'pins', 'Totalpins'));
    }

    
     public function eRecharge(Request $request)
    {
        $general = gs();
        if($general->epin_status != Status::ENABLE){
            abort(404);
        }
         
        $request->validate([
            'pin' => 'required|exists:pins,pin'
        ]);
         
        $user = auth()->user();
        $pin = Pin::where('pin', $request->pin)->where('status', Status::NO)->first();
        if(!$pin)
        {
            $notify[] = ['error', 'Already used this pin.'];
            return back()->withNotify($notify);
        }
        $userPin = Pin::where('pin', $request->pin)->where('status', Status::NO)->where('generate_user_id', $user->id)->first();

        if($userPin)
        {
            $notify[] = ['error', 'You can not e-pin recharge to self account.'];
            return back()->withNotify($notify);
        }
        
        
        $pin->status = Status::YES;
        $pin->user_id = $user->id;
        $pin->save();
      
        $user->visa += $pin->amount;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $pin->amount;
        $transaction->post_balance = $user->balance;
        $transaction->trx_type = '+';
        $transaction->remark = 'epin';
        $transaction->details = 'E-Pin recharge via ' . $pin->pin;
        $transaction->trx = getTrx();
        $transaction->save();

        $deposit = new Deposit();
        $deposit->user_id = $user->id;
        $deposit->method_code = 0;
        $deposit->method_currency = $general->cur_text;
        $deposit->amount = $pin->amount;
        $deposit->rate = 1;
        $deposit->final_amount = $pin->amount;
        $deposit->btc_amount = 0;
        $deposit->btc_wallet = "";
        $deposit->trx = $transaction->trx;
        $deposit->status = Status::PAYMENT_SUCCESS;
        $deposit->save();

        notify($user, 'PIN_RECHARGE', [
            'trx' => $transaction->trx,
            'pin_number' => $pin->pin,
            'amount' => showAmount($pin->amount,currencyFormat:false),
            'post_balance' => showAmount($user->balance,currencyFormat:false),
        ]);

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Deposit successful via e-pin';
        $adminNotification->click_url = urlPath('admin.deposit.successful');
        $adminNotification->save();

        $notify[] = ['success', 'Balance has been added to your account'];
        return back()->withNotify($notify);
    }

    public function epinRechargeLog()
    {
        $general = gs();
        if($general->epin_status != Status::ENABLE){
            abort(404);
        }
        $pageTitle = 'Recharge History'; 
        $transactions = Transaction::where('user_id', auth()->id())->where('remark', 'epin')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::user.transactions', compact('pageTitle', 'transactions'));
    }




    public function pinGenerate(Request $request)
    {
        $general = gs();
        if($general->epin_status != Status::ENABLE){
           // abort(404);
        }

        $request->validate([
            'amount' => 'required|numeric|gt:0'
        ]);

        $general = gs();
        $charge = 0;
        if($general->epin_status == 1){
            $charge = (($request->amount / 100) * $general->epin_charge);
        }
        
        $user = auth()->user();
        
        if( ($request->amount + $charge) > $user->visa){
            $notify[] = ['error', 'You have insufficient money in VISA Wwallet'];
            return back()->withNotify($notify);
        }


        DB::beginTransaction();

        try {

            $user->visa -= ($request->amount + $charge);
            $user->save();

            $pin = new Pin();

            $pin->generate_user_id = $user->id;
            // if this user stats is 1. it should include 1 in the pin->stats so
            if($user->stats == 1){
                $pin->stats = 1;

            } 
            $pin->user_id = 0;
            $pin->status = 0;
            $pin->amount = $request->amount;
            $pin->pin = rand(10000000,99999999).'-'.rand(10000000,99999999).'-'.rand(10000000,99999999).'-'.rand(10000000,99999999);
            $pin->details = "Created via " .$user->username;

            $pin->save();

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->amount = $pin->amount;
            $transaction->post_balance = $user->visa;
            $transaction->charge = $charge;
            $transaction->trx_type = '-';
            $transaction->details = 'Created E-pin';
            $transaction->remark = 'epin';
            $transaction->trx = getTrx();
            $transaction->save();

            //Notification

            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'E-pin Generated successful';
            $adminNotification->click_url = urlPath('admin.withdraw.data.approved');
            $adminNotification->save();

            $notify[] = ['success', 'The pin has been created'];
         DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();
            $notify[] = ['error', 'Error creating pin'];
            //throw $e;
        }
        return back()->withNotify($notify);
    }
   


}
