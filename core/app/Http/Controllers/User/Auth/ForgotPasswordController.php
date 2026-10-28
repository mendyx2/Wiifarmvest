<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use App\Support\CaptchaPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    /** A reset code works for this long. */
    public const CODE_MINUTES = 60;

    /** Wrong codes allowed before the code is cancelled and a new one must be requested. */
    public const MAX_ATTEMPTS = 5;

    public function showLinkRequestForm()
    {
        return Inertia::render('Auth/ForgotPassword', [
            'captcha' => CaptchaPresenter::props(),
            'urls'    => ['submit' => route('user.password.email'), 'login' => route('user.login')],
        ])->withViewData(['pageTitle' => 'Account Recovery']);
    }

    public function sendResetCodeEmail(Request $request)
    {
        $request->validate([
            'value' => 'required'
        ]);

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $fieldType = $this->findFieldType();
        $user = User::where($fieldType, $request->value)->first();

        if (!$user) {
            $notify[] = ['error', 'The account could not be found'];
            return back()->withNotify($notify);
        }

        PasswordReset::where('email', $user->email)->delete();
        Cache::forget($this->attemptsKey($user->email));
        $code = verificationCode(6);
        $password = new PasswordReset();
        $password->email = $user->email;
        $password->token = $code;
        $password->created_at = \Carbon\Carbon::now();
        $password->save();

        $userIpInfo = getIpInfo();
        $userBrowserInfo = osBrowser();
        passwordReset($user, $code);
        notify($user, 'PASS_RESET_CODE', [
            'code' => $code,
            'operating_system' => @$userBrowserInfo['os_platform'],
            'browser' => @$userBrowserInfo['browser'],
            'ip' => @$userIpInfo['ip'],
            'time' => @$userIpInfo['time']
        ], ['email']);

        session()->put('pass_res_mail', $user->email);
        $notify[] = ['success', 'Password reset email sent successfully'];
        return to_route('user.password.code.verify')->withNotify($notify);
    }

    public function findFieldType()
    {
        $input = request()->input('value');

        $fieldType = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        request()->merge([$fieldType => $input]);
        return $fieldType;
    }

    public function codeVerify(Request $request)
    {
        $email = $request->session()->get('pass_res_mail');
        if (!$email) {
            $notify[] = ['error', 'Oops! session expired'];
            return to_route('user.password.request')->withNotify($notify);
        }

        return Inertia::render('Auth/VerifyCode', [
            'kind'   => 'reset',
            'title'  => 'Verify Email Address',
            'text'   => 'A 6 digit verification code sent to your email address: ' . showEmailAddress($email),
            'submit' => route('user.password.verify.code'),
            'again'  => route('user.password.request'),
        ])->withViewData(['pageTitle' => 'Verify Email']);
    }

    /**
     * The email comes from the session this browser started the reset in,
     * not from a form field. A code expires after CODE_MINUTES, and after
     * MAX_ATTEMPTS wrong guesses it is cancelled (the route is throttled too):
     * six digits are otherwise quick to guess.
     */
    public function verifyCode(Request $request)
    {
        $request->validate(['code' => 'required']);

        $email = $request->session()->get('pass_res_mail');
        if (!$email) {
            $notify[] = ['error', 'Oops! session expired'];
            return to_route('user.password.request')->withNotify($notify);
        }

        $code  = str_replace(' ', '', (string) $request->code);
        $reset = self::validReset($email, $code);

        if (!$reset) {
            Cache::add($this->attemptsKey($email), 0, now()->addMinutes(self::CODE_MINUTES));
            $attempts = (int) Cache::increment($this->attemptsKey($email));
            if ($attempts >= self::MAX_ATTEMPTS) {
                PasswordReset::where('email', $email)->delete();
                Cache::forget($this->attemptsKey($email));
                $request->session()->forget('pass_res_mail');
                $notify[] = ['error', 'Too many wrong codes. Please request a new one.'];
                return to_route('user.password.request')->withNotify($notify);
            }
            return back()->withErrors(['code' => 'Verification code doesn\'t match']);
        }

        Cache::forget($this->attemptsKey($email));
        // Kept (not flashed) until the password is changed, so reloading the next page works.
        session()->put('fpass_email', $email);
        $notify[] = ['success', 'You can change your password'];
        return to_route('user.password.reset', $code)->withNotify($notify);
    }

    /** The unexpired reset for this email and code, if there is one. */
    public static function validReset(?string $email, ?string $code): ?PasswordReset
    {
        if (!$email || !$code) {
            return null;
        }

        return PasswordReset::where('email', $email)
            ->where('token', $code)
            ->where('created_at', '>=', now()->subMinutes(self::CODE_MINUTES))
            ->first();
    }

    private function attemptsKey(string $email): string
    {
        return 'password-reset-attempts:' . sha1(strtolower($email));
    }
}
