<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Project;
use App\Models\Matrix;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Services\MatrixPlacementService2;
use DomainException;
use Inertia\Inertia;

class DistributorController extends Controller
{
    public function __construct(private MatrixPlacementService2 $matrixService2)
    {
    }

    public function showForm(Request $request)
    {
        $sponsor = auth()->user();
        $visa    = (float) ($sponsor->visa ?? 0);

        $info      = json_decode(json_encode(getIpInfo()), true);
        $ipCountry = @implode(',', $info['code']) ?: null;

        $countries = collect((array) json_decode(file_get_contents(resource_path('views/partials/country.json'))))
            ->map(fn ($c, $code) => ['code' => (string) $code, 'name' => $c->country, 'dial' => (string) $c->dial_code])
            // Nigeria first, as on the old page
            ->sortBy(fn ($c) => $c['name'] === 'Nigeria' ? 0 : 1)
            ->values();

        $defaultCountry = $countries->firstWhere('code', $ipCountry) ?? $countries->firstWhere('name', 'Nigeria') ?? $countries->first();

        return Inertia::render('User/Distributor', [
            'sponsorUsername' => $sponsor->username,
            'visa'            => showAmount($visa),
            'projects'        => Project::active()->get()->map(fn (Project $p) => [
                'id'         => $p->id,
                'title'      => $p->title,
                'amount'     => showAmount($p->amount),
                'affordable' => $visa >= (float) $p->amount,
            ])->values(),
            'countries'       => $countries,
            'defaultCountry'  => $defaultCountry['code'] ?? '',
            // The binary list links here with ?parent=USERNAME&position=left|right for an open slot.
            'prefill'         => [
                'parent'   => (string) $request->query('parent', ''),
                'position' => in_array($request->query('position'), ['left', 'right'], true) ? $request->query('position') : '',
            ],
            'urls'            => [
                'store' => route('user.distributor.store'),
                'check' => route('user.distributor.check'),
                // Free country → state → city lookup, called from the browser as before.
                'geo'   => 'https://countriesnow.space/api/v0.1/countries',
            ],
        ])->withViewData(['pageTitle' => 'Add Distributor']);
    }

    /**
     * Live checks while the sponsor types: is the username free, does the
     * parent exist and which of its two slots are open. Replaces the
     * check-visa call (the page knows the VISA balance) and the shared parent
     * check, which only said "found", not whether the slot was free.
     */
    public function check(Request $request)
    {
        $result = [];

        if (is_string($username = $request->query('username')) && $username !== '') {
            $result['usernameTaken'] = User::where('username', $username)->exists();
        }

        // Warnings only: an email or phone may be shared by several members.
        if (is_string($email = $request->query('email')) && $email !== '') {
            $result['emailUsed'] = User::where('email', $email)->exists();
        }
        if (is_string($mobile = $request->query('mobile')) && $mobile !== '') {
            $result['mobileUsed'] = User::where('mobile', $mobile)->where('dial_code', (string) $request->query('dial'))->exists();
        }

        if (is_string($parent = $request->query('parent')) && $parent !== '') {
            $parentUser = User::where('username', $parent)->where('status', Status::USER_ACTIVE)->first();
            $matrix     = $parentUser ? Matrix::where('user_id', $parentUser->id)->where('stage_id', 1)->where('is_active', 1)->first() : null;
            $result['parent'] = $matrix
                ? ['found' => true, 'left' => (int) $matrix->left === 0, 'right' => (int) $matrix->right === 0]
                : ['found' => false, 'left' => false, 'right' => false];
        }

        return response()->json($result);
    }

