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

        $send['key']      = $paystackAcc->public_key;
        $send['email']    = auth()->user()->email;
        $send['amount']   = $deposit->final_amount * 100;
        $send['currency'] = $deposit->method_currency;
        $send['ref']      = $deposit->trx;
        $send['view']     = 'user.payment.' . $deposit->gateway->alias;

        return json_encode($send);
    }



    /**
     * Paystack redirects the user back here after payment (callback_url).
     * We verify the transaction and credit the wallet. The webhook below
     * does the same thing server-to-server, so whichever fires first wins
     * (duplicate guard: status checked before processing).
     */
    public function callback(Request $request)
    {
        $track   = $request->trxref ?? $request->reference;
        $deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();

        if (!$deposit) {
            $notify[] = ['error', 'Transaction not found.'];
            return redirect(route('user.deposit.history'))->withNotify($notify);
        }

        // Already processed (webhook beat us to it)
        if ($deposit->status != Status::PAYMENT_INITIATE) {
            $notify[] = ['success', 'Payment processed successfully!'];
            return redirect(urlPath('user.deposit.history'))->withNotify($notify);
        }

        $paystackAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
        $secret_key  = $paystackAcc->secret_key;

        $url = 'https://api.paystack.co/transaction/verify/' . $track;
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secret_key]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!$result || empty($result['data'])) {
            $notify[] = ['error', 'Could not verify payment. Please contact support.'];
            return redirect(urlPath('user.deposit.history'))->withNotify($notify);
        }

        $data = $result['data'];

        if ($data['status'] == 'success') {
            $paid    = round($data['amount'] / 100, 2);
            $expected = round($deposit->final_amount, 2);

            if ($paid >= $expected && $data['currency'] == $deposit->method_currency) {
                $deposit->detail = $data;
                $deposit->save();
                PaymentController::userDataUpdate($deposit);
                $notify[] = ['success', 'Payment captured successfully!'];
            } else {
                $notify[] = ['error', 'Amount mismatch. Please contact support. Ref: ' . $track];
            }
        } else {
            $notify[] = ['error', $data['gateway_response'] ?? 'Payment was not successful.'];
        }

        return redirect(urlPath('user.deposit.history'))->withNotify($notify);
    }

    /**
     * Paystack Webhook (charge.success event).
     * URL to add in Paystack dashboard: https://yourdomain.com/ipn/paystack2
     *
     * Routes to the correct handler based on metadata.payment_type sent at
     * payment initialisation ('registration' or 'deposit'). Payments initiated
     * before metadata was added fall through to a reference-lookup fallback.
     */
    public function ipn_webhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $event     = json_decode($payload, true);

        if (empty($event['event']) || $event['event'] != 'charge.success') {
            return response('OK', 200);
        }

        $reference = $event['data']['reference'] ?? null;
        if (!$reference) {
            return response('OK', 200);
        }

        // Verify signature using the shared Paystack account credentials
        $paysAcc = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();
        if (!$paysAcc) {
            return response('OK', 200);
        }

        $paystackAcc = json_decode($paysAcc->gateway_parameter);
        $secret_key  = $paystackAcc->secret_key;
        $computed    = hash_hmac('sha512', $payload, $secret_key);

        if (!hash_equals($computed, (string) $signature)) {
            return response('Invalid signature', 401);
        }

        $data        = $event['data'];
        $paymentType = $data['metadata']['payment_type'] ?? null;

        if ($paymentType == 'deposit') {
            $this->processDepositWebhook($data, $reference);
        } elseif ($paymentType == 'registration') {
            $this->processRegistrationWebhook($data, $reference);
        } else {
            // Fallback for payments initiated before metadata was introduced
            $deposit = Deposit::where('trx', $reference)->orderBy('id', 'DESC')->first();
            if ($deposit) {
                $this->processDepositWebhook($data, $reference);
            } else {
                $this->processRegistrationWebhook($data, $reference);
            }
        }

        return response('OK', 200);
    }

    private function processDepositWebhook(array $data, string $reference): void
    {
        $deposit = Deposit::where('trx', $reference)->orderBy('id', 'DESC')->first();

        if (!$deposit || $deposit->status != Status::PAYMENT_INITIATE) {
            return;
        }

        $paid     = round($data['amount'] / 100, 2);
        $expected = round($deposit->final_amount, 2);

        if (
            $data['status'] == 'success' &&
            $paid >= $expected &&
            $data['currency'] == $deposit->method_currency
        ) {
            $deposit->detail = $data;
            $deposit->save();
            PaymentController::userDataUpdate($deposit);
        }
    }

    private function processRegistrationWebhook(array $data, string $reference): void
    {
        $user = User::where('trx', $reference)
                    ->where('profile_complete', Status::NO)
                    ->where('section', 2)
                    ->first();

        if (!$user) {
            return;
        }

        $paid     = round($data['amount'] / 100, 2);
        $expected = round(gs()->registration_fee, 2);

        if (
            $data['status'] == 'success' &&
            $paid >= $expected &&
            $data['currency'] == 'NGN'
        ) {
            complete_registration($user, $data);
        }
    }

    public function  ipn(Request $request)
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

        $user  = auth()->user();
        $track = $request->reference;

        // Guard: webhook may have already processed this payment
        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $paysAcc     = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();
        $paystackAcc = json_decode($paysAcc->gateway_parameter);
        $secret_key  = $paystackAcc->secret_key; 

        $url = 'https://api.paystack.co/transaction/verify/' . $track;
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secret_key]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            $notify[] = ['error', 'Something went wrong while executing CODE:PY102'];
            return back()->withNotify($notify);
        }

        $result = json_decode($response, true);

        if (!$result || empty($result['data'])) {
            $notify[] = ['error', 'Something went wrong while executing CODE:PY101'];
            return back()->withNotify($notify);
        }

        $data = $result['data'];

        if ($data['status'] != 'success') {
            $notify[] = ['error', $data['gateway_response'] ?? 'Payment was not successful.'];
            return back()->withNotify($notify);
        }

        $paid     = round($data['amount'] / 100, 2);
        $expected = round(gs()->registration_fee, 2);

        if ($paid >= $expected && $data['currency'] == 'NGN') {
            // change this to read table Rmatrixes section
            complete_registration($user, $data);
            return to_route('user.land');
        }

        $notify[] = ['error', 'Amount mismatch. Please contact support. Ref: ' . $track];
        return back()->withNotify($notify);
    }
 
    public function pinPayment(Request $request)
    { //
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


        $sam = gs()->registration_fee;
        //dd($sam);
        if ($rmatrix) {

            if ($pin->amount == $sam) {

                $parent = User::find($user->ref_by); 
                //investigate from here
                if($rmatrix->parent_id == 0 && $parent){

                    $matrix = Matrix::where('stage_id', 1)->where('user_id', $parent->id)->first(); 
                    //dd($matrix);

                    if ($matrix) {
                       // Get the root user
                        $user_parent_matrix = $this->matrixService2->findDownline_reg($matrix, $stage_id = 1);
                        //dd($user_parent_matrix);
                        if ($user_parent_matrix) {
                            //here
                        complete_registration_pin($user, $rmatrix, $user_parent_matrix, $pin);
                        }else{
                            complete_registration_pin($user, $rmatrix, 0, $pin);
                        }
                    }else{
                        complete_registration_pin($user, $rmatrix, 0, $pin);
                    }
                   
                }else{
                    ///



                   // dd($pin);
                    complete_registration_pin($user, $rmatrix, 0, $pin);
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

    protected function Register_now(){
        $rmatrix = Rmatrix::where('user_id', $user->id)->first();
        $sam = gs()->registration_fee;
        
        if ($rmatrix) {

            if ($pin->amount == $sam) {

                $parent = User::find($user->ref_by); 
                //investigate from here
                if($rmatrix->parent_id == 0 && $parent){

                    $matrix = Matrix::where('stage_id', 1)->where('user_id', $parent->id)->first(); 
                    //dd($matrix);

                    if ($matrix) {
                       // Get the root user
                        $user_parent_matrix = $this->matrixService2->findDownline_reg($matrix, $stage_id = 1);
                        //dd($user_parent_matrix);
                        if ($user_parent_matrix) {
                            //here
                        complete_registration_pin($user, $rmatrix, $user_parent_matrix, $pin);
                        }else{
                            complete_registration_pin($user, $rmatrix, 0, $pin);
                        }
                    }else{
                        complete_registration_pin($user, $rmatrix, 0, $pin);
                    }
                   
                }else{
                    ///



                   // dd($pin);
                    complete_registration_pin($user, $rmatrix, 0, $pin);
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
