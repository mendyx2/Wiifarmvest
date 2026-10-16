<?php

namespace App\Http\Controllers\Gateway\Paystack;

use App\Constants\Status;
use App\Models\User;
use App\Models\Matrix;
use App\Models\Rmatrix;
use App\Models\Deposit;
use App\Models\MatrixStage;
use App\Models\Project;
use App\Models\GatewayCurrency;
use App\Models\AdminNotification;
use App\Models\UserLogin;
use App\Models\Pin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Paystack server-to-server webhook (charge.success).
     * Register this URL in the Paystack dashboard: https://yourdomain.com/ipn/paystack2
     *
     * Acts as the fallback processor for both payment flows — deposits and
     * membership registrations — so that network failures on the user's side
     * do not leave transactions stuck. Paystack retries failed webhooks for
     * up to 72 hours, guaranteeing eventual processing.
     *
     * Flow detection uses the metadata.payment_type field set when each form
     * initialises the Paystack popup: 'deposit' → processDepositWebhook,
     * 'registration' → processRegistrationWebhook.
     * A reference-lookup fallback handles any transactions that pre-date the
     * metadata field.
     *
     * SECURITY: HMAC-SHA512 signature is verified before any parsing or DB
     * access, ensuring only genuine Paystack events are processed.
     */
    public function ipn_webhook(Request $request)
    {
        // ── 1. Capture raw body before any framework parsing ──────────────
        $payload   = $request->getContent();
        $signature = (string) $request->header('x-paystack-signature', '');

        // ── 2. Verify HMAC signature first — reject non-Paystack requests ─
        $paysAcc = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();
        if (!$paysAcc) {
            \Log::error('Paystack webhook: GatewayCurrency (method_code=107, NGN) not found.');
            return response('OK', 200);
        }

        $paystackAcc = json_decode($paysAcc->gateway_parameter);
        $secret_key  = $paystackAcc->secret_key ?? '';
        $computed    = hash_hmac('sha512', $payload, $secret_key);

        if (!hash_equals($computed, $signature)) {
            return response('Forbidden', 403);
        }

        // ── 3. Parse and validate the event ───────────────────────────────
        $event = json_decode($payload, true);

        if (empty($event['event']) || $event['event'] !== 'charge.success') {
            return response('OK', 200);
        }

        $data      = $event['data'] ?? [];
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return response('OK', 200);
        }

        // ── 4. Route to the correct processor ─────────────────────────────
        $paymentType = $data['metadata']['payment_type'] ?? null;

        if ($paymentType === 'deposit') {
            $this->processDepositWebhook($data, $reference);
        } elseif ($paymentType === 'registration') {
            $this->processRegistrationWebhook($data, $reference);
        } elseif ($paymentType === 'affiliate_shop') {
            $this->processAffiliateShopWebhook($data, $reference);
        } else {
            // Fallback: check by reference — deposit table, affiliate orders, then user trx
            $deposit = Deposit::where('trx', $reference)->orderBy('id', 'DESC')->first();
            if ($deposit) {
                $this->processDepositWebhook($data, $reference);
            } elseif (\App\Models\AffiliateOrder::where('order_code', $reference)->exists()) {
                $this->processAffiliateShopWebhook($data, $reference);
            } else {
                $this->processRegistrationWebhook($data, $reference);
            }
        }

        return response('OK', 200);
    }

    /**
     * Server-to-server confirmation for /shop Paystack orders — the reliability
     * fallback for AffiliateOrderService::markPaystackPaid(), same role this
     * webhook already plays for deposits/registration above.
     */
    private function processAffiliateShopWebhook(array $data, string $reference): void
    {
        $order = \App\Models\AffiliateOrder::where('order_code', $reference)->first();

        if (!$order || $order->status !== \App\Models\AffiliateOrder::STATUS_PENDING) {
            return;
        }

        $paid     = round($data['amount'] / 100, 2);
        $expected = round($order->total_amount, 2);

        if ($data['status'] == 'success' && $paid >= $expected && $data['currency'] == 'NGN') {
            app(\App\Services\AffiliateOrderService::class)->markPaystackPaid($order, $reference);
        }
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
                    ->first();

        if (!$user) {
            return;
        }

        $paid     = round($data['amount'] / 100, 2);
        $project  = $user->project_id ? Project::find($user->project_id) : null;
        $expected = round($project ? (float) $project->amount : (float) gs()->registration_fee, 2);

        if (
            $data['status'] == 'success' &&
            $paid >= $expected &&
            $data['currency'] == 'NGN'
        ) {
            $this->register_now($user, 0, $data);
            //complete_registration($user, $data);
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
            $notify[] = ['error', 'Something went wrong while executing Send support this code:PY102'];
            return back()->withNotify($notify);
        }

        $result = json_decode($response, true);

        if (!$result || empty($result['data'])) {
            $notify[] = ['error', 'Something went wrong while executing Send support this code: PY101'];
            return back()->withNotify($notify);
        }

        $data = $result['data'];

        if ($data['status'] != 'success') {
            $notify[] = ['error', $data['gateway_response'] ?? 'Payment was not successful.'];
            return back()->withNotify($notify);
        }

        // The reference must be a payment made by THIS account. Paystack
        // confirms any successful reference, so without this check one member's
        // payment could be replayed to register other accounts for free. The
        // payment is started with the user's email (unique per account), and
        // users.trx is regenerated on every page load, so email is the stable key.
        if (strcasecmp((string) ($data['customer']['email'] ?? ''), (string) $user->email) !== 0) {
            $notify[] = ['error', 'This payment does not belong to your account. Ref: ' . $track];
            return back()->withNotify($notify);
        }

        $paid     = round($data['amount'] / 100, 2);
        $project  = $user->project_id ? Project::find($user->project_id) : null;
        $expected = round($project ? (float) $project->amount : (float) gs()->registration_fee, 2);

        if ($paid >= $expected && $data['currency'] == 'NGN') {
            // change this to read table Rmatrixes section
            
            $this->register_now($user, 0, $data);
            
           //this want the old method
           //complete_registration($user, $data);
            return to_route('user.home');
            
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

        $user    = auth()->user();
        $pin     = Pin::where('pin', $request->pin)->where('status', Status::NO)->first();

        if(!$pin)
        {
            $notify[] = ['error', 'Invalid: please check pin and try again.'];
            return back()->withNotify($notify);
        }

        $project = $user->project_id ? Project::find($user->project_id) : null;
        $sam     = $project ? (float) $project->amount : (float) gs()->registration_fee;

        if ($pin->amount >= $sam) {
            return $this->Register_now($user, $pin);
        }

        $notify[] = ['error', 'Less amount paid. Contact support if it persists.'];
        return back()->withNotify($notify);
    }

    /**
     * Complete a paid registration — exactly once.
     *
     * The Paystack webhook, the browser callback (ipn1) and the PIN form can
     * all arrive for the same user at once, and registration pays direct and
     * indirect bonuses, PV and matrix placement. So the work is serialised on
     * the user row (and the PIN row) and the "already done?" checks are made
     * again inside those locks, against the current database values.
     */
    protected function register_now(User $user, $pin = null, $data = null){

        return DB::transaction(function () use ($user, $pin) {
            $locked = User::lockForUpdate()->find($user->id);

            if (!$locked || $locked->profile_complete == Status::YES) {
                return to_route('user.home');
            }

            if ($pin instanceof Pin) {
                $pin = Pin::lockForUpdate()->find($pin->id);

                if (!$pin || $pin->status != Status::NO) {
                    $notify[] = ['error', 'Invalid: this pin has already been used.'];
                    return back()->withNotify($notify);
                }
            }

            return $this->completeRegistration($locked, $pin);
        });
    }

    private function completeRegistration(User $user, $pin = null){

        $rmatrix = Rmatrix::where('user_id', $user->id)->first();
        $sam = gs()->registration_fee;

        if ($rmatrix) {
            $parent = User::find($user->ref_by); 
                //investigate from here
            if($rmatrix->parent_id == 0 && $parent){

                $matrix = Matrix::where('stage_id', 1)->where('user_id', $parent->id)->first(); 
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
                     
                complete_registration_pin($user, $rmatrix, 0, $pin);
            } 

            // PaymentController::userDataUpdate1($user, $sam);
            return to_route('user.home');

            

        // this is the end
        } else {
            $notify[] = ['error', 'Something went wrong please chat with support CODE: CD101 '];
            return back()->withNotify($notify);
        }
        
        
    }
}
