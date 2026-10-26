<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Constants\Status;
use App\Lib\Intended;
use Inertia\Inertia;

class AuthorizationController extends Controller
{
    protected function checkCodeValidity($user,$addMin = 2)
    {
        if (!$user->ver_code_send_at){
            return false;
        }
        if ($user->ver_code_send_at->addMinutes($addMin) < Carbon::now()) {
            return false;
        }
        return true;
    }

    public function authorizeForm()
    {
        $user = auth()->user();
        if (!$user->status) {
            $pageTitle = 'Banned';
            $type = 'ban';
        }elseif(!$user->ev) {
            $type = 'email';
            $pageTitle = 'Verify Email';
            $notifyTemplate = 'EVER_CODE';
        }elseif (!$user->sv) {
            $type = 'sms';
            $pageTitle = 'Verify Mobile Number';
            $notifyTemplate = 'SVER_CODE';
        }elseif (!$user->tv) {
            $pageTitle = '2FA Verification';
            $type = '2fa';
        }else{
            return to_route('user.home');
        }

        if (!$this->checkCodeValidity($user) && ($type != '2fa') && ($type != 'ban')) {
            $user->ver_code = verificationCode(6);
            $user->ver_code_send_at = Carbon::now();
            $user->save();
            notify($user, $notifyTemplate, [
                'code' => $user->ver_code
            ],[$type]);
        }

        if ($type === 'ban') {
            return Inertia::render('Auth/Banned', [
                'reason' => $user->ban_reason,
                'id'     => $user->id,
                'date'   => now()->format('d M Y'),
                'urls'   => ['ticket' => route('ticket.open'), 'home' => route('home'), 'logout' => route('user.logout')],
            ])->withViewData(compact('pageTitle'));
        }

        $pages = [
            'email' => ['title' => 'Verify Email Address', 'text' => 'A 6 digit verification code sent to your email address: ' . showEmailAddress($user->email), 'submit' => route('user.verify.email')],
            'sms'   => ['title' => 'Verify Mobile Number', 'text' => 'A 6 digit verification code sent to your mobile number: +' . showMobileNumber($user->mobileNumber), 'submit' => route('user.verify.mobile')],
            '2fa'   => ['title' => '2FA Verification', 'text' => 'Enter the 6 digit code from your authenticator app.', 'submit' => route('user.2fa.verify')],
        ];

        return Inertia::render('Auth/VerifyCode', $pages[$type] + [
            'kind'     => $type,
            // Seconds until "Try again" may send a new code (codes are resent every 2 minutes at most).
            'resendIn' => $type === '2fa' ? null : max(0, ($user->ver_code_send_at?->copy()->addMinutes(2)->timestamp ?? 0) - time()),
            'resend'   => $type === '2fa' ? null : route('user.send.verify.code', $type),
            'logout'   => route('user.logout'),
        ])->withViewData(compact('pageTitle'));
    }

    public function sendVerifyCode($type)
    {
        $user = auth()->user();

        if ($this->checkCodeValidity($user)) {
            $targetTime = $user->ver_code_send_at->addMinutes(2)->timestamp;
            $delay = $targetTime - time();
            throw ValidationException::withMessages(['resend' => 'Please try after ' . $delay . ' seconds']);
        }

        $user->ver_code = verificationCode(6);
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        if ($type == 'email') {
            $type = 'email';
            $notifyTemplate = 'EVER_CODE';
        } else {
            $type = 'sms';
            $notifyTemplate = 'SVER_CODE';
        }

        notify($user, $notifyTemplate, [
            'code' => $user->ver_code
        ],[$type]);

        $notify[] = ['success', 'Verification code sent successfully'];
        return back()->withNotify($notify);
    }

    public function emailVerification(Request $request)
    {
        $request->validate([
            'code'=>'required'
        ]);

        $user = auth()->user();

        if ($user->ver_code == $request->code) {
            $user->ev = Status::VERIFIED;
            $user->ver_code = null;
            $user->ver_code_send_at = null;
            $user->save();

            return $this->leave();
        }
        throw ValidationException::withMessages(['code' => 'Verification code didn\'t match!']);
    }

    public function mobileVerification(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);


        $user = auth()->user();
        if ($user->ver_code == $request->code) {
            $user->sv = Status::VERIFIED;
            $user->ver_code = null;
            $user->ver_code_send_at = null;
            $user->save();
            return $this->leave();
        }
        throw ValidationException::withMessages(['code' => 'Verification code didn\'t match!']);
    }

    public function g2faVerification(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'code' => 'required',
        ]); 
        $response = verifyG2fa($user,$request->code);
        if ($response) {
            return $this->leave();
        }else{
            $notify[] = ['error','Wrong verification code'];
            return back()->withNotify($notify);
        }
    }

    /**
     * Verified: on to where the member was going. A full page load, because the
     * next page may still be Blade (the registration payment page).
     */
    private function leave()
    {
        $redirection = Intended::getRedirection();

        return Inertia::location($redirection ? $redirection->getTargetUrl() : route('user.home'));
    }
}
