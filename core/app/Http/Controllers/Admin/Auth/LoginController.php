<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Support\CaptchaPresenter;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laramin\Utility\Onumoti;

/**
 * Admin sign-in (Pages/Admin/Auth/Login.tsx). Laravel's AuthenticatesUsers
 * does the work: the username field, five tries a minute per username and
 * IP, and the "these credentials do not match" error on the username field.
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    /** Where to go after signing in, unless a page asked for sign-in first. */
    public $redirectTo = 'admin/dashboard';

    public function showLoginForm()
    {
        return Inertia::render('Admin/Auth/Login', [
            'logo'    => siteLogo(),
            'captcha' => CaptchaPresenter::props(),
            'urls'    => ['login' => route('admin.login'), 'forgot' => route('admin.password.reset')],
        ])->withViewData(['pageTitle' => 'Admin Login']);
    }

    protected function guard()
    {
        return auth()->guard('admin');
    }

    public function username()
    {
        return 'username';
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (!verifyCaptcha()) {
            throw ValidationException::withMessages(['captcha' => 'Invalid captcha provided']);
        }

        // Checked before the licence call below, so a stream of guesses is
        // turned away here instead of each one calling out to the vendor.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $this->checkLicence();

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * The script vendor's licence check (laramin/utility), kept as it was:
     * it posts the purchase code and site address to the vendor, can switch
     * on maintenance mode from their answer, and stops the sign-in when they
     * do not answer. Whether to keep it is the owner's call (notes Step 118).
     */
    protected function checkLicence(): void
    {
        try {
            Onumoti::getData();
        } catch (ValidationException) {
            // It used to say only "Something went wrong".
            throw ValidationException::withMessages(['username' => 'The licence server could not be reached, so sign-in is paused. Please try again in a minute.']);
        }
    }

    /**
     * A deactivated account cannot sign in. Otherwise the next page opens
     * with a full page load: the session (and its CSRF token) is new, and it
     * may be a page that is not React.
     */
    protected function authenticated(Request $request, $admin)
    {
        if ($admin->is_active === false) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['username' => 'This admin account has been deactivated.']);
        }

        return Inertia::location(redirect()->intended($this->redirectPath())->getTargetUrl());
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.login');
    }
}
