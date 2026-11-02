<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Loan;
use App\Models\Withdrawal;
use App\Support\FormPresenter;
use App\Support\PaymentPresenter;
use Inertia\Inertia;
use App\Models\WithdrawMethod;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawController extends Controller
{

    private function checkWithdrawalBlock()
    {
        $user = auth()->user();
        if ($user->withdrawal_blocked) {
            $notify[] = ['error', 'Your withdrawal access has been suspended. Please contact support.'];
            return redirect()->route('user.withdraw.history')->withNotify($notify);
        }
        return null;
    }

    private function checkBankDetails()
    {
        $user = auth()->user();
        if (empty($user->bname) || empty($user->aname) || empty($user->ano) || strlen((string) $user->ano) <= 9) {
            $notify[] = ['error', 'Please complete your bank account details before making a withdrawal. Ensure your account number is valid (more than 9 digits).'];
            return redirect()->route('user.profile.setting')->withNotify($notify);
        }
        return null;
    }

    private function checkPendingLoans()
    {
        $hasPending = Loan::where('user_id', auth()->id())
                          ->where('status', 'pending')
                          ->exists();

        if ($hasPending) {
            $notify[] = ['error', 'You cannot withdraw while you have a pending loan application. Please wait for it to be approved or rejected.'];
            return back()->withNotify($notify);
        }
        return null;
    }

    public function withdrawMoney()
    {
        $redirect = $this->checkWithdrawalBlock() ?? $this->checkPendingLoans() ?? $this->checkBankDetails();
        if ($redirect) return $redirect;

        $withdrawMethod = WithdrawMethod::active()->get();
        $pageTitle = 'Withdraw Money';
        $user = auth()->user();

        // Migrated to React + Inertia (resources/js/Pages/User/Withdraw.tsx).
        // Was the Blade view Template::user.withdraw.methods (deleted)
        return Inertia::render('User/Withdraw', [
            // checkWithdrawalBlock() above already redirects blocked users; the page
            // keeps the notice too, as the Blade view did.
            'blocked'     => (bool) $user->withdrawal_blocked,
            'blockReason' => $user->withdrawal_blocked ? $user->withdrawal_block_reason : null,
            'balance'     => gs('cur_sym') . number_format($user->balance, 2),
            'bank'        => ['name' => $user->bname, 'accountName' => $user->aname, 'accountNumber' => $user->ano],
            'methods'     => $withdrawMethod->map(fn ($method) => [
                'id'            => $method->id,
                'name'          => __($method->name),
                'image'         => getImage(getFilePath('withdrawMethod') . '/' . $method->image),
                'currency'      => $method->currency,
                'minLimit'      => (float) $method->min_limit,
                'maxLimit'      => (float) $method->max_limit,
                'minLabel'      => showAmount($method->min_limit),
                'maxLabel'      => showAmount($method->max_limit),
                'percentCharge' => (float) $method->percent_charge,
                'fixedCharge'   => (float) $method->fixed_charge,
                'rate'          => (float) $method->rate,
            ])->values(),
            'curSym'     => gs('cur_sym'),
            'curText'    => __(gs('cur_text')),
            'submitUrl'  => route('user.withdraw.money'),
            'historyUrl' => route('user.withdraw.history'),
            'profileUrl' => route('user.profile.setting'),
        ])->withViewData(compact('pageTitle'));
    }


    public function withdrawStore(Request $request)
    {
        $redirect = $this->checkWithdrawalBlock() ?? $this->checkPendingLoans();
        if ($redirect) return $redirect;

        $request->validate([
            'method_code' => 'required',
            'amount' => 'required|numeric'
        ]);
        $method = WithdrawMethod::where('id', $request->method_code)->active()->firstOrFail();
        $user = auth()->user();
        if ($request->amount < $method->min_limit) {
            $notify[] = ['error', 'Your requested amount is smaller than minimum amount'];
            return back()->withNotify($notify)->withInput($request->all());
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = ['error', 'Your requested amount is larger than maximum amount'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        if ($request->amount > $user->balance) {
            $notify[] = ['error', 'Insufficient balance for withdrawal'];
            return back()->withNotify($notify)->withInput($request->all());
        }


        $charge = $method->fixed_charge + ($request->amount * $method->percent_charge / 100);
        $afterCharge = $request->amount - $charge;

        if ($afterCharge <= 0) {
            $notify[] = ['error', 'Withdraw amount must be sufficient for charges'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        $finalAmount = $afterCharge * $method->rate;

        $withdraw = new Withdrawal();
        $withdraw->method_id = $method->id; // wallet method ID
        $withdraw->user_id = $user->id;
        $withdraw->amount = $request->amount;
        $withdraw->currency = $method->currency;
        $withdraw->rate = $method->rate;
        $withdraw->charge = $charge;
        $withdraw->final_amount = $finalAmount;
        $withdraw->after_charge = $afterCharge;
        $withdraw->trx = getTrx();
        $withdraw->save();
        session()->put('wtrx', $withdraw->trx);
        return to_route('user.withdraw.preview');
    }


    public function withdrawPreview()
    {
        $withdraw = Withdrawal::with('method','user')->where('trx', session()->get('wtrx'))->where('status', Status::PAYMENT_INITIATE)->orderBy('id','desc')->firstOrFail();
        $pageTitle = 'Withdraw Preview';

        // Migrated to React + Inertia (resources/js/Pages/User/WithdrawPreview.tsx).
        // Was the Blade view Template::user.withdraw.preview (deleted)
        return Inertia::render('User/WithdrawPreview', [
            'amount'      => showAmount($withdraw->amount),
            'charge'      => showAmount($withdraw->charge),
            'net'         => showAmount($withdraw->amount - $withdraw->charge),
            'finalAmount' => showAmount($withdraw->final_amount, currencyFormat: false) . ' ' . $withdraw->currency,
            'currency'    => $withdraw->currency,
            'methodName'  => $withdraw->method->name,
            'description' => FormPresenter::safeHtml($withdraw->method->description),
            'fields'      => FormPresenter::fields($withdraw->method->form_id),
            'twoFactor'   => (bool) auth()->user()->ts,
            'submitUrl'   => route('user.withdraw.submit'),
        ])->withViewData(compact('pageTitle'));
    }



    public function withdrawSubmit(Request $request)
    {
        return $this->submitWithdrawal($request, 'user.withdraw.history');
    }


    /**
     * Confirm an initiated withdrawal and take the money.
     *
     * The withdrawal row is locked and re-checked so a double submit can only
     * move it INITIATE → PENDING once, and the debit goes through
     * WalletService so a parallel purchase can't spend the same balance. Both
     * happen in one transaction: either the request is pending AND the money
     * is gone, or neither.
     */
    private function submitWithdrawal(Request $request, string $historyRoute)
    {
        $redirect = $this->checkWithdrawalBlock() ?? $this->checkPendingLoans();
        if ($redirect) return $redirect;

        $withdraw = Withdrawal::with('method','user')->where('trx', session()->get('wtrx'))->where('status', Status::PAYMENT_INITIATE)->orderBy('id','desc')->firstOrFail();

        $method = $withdraw->method;
        if ($method->status == Status::DISABLE) {
            abort(404);
        }

        $formData = @$method->form->form_data ?? [];

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);

        $user = auth()->user();
        if ($user->ts) {
            $response = verifyG2fa($user,$request->authenticator_code);
            if (!$response) {
                $notify[] = ['error', 'Wrong verification code'];
                return back()->withNotify($notify)->withInput($request->all());
            }
        }

        try {
            $submitted = DB::transaction(function () use ($withdraw, $user, $userData) {
                $locked = Withdrawal::lockForUpdate()->find($withdraw->id);

                if (!$locked || $locked->status != Status::PAYMENT_INITIATE) {
                    return false;
                }

                app(WalletService::class)->debit(
                    $user,
                    (float) $withdraw->amount,
                    'withdraw',
                    'Withdraw request via ' . $withdraw->method->name,
                    $withdraw->trx,
                    charge: (float) $withdraw->charge,
                );

                $locked->status = Status::PAYMENT_PENDING;
                $locked->withdraw_information = $userData;
                $locked->save();

                return true;
            });
        } catch (InsufficientBalanceException) {
            $notify[] = ['error', 'Your request amount is larger then your current balance.'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        if (!$submitted) {
            $notify[] = ['error', 'This withdrawal has already been submitted.'];
            return to_route($historyRoute)->withNotify($notify);
        }

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New withdraw request from '.$user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.data.details',$withdraw->id);
        $adminNotification->save();

        notify($user, 'WITHDRAW_REQUEST', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => showAmount($withdraw->final_amount,currencyFormat:false),
            'amount' => showAmount($withdraw->amount,currencyFormat:false),
            'charge' => showAmount($withdraw->charge,currencyFormat:false),
            'rate' => showAmount($withdraw->rate,currencyFormat:false),
            'trx' => $withdraw->trx,
            'post_balance' => showAmount($user->balance,currencyFormat:false),
        ]);

        $notify[] = ['success', 'Withdraw request sent successfully'];
        return to_route($historyRoute)->withNotify($notify);
    }

    public function withdrawLog(Request $request)
    {
        $pageTitle = "Withdrawal Log";
        $withdraws = Withdrawal::where('user_id', auth()->id())->where('status', '!=', Status::PAYMENT_INITIATE);
        if ($request->search) {
            $withdraws = $withdraws->where('trx',$request->search);
        }
        $withdraws = $withdraws->with('method')->orderBy('id','desc')->paginate(getPaginate())->withQueryString();

        // Totals moved here from the old view's @php block.
        $userId = auth()->id();
        $sum    = fn ($status) => gs('cur_sym') . number_format(Withdrawal::where('user_id', $userId)->where('status', $status)->sum('amount'), 2);
        $user   = auth()->user();

        // Migrated to React + Inertia (resources/js/Pages/User/WithdrawHistory.tsx).
        // Was the Blade view Template::user.withdraw.log (deleted)
        return Inertia::render('User/WithdrawHistory', [
            'withdraws' => $withdraws->through(fn ($withdraw) => [
                'id'            => $withdraw->id,
                'trx'           => $withdraw->trx,
                'gateway'       => __(@$withdraw->method->name),
                'date'          => $withdraw->created_at->format('M d, Y'),
                'time'          => $withdraw->created_at->format('h:i A'),
                'net'           => showAmount($withdraw->amount - $withdraw->charge),
                'amount'        => showAmount($withdraw->amount),
                'charge'        => showAmount($withdraw->charge),
                'rate'          => '1 ' . gs('cur_text') . ' = ' . showAmount($withdraw->rate, currencyFormat: false) . ' ' . __($withdraw->currency),
                'finalAmount'   => showAmount($withdraw->final_amount, currencyFormat: false) . ' ' . __($withdraw->currency),
                'status'        => PaymentPresenter::withdrawStatus($withdraw),
                'details'       => PaymentPresenter::details($withdraw->withdraw_information),
                'adminFeedback' => $withdraw->status == Status::PAYMENT_REJECT ? $withdraw->admin_feedback : null,
            ]),
            'stats' => [
                'withdrawn' => $sum(Status::PAYMENT_SUCCESS),
                'pending'   => $sum(Status::PAYMENT_PENDING),
                'rejected'  => $sum(Status::PAYMENT_REJECT),
                'requests'  => Withdrawal::where('user_id', $userId)->where('status', '!=', Status::PAYMENT_INITIATE)->count(),
            ],
            'blocked'     => (bool) $user->withdrawal_blocked,
            'blockReason' => $user->withdrawal_blocked ? $user->withdrawal_block_reason : null,
            'search'      => (string) $request->search,
            'withdrawUrl' => route('user.withdraw'),
        ])->withViewData(compact('pageTitle'));
    }


}
