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

class DistributorController extends Controller
{

    public function __construct(MatrixPlacementService2 $matrixService2)
    {
        
        $this->matrixService2 = $matrixService2;

    }

    public function showForm(Request $request)
    {
        $pageTitle = 'Add Distributor';
        $sponsor   = auth()->user();
        $projects  = Project::active()->get();

        $info       = json_decode(json_encode(getIpInfo()), true);
        $mobileCode = @implode(',', $info['code']);
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        // Pre-fill support: binary-list passes ?parent=USERNAME&position=left|right
        // so the sponsor can register directly into a specific open slot.
        $preParent   = $request->query('parent',   '');
        $prePosition = in_array($request->query('position'), ['left', 'right'], true)
                       ? $request->query('position')
                       : '';
 
        return view('Template::user.distributor', compact(
            'pageTitle', 'sponsor', 'projects', 'mobileCode', 'countries',
            'preParent', 'prePosition'
        ));
    }

    public function checkVisaBalance(Request $request)
    {
        $request->validate(['project_id' => 'required|integer|exists:projects,id']);

        $project = Project::findOrFail($request->project_id);
        $user    = auth()->user();
        $balance = (float) ($user->visa ?? 0);
        $cost    = (float) $project->amount;

        return response()->json([
            'success'    => $balance >= $cost,
            'balance'    => $balance,
            'cost'       => $cost,
            'balance_fmt'=> showAmount($balance),
            'cost_fmt'   =>  showAmount($cost),
            'project'    => $project->title,
        ]);
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
            
            'position'    => 'required|in:left,right',
        ], [
            'username.regex'  => 'Username may only contain letters, numbers and underscores.',
            'project_id.required' => 'Please select a project.',
            'referBy.exists' => 'The sponsor username does not exists.',
            
        ]);
        //'position'    => 'nullable|in:left,right',

        $user = auth()->user();

        $project = Project::findOrFail($request->project_id);

        // Visa balance check
        $visaBalance = (float) ($user->visa ?? 0);
        $cost        = (float) $project->amount;

        if ($visaBalance < $cost) {
            $notify[] = ['error', 'Insufficient VISA wallet balance. You need ' . gs('cur_sym') . showAmount($cost, currencyFormat: false) . ' but have ' . gs('cur_sym') . showAmount($visaBalance, currencyFormat: false) . '.'];
            return back()->withNotify($notify)->withInput($request->except('password', 'password_confirmation'));
        }

        

        // Resolve referrer
        $referrerId = $user->id;
        if ($request->filled('referBy')) {
            $refUser = User::where('username', $request->referBy)->first();
            if (!$refUser) {
                $notify[] = ['error', 'Referral username not found in the system.'];
                return back()->withNotify($notify)->withInput($request->except('password', 'password_confirmation'));
            }
            $sponsor = $refUser;
            $referrerId = $refUser->id;
        }else {
            $sponsor = auth()->user();
        }

        // check is the placement username is your downline as Binary matrix does not allow board crossing.

        //checkDownline_new(Matrix $sponsor_matrix, Matrix $user_placement_matrix)
        if ($request->filled('parent')) {
            $parent = User::where('username', $request->parent)->first();
            
            if ($parent) {
                
                if(!checkDownline($sponsor->id,  $parent->id)){
                    $notify[] = ['error', 'Parent must be under Sponsor tree. No Tree crossing'];
                    return back()->withNotify($notify)->withInput($request->all());
                }
            }
        }

        //dd(checkDownline($sponsor->id,  $parent->id));
 
        DB::beginTransaction();
        try {
            $parentId = 0;
            $position = 0;

            // Validate parent placement if provided
            if ($request->filled('parent')) {
                $parentUser = User::where('username', $request->parent)->first();
                if (!$parentUser) {
                    $notify[] = ['error', 'Parent username not found in the network.'];
                    return back()->withNotify($notify)->withInput($request->except('password', 'password_confirmation'));
                }

                $parentMatrix = Matrix::where('user_id', $parentUser->id)
                    ->where($request->position, 0)
                    ->where('stage_id', 1)
                    ->where('is_active', 1)
                    ->first();
                // when you go live. remmember to check if the parent has an active matrix and the position is available
                if (!$parentMatrix) {
                    $notify[] = ['error', 'Parent\'s ' . $request->position . ' position is already taken.'];
                    return back()->withNotify($notify)->withInput($request->except('password', 'password_confirmation'));
                }

                $parentId = $parentUser->id;
                $position = $request->position;
            } else {
                // No parent specified — BFS through sponsor's downline to find first open slot
                $sponsorMatrix = Matrix::where('user_id', $sponsor->id)
                    ->where('stage_id', 1)
                    ->first();
                    //->where('is_active', 1) this will be revisited. for now.. let me rest

                if (!$sponsorMatrix) {
                    $notify[] = ['error', 'Your account is not active. Please contact support. ERROR 205'];
                    return back()->withNotify($notify)->withInput($request->except('password', 'password_confirmation'));
                }

                $placement = $this->matrixService2->findDownline_reg($sponsorMatrix, 1);

                if (!$placement) {
                    $notify[] = ['error', 'No open slot found in your downline. Please specify a parent and position manually.ERROR 206'];
                    return back()->withNotify($notify)->withInput($request->except('password', 'password_confirmation'));
                }

                $parentId = $placement->user_id;
                $position = ((int) $placement->left == 0) ? 'left' : 'right';
            }

            // Create the new user
            $trx  = getTrx(20);   
            $newUser = new User();
            $newUser->username     = $request->username;
            $newUser->email        = $request->email;
            $newUser->password     = Hash::make($request->password);
            $newUser->firstname    = $request->firstname;
            $newUser->lastname     = $request->lastname;
            $newUser->country_code = $request->country_code;
            $newUser->country_name = $request->country;
            //$newUser->mobile_code  = $request->mobile_code ?? null;
            $newUser->dial_code    = $request->mobile_code ?? 234; 
            $newUser->mobile       = $request->mobile;
            $newUser->address      = $request->address;
            $newUser->state        = $request->state;
            $newUser->city         = $request->city;
            $newUser->ref_by       = $referrerId;
            $newUser->pos_id       = 1;
            $newUser->trx          = $trx;
            $newUser->section      = $parentId ? 1 : 1;
            $newUser->project_id   = $project->id;
            //gs('kv') ? Status::NO : Status::YES;
            $newUser->kv = Status::NO;
            $newUser->ev = Status::YES;
            $newUser->sv = Status::YES;
            $newUser->ts = Status::DISABLE;
            $newUser->tv = Status::ENABLE;
            $newUser->status = Status::USER_ACTIVE;
            $newUser->profile_complete = Status::YES;
            $newUser->save();
 
            // Place in matrix
            $matrix            = new Matrix();
            $matrix->user_id   = $newUser->id;
            $matrix->parent_id = $parentId;
            $matrix->position  = $position;
            $matrix->stage_id  = 1;
            $matrix->save();

            // Link new user into the parent's left/right slot so the tree is traversable
            if ($parentId && in_array($position, ['left', 'right'])) {
                Matrix::where('user_id', $parentId)
                    ->where('stage_id', 1)
                    ->update([$position => $newUser->id]);
            }

            // Debit sponsor's visa wallet FIRST — cash back is only allocated after this deduction
            $user = auth()->user()->fresh();
            $user->visa -= $cost;
            $user->save();

            // Record debit transaction for sponsor's visa wallet
            $detailsss = 'Registered distributor: ' . $newUser->username . ' on ' . $project->title;
            $remark = 'distributor_registration';
            newTransaction($user, $detailsss, $remark, $cost, '-', $trx , 1, 0, 'visa');

            $newUser->keyed_in_by = $user->id;
            $newUser->save();
            $details = 'Direct bonus gotten from username: '.$newUser->username. 'Subscribing to '.$project->title;

            //Next is process Direct bonus
        
            //dd($project->direct_commission);
            directBonus($newUser,  $details, $project->direct_commission, $trx);
            // Indirect bonus is inside the direct bonus
            // the indirect bonus start from the sponsor referer. 


            $user1 = User::find($newUser->ref_by);
            $inDirect_bonus_details = 'Indirect bonus gotten from username: '.$newUser->username. ' Subscribing to '.$project->title;
            

            indirectBonus($user1, $project->pv, $inDirect_bonus_details, $trx);
           

            $detls = $newUser->username . ' Subscribed to ' . $newUser->project->title . ' Project.';
            $pv = $newUser->project->pv;

            updatePV($newUser, $detls, $pv); 

            // Cash back credited to the new user's product_wallet after visa deduction is settled
            $detss = $project->title . ' subscription cash back to Repurchase wallet';
            processCashBack($newUser, $project, $trx, $detss);
            
            // Key-In Bonus: 2.00% of registration PV to the operator who keyed in the registration
            // Proposed Plan: 2.00% (0.02) * PV * ₦600/PV (e.g. 450 PV Mega = ₦5,400)
            $user_to_Get_keyInBonus = auth()->user();
            $pv = (float) $project->pv;
            $keyInBonus = round(($pv * 0.02 * 600), 2);

            if ($keyInBonus > 0) {

                $user_to_Get_keyInBonus->increment('key_in_bonus', $keyInBonus);
                $user_to_Get_keyInBonus->refresh();
                balance_TransactionReturn(
                    $user_to_Get_keyInBonus->id,
                    'Key-in bonus: registered ' . $newUser->username,
                    $keyInBonus,
                    'key_in_bonus',
                    $trx,
                    $user_to_Get_keyInBonus->key_in_bonus
                );

                // Debit from isolated Member Compensation Reserve Ledger
                try {
                    app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                        $keyInBonus,
                        $trx,
                        'Key-in bonus to ' . $user_to_Get_keyInBonus->username . ' for registering ' . $newUser->username,
                        'CRP',
                        'key_in_bonus',
                        $user_to_Get_keyInBonus->id
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

            // Admin notification
            $adminNotification            = new AdminNotification();
            $adminNotification->user_id   = $newUser->id;
            $adminNotification->title     = 'New distributor registered by ' . $user_to_Get_keyInBonus->username;
            $adminNotification->click_url = urlPath('admin.users.detail', $newUser->id);
            $adminNotification->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $notify[] = ['error', 'Something went wrong. Please try again.'];
            //throw $e;
            $notify[] = ['error', 'Error: ' . $e];
            return back()->withNotify($notify)->withInput($request->except('password', 'password_confirmation'));
            
        }

        $notify[] = ['success', 'Distributor ' . $newUser->username . ' registered successfully! ' . gs('cur_sym') . showAmount($cost, currencyFormat: false) . ' debited from your VISA wallet.'];
        return back()->withNotify($notify);
    }
}