    public function store(Request $request)
    { 
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $countryData  = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));
 
        $request->validate([
            'username'    => 'required|unique:users|min:4|regex:/^[A-Za-z0-9_]+$/',
            'email'       => 'required|email',
            'password'    => ['required', 'confirmed', $passwordValidation],
            'firstname'   => 'required|string|min:3|max:50',
            'lastname'    => 'required|string|min:3|max:50',
            'country_code'=> 'required|in:' . $countryCodes,
            'country'     => 'required|in:' . $countries,
            'mobile_code' => 'required|in:' . $mobileCodes,
            'mobile'      => 'required',
            'address'     => 'required|string|max:255',
            'state'       => 'required|string|max:50',
            'city'        => 'required|string|max:50',
            'project_id'  => 'required|integer|exists:projects,id',
            'referBy'     => 'required|min:3|max:40|regex:/^[A-Za-z0-9_]+$/|exists:users,username',
            
            'parent'      => 'nullable|string|min:3|max:30',
            
            // Only with a parent: without one, the first open slot under the sponsor is used.
            'position'    => 'nullable|required_with:parent|in:left,right',
        ], [
            'username.regex'  => 'Username may only contain letters, numbers and underscores.',
            'project_id.required' => 'Please select a project.',
            'referBy.exists' => 'The sponsor username does not exists.',
            
        ]);
        $payerId = auth()->id();
        $project = Project::findOrFail($request->project_id);
        $cost    = (float) $project->amount;
        $sponsor = User::where('username', $request->referBy)->firstOrFail();

        try {
            $newUser = DB::transaction(function () use ($request, $payerId, $sponsor, $project, $cost) {
                // Lock the paying account first: two quick submits used to both pass the
                // VISA check, which ran on the copy loaded at the start of the request.
                $payer = User::lockForUpdate()->findOrFail($payerId);
                if ((float) $payer->visa < $cost) {
                    throw new DomainException('Insufficient VISA wallet balance. You need ' . showAmount($cost) . ' but have ' . showAmount($payer->visa) . '.');
                }

                [$parentId, $position] = $this->placement($request, $sponsor);

                $trx = getTrx(20);
                $newUser = new User();
                $newUser->username     = $request->username;
                $newUser->email        = $request->email;
                $newUser->password     = Hash::make($request->password);
                $newUser->firstname    = $request->firstname;
                $newUser->lastname     = $request->lastname;
                $newUser->country_code = $request->country_code;
                $newUser->country_name = $request->country;
                $newUser->dial_code    = $request->mobile_code ?? 234;
                $newUser->mobile       = $request->mobile;
                $newUser->address      = $request->address;
                $newUser->state        = $request->state;
                $newUser->city         = $request->city;
                $newUser->ref_by       = $sponsor->id;
                $newUser->pos_id       = 1;
                $newUser->trx          = $trx;
                $newUser->section      = 1;
                $newUser->project_id   = $project->id;
                $newUser->kv = Status::NO;
                $newUser->ev = Status::YES;
                $newUser->sv = Status::YES;
                $newUser->ts = Status::DISABLE;
                $newUser->tv = Status::ENABLE;
                $newUser->status = Status::USER_ACTIVE;
                $newUser->profile_complete = Status::YES;
                $newUser->keyed_in_by = $payer->id;
                $newUser->save();

                $matrix            = new Matrix();
                $matrix->user_id   = $newUser->id;
                $matrix->parent_id = $parentId;
                $matrix->position  = $position;
                $matrix->stage_id  = 1;
                $matrix->save();

                // Link the new user into the parent's slot so the tree is traversable.
                Matrix::where('user_id', $parentId)->where('stage_id', 1)->update([$position => $newUser->id]);

                // Debit the VISA wallet first: cash back is only allocated after this deduction.
                $payer->visa -= $cost;
                $payer->save();
                newTransaction($payer, 'Registered distributor: ' . $newUser->username . ' on ' . $project->title, 'distributor_registration', $cost, '-', $trx, 1, 0, 'visa');

                directBonus($newUser, 'Direct bonus gotten from username: ' . $newUser->username . 'Subscribing to ' . $project->title, $project->direct_commission, $trx);
                // The indirect bonus starts from the sponsor's referrer.
                indirectBonus(User::find($newUser->ref_by), $project->pv, 'Indirect bonus gotten from username: ' . $newUser->username . ' Subscribing to ' . $project->title, $trx);
                updatePV($newUser, $newUser->username . ' Subscribed to ' . $project->title . ' Project.', $project->pv);
                processCashBack($newUser, $project, $trx, $project->title . ' subscription cash back to Repurchase wallet');

                // Key-In Bonus: 2% of the registration PV at ₦600/PV, to the member who keyed it in
                // (e.g. 450 PV Mega = ₦5,400).
                $keyInBonus = round(((float) $project->pv * 0.02 * 600), 2);
                if ($keyInBonus > 0) {
                    $payer->increment('key_in_bonus', $keyInBonus);
                    $payer->refresh();
                    balance_TransactionReturn($payer->id, 'Key-in bonus: registered ' . $newUser->username, $keyInBonus, 'key_in_bonus', $trx, $payer->key_in_bonus);

                    // Debit from the isolated Member Compensation Reserve Ledger
                    try {
                        app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                            $keyInBonus,
                            $trx,
                            'Key-in bonus to ' . $payer->username . ' for registering ' . $newUser->username,
                            'CRP',
                            'key_in_bonus',
                            $payer->id
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit key-in bonus: ' . $e->getMessage());
                    }
                }

                // Blueprint: Strategic Reserve — Global Share Pool (1.25%)
                try {
                    \App\Models\GlobalPoolLedger::accrue((float) $project->pv);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[GlobalPool] Failed to accrue on registration: ' . $e->getMessage());
                }

                $adminNotification            = new AdminNotification();
                $adminNotification->user_id   = $newUser->id;
                $adminNotification->title     = 'New distributor registered by ' . $payer->username;
                $adminNotification->click_url = urlPath('admin.users.detail', $newUser->id);
                $adminNotification->save();

                return $newUser;
            });
        } catch (DomainException $e) {
            return back()->withNotify([['error', $e->getMessage()]]);
        } catch (\Throwable $e) {
            // Logged for us; the member gets a plain message. This used to put
            // the whole exception (file paths, SQL) in the toast.
            report($e);
            return back()->withNotify([['error', 'Something went wrong. Please try again.']]);
        }

        return back()->withNotify([['success', 'Distributor ' . $newUser->username . ' registered successfully! ' . showAmount($cost) . ' debited from your VISA wallet.']]);
    }

    /**
     * Where the new member goes in the binary tree: the chosen parent's
     * left/right slot, or the first open slot under the sponsor. The slot's
     * matrix row is locked so two registrations cannot take the same slot.
     *
     * @return array{0: int, 1: string} [parent user id, 'left'|'right']
     * @throws DomainException
     */
    private function placement(Request $request, User $sponsor): array
    {
        if ($request->filled('parent')) {
            $parentUser = User::where('username', $request->parent)->first();
            if (!$parentUser) {
                throw new DomainException('Parent username not found in the network.');
            }
            // Binary placement may not cross into another sponsor's tree.
            if (!checkDownline($sponsor->id, $parentUser->id)) {
                throw new DomainException('Parent must be under Sponsor tree. No Tree crossing');
            }

            $slot = Matrix::where('user_id', $parentUser->id)->where('stage_id', 1)->where('is_active', 1)->lockForUpdate()->first();
            if (!$slot || (int) $slot->{$request->position} !== 0) {
                throw new DomainException('Parent\'s ' . $request->position . ' position is already taken.');
            }

            return [$parentUser->id, $request->position];
        }

        // No parent given: the first open slot under the sponsor.
        $sponsorMatrix = Matrix::where('user_id', $sponsor->id)->where('stage_id', 1)->first();
        if (!$sponsorMatrix) {
            throw new DomainException('Your account is not active. Please contact support. ERROR 205');
        }

        $placement = $this->matrixService2->findDownline_reg($sponsorMatrix, 1);
        if (!$placement) {
            throw new DomainException('No open slot found in your downline. Please specify a parent and position manually.ERROR 206');
        }

        $slot     = Matrix::lockForUpdate()->find($placement->id);
        $position = (int) $slot->left === 0 ? 'left' : ((int) $slot->right === 0 ? 'right' : null);
        if (!$position) {
            throw new DomainException('That slot was just taken. Please try again.');
        }

        return [(int) $slot->user_id, $position];
    }
}
