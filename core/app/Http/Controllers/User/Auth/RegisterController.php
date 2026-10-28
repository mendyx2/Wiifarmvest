<?php

namespace App\Http\Controllers\User\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\Intended;
use App\Models\Matrix;
use App\Models\Project;
use App\Models\Rmatrix;
use App\Models\User;
use App\Support\CaptchaPresenter;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * Sign-up. The new member is saved with a *pending* place in the tree
 * (rmatrices); the real placement and the bonuses happen when they pay for
 * their project on the next page (user.data → Gateway\Paystack).
 */
class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        $refUser = null;
        if ($request->ref) {
            $refUser = User::where('username', $request->ref)->first();
            if (!$refUser) {
                $notify[] = ['error', 'Invalid Referral link.'];
                return redirect()->route('home')->withNotify($notify);
            }
            if ((int) $refUser->status !== Status::USER_ACTIVE) {
                $notify[] = ['error', 'Your Referral is not active. pls contact  Admin or your Referral'];
                return redirect()->route('home')->withNotify($notify);
            }
        }

        $info      = json_decode(json_encode(getIpInfo()), true);
        $ipCountry = @implode(',', $info['code']) ?: null;
        $countries = collect((array) json_decode(file_get_contents(resource_path('views/partials/country.json'))))
            ->map(fn ($c, $code) => ['code' => (string) $code, 'name' => $c->country, 'dial' => (string) $c->dial_code])
            ->sortBy(fn ($c) => $c['name'] === 'Nigeria' ? 0 : 1) // Nigeria first, as before
            ->values();
        $default = $countries->firstWhere('code', $ipCountry) ?? $countries->firstWhere('name', 'Nigeria') ?? $countries->first();

        Intended::identifyRoute();

        return Inertia::render('Auth/Register', [
            'logo'           => siteLogo(),
            'heading'        => [
                'title' => @getContent('register.content', true)->data_values->heading ?: 'Open Your Account',
                'sub'   => @getContent('register.content', true)->data_values->sub_heading ?: 'Secure, fast and fully encrypted registration',
            ],
            'referrer'       => $refUser?->username,
            'projects'       => Project::active()->get()->map(fn (Project $p) => ['id' => $p->id, 'title' => $p->title, 'amount' => showAmount($p->amount)])->values(),
            'countries'      => $countries,
            'defaultCountry' => $default['code'] ?? '',
            'open'           => (bool) gs('registration'),
            'mustAgree'      => (bool) gs('agree'),
            'securePassword' => (bool) gs('secure_password'),
            'captcha'        => CaptchaPresenter::props(),
            'urls'           => [
                'submit'    => route('user.register'),
                'check'     => route('user.register.check'),
                'login'     => route('user.login'),
                'agreement' => route('agreement'),
                'home'      => route('home'),
                'geo'       => 'https://countriesnow.space/api/v0.1/countries',
            ],
        ])->withViewData(['pageTitle' => 'Register']);
    }

    /**
     * Live checks while the visitor types (guest, throttled). One JSON answer
     * instead of the four old endpoints, three of which sent back HTML.
     */
    public function check(Request $request)
    {
        $result = [];

        if (is_string($v = $request->query('username')) && $v !== '') {
            $result['usernameTaken'] = User::where('username', $v)->exists();
        }
        if (is_string($v = $request->query('email')) && $v !== '') {
            $result['emailUsed'] = User::where('email', $v)->exists();
        }
        if (is_string($v = $request->query('mobile')) && $v !== '') {
            $result['mobileUsed'] = User::where('mobile', $v)->where('dial_code', (string) $request->query('dial'))->exists();
        }
        if (is_string($v = $request->query('referral')) && $v !== '') {
            $result['referralFound'] = User::where('username', $v)->where('status', Status::USER_ACTIVE)->exists();
        }
        if (is_string($v = $request->query('parent')) && $v !== '') {
            $parent = User::where('username', $v)->where('status', Status::USER_ACTIVE)->first();
            $matrix = $parent ? Matrix::where('user_id', $parent->id)->where('stage_id', 1)->where('is_active', 1)->first() : null;
            $result['parent'] = $matrix
                ? ['found' => true, 'left' => (int) $matrix->left === 0, 'right' => (int) $matrix->right === 0]
                : ['found' => false, 'left' => false, 'right' => false];
        }

        return response()->json($result);
    }

    public function register(Request $request)
    {
        if (!gs('registration')) {
            // This used to answer with a bare redirect and no message.
            return back()->withNotify([['error', 'Registration is currently disabled.']]);
        }

        $this->validator($request->all())->validate();

        if (!verifyCaptcha()) {
            return back()->withNotify([['error', 'Invalid captcha provided']]);
        }

        try {
            $user = DB::transaction(fn () => $this->createMember($request));
        } catch (DomainException $e) {
            return back()->withNotify([['error', $e->getMessage()]]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withNotify([['error', 'Please refresh your browser and try again']]);
        }

        auth()->login($user);
        $request->session()->regenerate();

        // A full page load: the next step, paying for the project, is a Blade page.
        return Inertia::location(route('user.home'));
    }

    protected function validator(array $data)
    {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $countryData  = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));
        $username     = 'regex:/^[A-Za-z0-9_]+$/';

        // The old controller checked these formats by hand after validating and
        // answered with toasts; they are ordinary rules now.
        return Validator::make($data, [
            'firstname'    => 'required',
            'lastname'     => 'required',
            'username'     => ['required', 'unique:users', 'min:6', $username],
            'country_code' => 'required|in:' . $countryCodes,
            'country'      => 'required|in:' . $countries,
            'mobile_code'  => 'required|in:' . $mobileCodes,
            'email'        => 'required|string|email',
            'password'     => ['required', 'confirmed', $passwordValidation],
            'referBy'      => ['nullable', 'string', 'max:40', $username],
            'parent'       => ['nullable', 'string', 'max:40', $username],
            'position'     => 'nullable|required_with:parent|in:left,right',
            'project_id'   => 'nullable|integer|exists:projects,id',
            'captcha'      => 'sometimes|required',
            'agree'        => gs('agree') ? 'accepted' : 'nullable',
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required'  => 'The last name field is required',
            'username.regex'     => 'Username can contain only Big/small letters, numbers and underscore.',
            'referBy.regex'      => 'No special character or space in the referral username.',
            'parent.regex'       => 'No special character or space in Parent name.',
        ]);
    }

    /** @throws DomainException */
    private function createMember(Request $request): User
    {
        // Sponsor: the referral username, or member #1 when there is none.
        $sponsorId = 1;
        if ($request->filled('referBy')) {
            $sponsor = User::where('username', $request->referBy)->first();
            if (!$sponsor) {
                throw new DomainException('The Referral is not in the system.');
            }
            $sponsorId = $sponsor->id;
        }

        $parentId = 0;
        $position = 0;
        if ($request->filled('parent')) {
            $parent = User::where('username', $request->parent)->first();
            if (!$parent) {
                throw new DomainException('Parent is not in the network..');
            }
            // Binary placement may not cross into another sponsor's tree.
            if ($request->filled('referBy') && !checkDownline($sponsorId, $parent->id)) {
                throw new DomainException('Parent must be under Sponsor tree. No Tree crossing');
            }
            $slotFree = Matrix::where('user_id', $parent->id)->where($request->position, 0)->where('stage_id', 1)->where('is_active', 1)->exists();
            if (!$slotFree) {
                throw new DomainException('Chose another position. Parent ' . $request->position . ' is taken');
            }
            $parentId = $parent->id;
            $position = $request->position;
        }

        $user = new User();
        if ($parentId) {
            $user->parent   = $request->parent;
            $user->position = $request->position;
        }
        $user->country_code = $request->country_code;
        $user->mobile       = $request->mobile;
        $user->address      = $request->address;
        $user->city         = $request->city;
        $user->state        = $request->state;
        $user->country_name = $request->country;
        $user->dial_code    = $request->mobile_code;
        $user->section      = 1;
        $user->trx          = getTrx(20);
        $user->username     = $request->username;
        $user->ref_by       = $sponsorId;
        $user->pos_id       = 1;
        $user->email        = $request->email;
        $user->firstname    = $request->firstname;
        $user->lastname     = $request->lastname;
        $user->password     = Hash::make($request->password);
        $user->kv           = Status::NO;
        $user->ev           = Status::YES;
        $user->sv           = Status::YES;
        $user->ts           = Status::DISABLE;
        $user->tv           = Status::ENABLE;
        $user->status       = Status::USER_ACTIVE;
        $user->project_id   = $request->project_id ?: null;
        $user->save();

        // Pending place in the tree, confirmed when the member pays.
        $rmatrix            = new Rmatrix();
        $rmatrix->user_id   = $user->id;
        $rmatrix->parent_id = $parentId;
        $rmatrix->position  = $position;
        $rmatrix->stage_id  = 1;
        $rmatrix->save();

        return $user;
    }
}
