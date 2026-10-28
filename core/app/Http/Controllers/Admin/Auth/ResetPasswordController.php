<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminPasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/** Admin password reset, step 3 (Pages/Admin/Auth/Reset.tsx): the new password. */
class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, $token)
    {
        if (!ForgotPasswordController::validReset($request->session()->get('pass_res_mail'), $token)) {
            $notify[] = ['error', 'This code is no longer valid. Please request a new one.'];
            return to_route('admin.password.reset')->withNotify($notify);
        }

        return Inertia::render('Admin/Auth/Reset', [
            'logo'  => siteLogo(),
            'token' => (string) $token,
            'urls'  => ['submit' => route('admin.password.change'), 'login' => route('admin.login')],
        ])->withViewData(['pageTitle' => 'Account Recovery']);
    }

    /** At least 8 characters, as on the admin's own password page (it was 4 here). */
    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $reset = ForgotPasswordController::validReset($request->session()->get('pass_res_mail'), $request->token);
        $admin = $reset ? Admin::where('email', $reset->email)->first() : null;
        if (!$admin) {
            $notify[] = ['error', 'This code is no longer valid. Please request a new one.'];
            return to_route('admin.password.reset')->withNotify($notify);
        }

        $admin->password = Hash::make($request->password);
        $admin->save();
        AdminPasswordReset::where('email', $reset->email)->where('status', Status::ENABLE)->update(['status' => Status::DISABLE]);
        Cache::forget(ForgotPasswordController::attemptsKey($reset->email));
        $request->session()->forget('pass_res_mail');

        $ipInfo  = getIpInfo();
        $browser = osBrowser();
        notify($admin, 'PASS_RESET_DONE', [
            'operating_system' => $browser['os_platform'],
            'browser'          => $browser['browser'],
            'ip'               => $ipInfo['ip'],
            'time'             => $ipInfo['time'],
        ], ['email'], false);

        $notify[] = ['success', 'Password changed. You can sign in now.'];
        return to_route('admin.login')->withNotify($notify);
    }
}
