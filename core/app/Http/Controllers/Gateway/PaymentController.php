<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class PaymentController extends Controller
{
    public function deposit()
    {
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();
        $pageTitle = 'Deposit Methods';
        return view('Template::user.payment.deposit', compact('gatewayCurrency', 'pageTitle'));
    }

    

    public function depositInsert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
            'currency' => 'required',
        ]);
        //dd($request);

        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
            $notify[] = ['error', 'Please follow deposit limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($request->amount * $gate->percent_charge / 100);
        $payable = $request->amount + $charge;
        $finalAmount = $payable * $gate->rate;

        $data = new Deposit();
        $data->user_id = $user->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $request->amount;
        $data->charge = $charge;
        $data->rate = $gate->rate;
        $data->final_amount = $finalAmount;
        $data->btc_amount = 0;
        $data->btc_wallet = "";
        $data->trx = getTrx();
        $data->success_url = urlPath('user.deposit.history');
        $data->failed_url = urlPath('user.deposit.history');
        $data->save();
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm');
    }

    public function depositInsert1(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
            'currency' => 'required',
        ]);


        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
            $notify[] = ['error', 'Please follow deposit limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($request->amount * $gate->percent_charge / 100);
        $payable = $request->amount + $charge;
        $finalAmount = $payable * $gate->rate;

        $data = new Deposit();
        $data->user_id = $user->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $request->amount;
        $data->charge = $charge;
        $data->rate = $gate->rate;
        $data->final_amount = $finalAmount;
        $data->btc_amount = 0;
        $data->btc_wallet = "";
        $data->trx = getTrx();
        $data->success_url = urlPath('user.deposit.history');
        $data->failed_url = urlPath('user.deposit.history');
        $data->save();
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm1');
    }

    public function depositConfirm()
    {
        $track = session()->get('Track');
        //dd($track);
        $deposit = Deposit::where('trx', $track)->where('status',Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();
        
        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm');
        }


        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);


        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return back()->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if(@$data->session){
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        return view("Template::$data->view", compact('data', 'pageTitle', 'deposit'));
    }

    public function depositConfirm1()
    {
        $track = session()->get('Track');
        //dd($track);
        $deposit = Deposit::where('trx', $track)->where('status',Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();
        
        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm1');
        }


        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);


        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return back()->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if(@$data->session){
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        return view("Template::$data->view", compact('data', 'pageTitle', 'deposit'));
    }

    public static function userDataUpdate1($user, $reg_fee, $isManual=null)
    {
        if ($user->status == Status::ACTIVE) {
            $methodName = "Paystack";
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->amount = $reg_fee;
            $transaction->post_balance = $reg_fee;
            $transaction->charge = 0;
            $transaction->trx_type = '+';
            $transaction->details = 'Registration Via ' . $methodName;
            $transaction->trx = $user->trx;
            $transaction->remark = 'Sign up fee';
            $transaction->save();
            
            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'Sign Up successful via '.$methodName;
            $adminNotification->click_url = urlPath('admin.users.active');
            $adminNotification->save();
           

            notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
                'method_name' => $methodName,
                'method_currency' => 'NGN',
                'method_amount' => showAmount($reg_fee,currencyFormat:false),
                'amount' => showAmount($reg_fee,currencyFormat:false),
                'charge' => showAmount(0,currencyFormat:false),
                'rate' => showAmount(0,currencyFormat:false),
                'trx' => $user->trx,
                'post_balance' => showAmount($user->balance,currencyFormat:false)
            ]);
        }
    }
    /**
     * Credit a successful deposit — exactly once.
     *
     * Every gateway, the Paystack webhook and the admin approval land here, and
     * several can arrive for the same deposit at the same moment (Paystack
     * sends the browser callback and the webhook together; an attacker can
     * replay the callback in parallel). The status check therefore happens in
     * the database: a conditional UPDATE flips INITIATE/PENDING → SUCCESS, and
     * only the one request whose UPDATE touched the row goes on to credit.
     *
     * @return bool true if this call credited the wallet, false if the deposit
     *              had already been processed.
     */
    public static function userDataUpdate($deposit,$isManual = null)
    {
        $methodName = $deposit->methodName();

        $transaction = DB::transaction(function () use ($deposit, $methodName) {
            $claimed = Deposit::whereKey($deposit->id)
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
                ->update(['status' => Status::PAYMENT_SUCCESS]);

            if (!$claimed) {
                return null;
            }

            return app(WalletService::class)->credit(
                $deposit->user_id,
                (float) $deposit->amount,
                'deposit',
                'Deposit Via ' . $methodName,
                $deposit->trx,
                charge: (float) $deposit->charge,
            );
        });

        if (!$transaction) {
            return false;
        }

        $deposit->status = Status::PAYMENT_SUCCESS;
        $deposit->syncOriginalAttribute('status');

        // Blueprint: Financial Governance & Pool Separation (Phase 4)
        try {
            app(\App\Services\RevenueAllocationService::class)->allocate(
                (float) $deposit->amount,
                'deposit',
                $deposit->trx,
                'Deposit via ' . $methodName . ' by user #' . $deposit->user_id
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[RevenueAllocation] Failed on deposit: ' . $e->getMessage());
        }

        $user = User::find($deposit->user_id);

        if (!$isManual) {
            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $user->id;
            $adminNotification->title = 'Deposit successful via '.$methodName;
            $adminNotification->click_url = urlPath('admin.deposit.successful');
            $adminNotification->save();
        }

        notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
            'method_name' => $methodName,
            'method_currency' => $deposit->method_currency,
            'method_amount' => showAmount($deposit->final_amount,currencyFormat:false),
            'amount' => showAmount($deposit->amount,currencyFormat:false),
            'charge' => showAmount($deposit->charge,currencyFormat:false),
            'rate' => showAmount($deposit->rate,currencyFormat:false),
            'trx' => $deposit->trx,
            'post_balance' => showAmount($transaction->post_balance,currencyFormat:false)
        ]);

        return true;
    }

    public function manualDepositConfirm1()
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        if ($data->method_code > 999) {
            $pageTitle = 'Confirm Deposit';
            $method = $data->gatewayCurrency();
            $gateway = $method->method;
            return view('Template::user.payment.manual1', compact('data', 'pageTitle', 'method','gateway'));
        }
        abort(404);
    }


    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        if ($data->method_code > 999) {
            $pageTitle = 'Confirm Deposit';
            $method = $data->gatewayCurrency();
            $gateway = $method->method;
            return view('Template::user.payment.manual', compact('data', 'pageTitle', 'method','gateway'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway = $gatewayCurrency->method;
        $formData = $gateway->form->form_data;

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);


        $data->detail = $userData;
        $data->status = Status::PAYMENT_PENDING;
        $data->save();


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $data->user->id;
        $adminNotification->title = 'Deposit request from '.$data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details',$data->id);
        $adminNotification->save();

        notify($data->user, 'DEPOSIT_REQUEST', [
            'method_name' => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount' => showAmount($data->final_amount,currencyFormat:false),
            'amount' => showAmount($data->amount,currencyFormat:false),
            'charge' => showAmount($data->charge,currencyFormat:false),
            'rate' => showAmount($data->rate,currencyFormat:false),
            'trx' => $data->trx
        ]);

        $notify[] = ['success', 'You have deposit request has been taken'];
        return to_route('user.deposit.history')->withNotify($notify);
    }
    public function manualDepositUpdate1(Request $request)
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway = $gatewayCurrency->method;
        $formData = $gateway->form->form_data;

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);


        $data->detail = $userData;
        $data->status = Status::PAYMENT_PENDING;
        $data->save();


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $data->user->id;
        $adminNotification->title = 'Deposit request from '.$data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details',$data->id);
        $adminNotification->save();

        notify($data->user, 'DEPOSIT_REQUEST', [
            'method_name' => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount' => showAmount($data->final_amount,currencyFormat:false),
            'amount' => showAmount($data->amount,currencyFormat:false),
            'charge' => showAmount($data->charge,currencyFormat:false),
            'rate' => showAmount($data->rate,currencyFormat:false),
            'trx' => $data->trx
        ]);

        $notify[] = ['success', 'You have deposit request has been taken'];
        return to_route('user.deposit.history')->withNotify($notify);
    }


}
