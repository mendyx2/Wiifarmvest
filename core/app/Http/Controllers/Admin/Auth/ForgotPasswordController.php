<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\AdminPasswordReset;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{

    public function showLinkRequestForm()
    {
        $pageTitle = 'Account Recovery';
        return view('admin.auth.passwords.email', compact('pageTitle'));
    }

    public function sendResetCodeEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        if(!verifyCaptcha()){
            $notify[] = ['error','Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $admin = Admin::where('email', $request->email)->first();
        if (!$admin) {
            $notify[] = ['error','No admin account found with this email'];
            return back()->withNotify($notify);
        }

        // Only the newest code works.
        AdminPasswordReset::where('email', $admin->email)->where('status', Status::ENABLE)->update(['status' => Status::DISABLE]);

        $code = verificationCode(6);
        $adminPasswordReset = new AdminPasswordReset();
        $adminPasswordReset->email = $admin->email;
        $adminPasswordReset->token = $code;
        $adminPasswordReset->created_at = Carbon::now();
        $adminPasswordReset->save();

        $adminIpInfo = getIpInfo();
        $adminBrowser = osBrowser();
        notify($admin, 'PASS_RESET_CODE', [
            'code' => $code,
            'operating_system' => $adminBrowser['os_platform'],
            'browser' => $adminBrowser['browser'],
            'ip' => $adminIpInfo['ip'],
            'time' => $adminIpInfo['time']
        ],['email'],false);

        $email = $admin->email;
        session()->put('pass_res_mail',$email);

        return to_route('admin.password.code.verify');
    }

    public function codeVerify(){
        $pageTitle = 'Verify Code';
        $email = session()->get('pass_res_mail');
        if (!$email) {
            $notify[] = ['error','Oops! session expired'];
            return to_route('admin.password.reset')->withNotify($notify);
        }
        return view('admin.auth.passwords.code_verify', compact('pageTitle','email'));
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => 'required']);
        $code  = str_replace(' ', '', $request->code);
        $reset = self::validReset(session()->get('pass_res_mail'), $code);

        if (!$reset) {
            $notify[] = ['error', 'Verification code does not match'];
            return to_route('admin.login')->withNotify($notify);
        }

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
            ->where('created_at', '>=', now()->subMinutes(60))
            ->latest('id')
            ->first();
    }
}
