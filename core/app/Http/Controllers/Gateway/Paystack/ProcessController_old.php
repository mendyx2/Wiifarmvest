<?php

namespace App\Http\Controllers\Gateway\Paystack;

use App\Constants\Status;
use App\Models\User;
use App\Models\Matrix;
use App\Models\Rmatrix;
use App\Models\Deposit;
use App\Models\MatrixStage;
use App\Models\GatewayCurrency;
use App\Models\AdminNotification;
use App\Models\UserLogin;
 use App\Models\Pin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Http\Request;
use App\Services\MatrixPlacementService2;

class ProcessController extends Controller
{
    /*
     * PayStack Gateway
     */
    public function __construct(MatrixPlacementService2 $matrixService2)
    {
        
        $this->matrixService2 = $matrixService2;

    }

    public static function process($deposit)
    {
        $paystackAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);

        $alias = $deposit->gateway->alias;


        $send['key'] = $paystackAcc->public_key;
        $send['email'] = auth()->user()->email;
        $send['amount'] = $deposit->final_amount * 100;
        $send['currency'] = $deposit->method_currency;
        $send['ref'] = $deposit->trx;
        $send['view'] = 'user.payment.'.$alias;
        return json_encode($send);
    }



    public function ipn(Request $request)
    {
        $request->validate([
            'reference' => 'required',
            'paystack-trxref' => 'required',
        ]);
        $track = $request->reference;
        $deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();
        $paystackAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
        $secret_key = $paystackAcc->secret_key;

        $result = array();
        //The parameter after verify/ is the transaction reference to be verified
        $url = 'https://api.paystack.co/transaction/verify/' . $track;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secret_key]);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $result = json_decode($response, true);

            if ($result) {
                if ($result['data']) {

                    $deposit->detail = $result['data'];
                    $deposit->save();

                    if ($result['data']['status'] == 'success') {

                        $am = $result['data']['amount']/100;
                        $sam = round($deposit->final_amount, 2);

                        if ($am == $sam && $result['data']['currency'] == $deposit->method_currency  && $deposit->status == Status::PAYMENT_INITIATE) {
                            PaymentController::userDataUpdate($deposit);
                            $notify[] = ['success', 'Payment captured successfully'];
                            return redirect($deposit->success_url)->withNotify($notify);
                        } else {
                            $notify[] = ['error', 'Less amount paid. Please contact with admin.'];
                        }
                    } else {
                        $notify[] = ['error', $result['data']['gateway_response']];
                    }
                } else {
                    $notify[] = ['error', $result['message']];
                }
            } else {
                $notify[] = ['error', 'Something went wrong while executing'];
            }
        } else {
            $notify[] = ['error', 'Something went wrong while executing'];
        }
        return back()->withNotify($notify);
    }

    public function ipn1(Request $request)
    {
        $request->validate([
            'reference' => 'required',
            'paystack-trxref' => 'required',
        ]);
        $user = auth()->user(); 
        //dd($user);
        $track = $request->reference;
        //dd($track);
        $rmatrix = Rmatrix::where('user_id', $user->id)->first();

        $paysAcc = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();

        //$deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();
        $paystackAcc = json_decode($paysAcc->gateway_parameter);
        $secret_key = $paystackAcc->secret_key;

        $result = array();
        //The parameter after verify/ is the transaction reference to be verified
        $url = 'https://api.paystack.co/transaction/verify/' . $track;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secret_key]);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $result = json_decode($response, true);

            if ($result && $rmatrix) {
                if ($result['data']) {
                    // add user to matrix, create a helper function here to process all that.

                    $details = $result['data'];
                    //$deposit->save();

                    if ($result['data']['status'] == 'success') {
                       

                        $am = $result['data']['amount']/100;
                        $sam = round(gs()->registration_fee, 2);

                        //work on the payment iniated later
                        if ($am == $sam && $result['data']['currency'] == 'NGN') {

                            $parent = User::find($user->ref_by); 
                            if($rmatrix->parent_id == 0 && $parent){
                                 $matrix = Matrix::where('stage_id', 1)->where('user_id', $parent->id)->first(); 

                                // Get the root user
                                $user_parent_matrix = $this->matrixService2->findDownline_reg($matrix, $stage_id = 1);
                                //dd($user_parent_matrix);

                                if ($user_parent_matrix) {
                                   complete_registration($user, $details, $rmatrix, $user_parent_matrix);
                                }else{
                                    complete_registration($user, $details, $rmatrix, 0);
                                }

                                

                            }else{
                                complete_registration($user, $details, $rmatrix, 0);
                            }
                            
                            
                            //PaymentController::userDataUpdate1($user, $sam);

                            //notify[] = ['success', 'Registration successfully'];
                            //return redirect($deposit->success_url)->withNotify($notify);
                            return to_route('user.land');
                        } else {
                            $notify[] = ['error', 'Less amount paid. Please contact with admin.'];
                        }
                    } else {
                        $notify[] = ['error', $result['data']['gateway_response']];
                    }
                } else {
                    $notify[] = ['error', $result['message']];
                }
            } else {
                $notify[] = ['error', 'Something went wrong while executing'];
            }
        } else {
            $notify[] = ['error', 'Something went wrong while executing'];
        }
        return back()->withNotify($notify);
    }

    public function pinPayment(Request $request)
    {
        //Add a return statement
        $request->validate([
            'pin' => 'required|exists:pins,pin',
            '_token' =>'required|string',
            
        ]);

        $user = auth()->user();
        $pin = Pin::where('pin', $request->pin)->where('status', Status::NO)->first();
        
        if(!$pin)
        {
            $notify[] = ['error', 'Invalid: please check pin and try again. CD102.'];
            return back()->withNotify($notify);
        }
        

        //dd($request);
        //72515418-46623570-32811687-43004279
        
        $rmatrix = Rmatrix::where('user_id', $user->id)->first();
        //dd($rmatrix);
        
        if ($rmatrix) {
               
            $sam = gs()->registration_fee;
            //dd($pin->amount);
            //work on the payment iniated later

            if ($pin->amount == $sam) {

                $parent = User::find($user->ref_by); 

                if($rmatrix->parent_id == 0 && $parent){
                    $matrix = Matrix::where('stage_id', 1)->where('user_id', $parent->id)->first(); 

                    // Get the root user
                    $user_parent_matrix = $this->matrixService2->findDownline_reg($matrix, $stage_id = 1);
                    //dd($user_parent_matrix);

                    if ($user_parent_matrix) {
                        complete_registration_pin($user, $rmatrix, $user_parent_matrix, $pin);
                    }else{
                        complete_registration_pin($user, $rmatrix, 0);
                    }

                }else{
                    complete_registration_pin($user, $rmatrix, 0);
                }

                // PaymentController::userDataUpdate1($user, $sam);
                return to_route('user.home');

            } else {
                     $notify[] = ['error', 'Less amount paid. CODE: CD103 .'];
            }

        // this is the end
        } else {
            $notify[] = ['error', 'Something went wrong please chat with support CODE: CD101 '];
        }
        
        return back()->withNotify($notify);
    }
}
