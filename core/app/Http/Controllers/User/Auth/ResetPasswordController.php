<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, $token = null)
    {
        $email = session('fpass_email');

        if (!ForgotPasswordController::validReset($email, $token)) {
            $notify[] = ['error', 'Invalid token'];
            return to_route('user.password.request')->withNotify($notify);
        }

        return Inertia::render('Auth/ResetPassword', [
            'token'          => $token,
            'securePassword' => (bool) gs('secure_password'),
            'urls'           => ['submit' => route('user.password.update')],
        ])->withViewData(['pageTitle' => 'Reset Password']);
    }

    /**
     * The code must belong to the email this browser verified it for. This
     * used to look the reset up by the 6-digit code alone (any email), so a
     * guessed code changed whichever account it happened to belong to.
     */
    public function reset(Request $request)
    {
        $request->validate($this->rules());

        $email = $request->session()->get('fpass_email');
        $reset = ForgotPasswordController::validReset($email, $request->token);
        if (!$reset) {
            $notify[] = ['error', 'Invalid verification code'];
            return to_route('user.password.request')->withNotify($notify);
        }

        $user = User::where('email', $reset->email)->first();
        if (!$user) {
            $notify[] = ['error', 'The account could not be found'];
            return to_route('user.password.request')->withNotify($notify);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // A code works once.
        PasswordReset::where('email', $reset->email)->delete();
        $request->session()->forget(['fpass_email', 'pass_res_mail']);

        $userIpInfo = getIpInfo();
        $userBrowser = osBrowser();
        notify($user, 'PASS_RESET_DONE', [
            'operating_system' => @$userBrowser['os_platform'],
            'browser' => @$userBrowser['browser'],
            'ip' => @$userIpInfo['ip'],
            'time' => @$userIpInfo['time']
        ],['email']);

        $notify[] = ['success', 'Password changed successfully'];
        return to_route('user.login')->withNotify($notify);
    }

    protected function rules()
    {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }
        return [
            'token' => 'required',
            'password' => ['required','confirmed',$passwordValidation],
        ];
    }
}
