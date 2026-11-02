<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminPasswordReset;
use App\Support\CaptchaPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Admin password reset, steps 1 and 2 (Pages/Admin/Auth/Forgot.tsx and
 * Code.tsx): ask for the email, then check the 6-digit code sent to it.
 * Step 3 is ResetPasswordController.
 */
class ForgotPasswordController extends Controller
{
    /** A reset code works for this long. */
    public const CODE_MINUTES = 60;

    /** Wrong codes allowed before the code is cancelled and a new one must be requested. */
    public const MAX_ATTEMPTS = 5;

    public function showLinkRequestForm()
    {
        return Inertia::render('Admin/Auth/Forgot', [
            'logo'    => siteLogo(),
            'captcha' => CaptchaPresenter::props(),
            'urls'    => ['submit' => route('admin.password.reset'), 'login' => route('admin.login')],
        ])->withViewData(['pageTitle' => 'Account Recovery']);
    }

    /**
     * Every address gets the same answer and goes on to the code page, so
     * the form cannot be used to find out which emails are admin accounts
     * (it used to say "No admin account found with this email").
     */
    public function sendResetCodeEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        if (!verifyCaptcha()) {
            throw ValidationException::withMessages(['captcha' => 'Invalid captcha provided']);
        }

        $admin = Admin::where('email', $request->email)->first();
        if ($admin) {
            // Only the newest code works.
            AdminPasswordReset::where('email', $admin->email)->where('status', Status::ENABLE)->update(['status' => Status::DISABLE]);
            Cache::forget(self::attemptsKey($admin->email));

            $code = verificationCode(6);
            AdminPasswordReset::create(['email' => $admin->email, 'token' => $code, 'status' => Status::ENABLE, 'created_at' => Carbon::now()]);

            $ipInfo  = getIpInfo();
            $browser = osBrowser();
            notify($admin, 'PASS_RESET_CODE', [
                'code'             => $code,
                'operating_system' => $browser['os_platform'],
                'browser'          => $browser['browser'],
                'ip'               => $ipInfo['ip'],
                'time'             => $ipInfo['time'],
            ], ['email'], false);
        }

        $request->session()->put('pass_res_mail', $request->email);
        $notify[] = ['success', 'If this email belongs to an admin account, a code has been sent to it.'];
        return to_route('admin.password.code.verify')->withNotify($notify);
    }

    public function codeVerify(Request $request)
    {
        $email = $request->session()->get('pass_res_mail');
        if (!$email) {
            $notify[] = ['error', 'Your session expired. Please enter your email again.'];
            return to_route('admin.password.reset')->withNotify($notify);
        }

        return Inertia::render('Admin/Auth/Code', [
            'logo'  => siteLogo(),
            'email' => showEmailAddress($email),
            'urls'  => ['submit' => route('admin.password.verify.code'), 'again' => route('admin.password.reset'), 'login' => route('admin.login')],
        ])->withViewData(['pageTitle' => 'Verify Code']);
    }

    /**
     * A wrong code stays on the page (it used to drop you back at the login
     * page). After MAX_ATTEMPTS wrong codes the code is cancelled: the route
     * is throttled per IP, this counts per email.
     */
    public function verifyCode(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $email = $request->session()->get('pass_res_mail');
        if (!$email) {
            $notify[] = ['error', 'Your session expired. Please enter your email again.'];
            return to_route('admin.password.reset')->withNotify($notify);
        }

        $code = str_replace(' ', '', $request->code);
        if (!self::validReset($email, $code)) {
            Cache::add(self::attemptsKey($email), 0, now()->addMinutes(self::CODE_MINUTES));
            if ((int) Cache::increment(self::attemptsKey($email)) >= self::MAX_ATTEMPTS) {
                AdminPasswordReset::where('email', $email)->where('status', Status::ENABLE)->update(['status' => Status::DISABLE]);
                Cache::forget(self::attemptsKey($email));
                $request->session()->forget('pass_res_mail');
                $notify[] = ['error', 'Too many wrong codes. Please request a new one.'];
                return to_route('admin.password.reset')->withNotify($notify);
            }
            return back()->withErrors(['code' => 'Verification code does not match']);
        }

        Cache::forget(self::attemptsKey($email));
        $notify[] = ['success', 'You can change your password'];
        return to_route('admin.password.reset.form', $code)->withNotify($notify);
    }

    /**
     * The open, unexpired reset for the email this browser asked about and this
     * code. Lookups used to go by the 6-digit code alone, never expired and were
     * not rate-limited, so the admin password could be reset by guessing.
     */
    public static function validReset(?string $email, ?string $code): ?AdminPasswordReset
    {
        if (!$email || !$code) {
            return null;
        }

        return AdminPasswordReset::where('email', $email)
            ->where('token', $code)
            ->where('status', Status::ENABLE)
            ->where('created_at', '>=', now()->subMinutes(self::CODE_MINUTES))
            ->latest('id')
            ->first();
    }

    public static function attemptsKey(string $email): string
    {
        return 'admin-password-reset-attempts:' . sha1(strtolower($email));
    }
}
