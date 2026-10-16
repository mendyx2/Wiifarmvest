<?php

namespace App\Http\Controllers\Admin;
use App\Models\Pin;
use App\Models\User;
use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Models\BvLog;
use App\Models\Order;
use App\Models\Deposit;
use App\Constants\Status;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Rinvestment;
use App\Models\SponsorChangeLog;
use App\Models\NotificationLog;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Services\AdminNoticeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManageUsersController extends Controller
{

    public function allUsers()
    {
        $pageTitle = 'All Users';
        $users     = $this->userData();
        return view('admin.users.list', compact('pageTitle', 'users'));
    }
    
    public function passwordUpdate(Request $request, $id)
    {
        $request->validate([
           
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);

        // if (!Hash::check($request->old_password, $user->password)) {
        //     $notify[] = ['error', 'Password doesn\'t match!!'];
        //     return back()->withNotify($notify);
        // }

        

        DB::beginTransaction();
        try {

            $user->password = Hash::make($request->password);
            $user->save();
            DB::commit();
            $notify[] = ['success', 'Password  updated successfully'];

        } catch (\Throwable $e) {
            DB::rollBack();
            //throw $e;
            $notify[] = ['error', 'Error: ' . $e];
            $notify[] = ['error', 'Error code AD103'];
        }

        
        return back()->withNotify($notify);
    }

    public function activeUsers()
    {
        $pageTitle = 'Active Users';
        $users     = $this->userData('active');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function bannedUsers()
    {
        $pageTitle = 'Banned Users';
        $users     = $this->userData('banned');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function emailUnverifiedUsers()
    {
        $pageTitle = 'Email Unverified Users';
        $users     = $this->userData('emailUnverified');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function kycUnverifiedUsers()
    {
        $pageTitle = 'KYC Unverified Users';
        $users     = $this->userData('kycUnverified');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function kycPendingUsers()
    {
        $pageTitle = 'KYC Pending Users';
        $users     = $this->userData('kycPending');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function emailVerifiedUsers()
    {
        $pageTitle = 'Email Verified Users';
        $users     = $this->userData('emailVerified');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }


    public function mobileUnverifiedUsers()
    {
        $pageTitle = 'Mobile Unverified Users';
        $users     = $this->userData('mobileUnverified');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }


    public function mobileVerifiedUsers()
    {
        $pageTitle = 'Mobile Verified Users';
        $users     = $this->userData('mobileVerified');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }


    public function usersWithBalance()
    {
        $pageTitle = 'Users with Balance';
        $users     = $this->userData('withBalance');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function paidUsers()
    {
        $pageTitle = 'Paid Users';
        $users     = $this->userData('paidUser');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function freeUsers()
    {
        $pageTitle = 'Free Users';
        $users     = $this->userData('freeUser');
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function stages(Request $request)
    {
        $pageTitle = 'User Stages';

        $stages = MatrixStage::orderBy('level')->get();
       

        // Default to first stage
        $activeStageId = (int) $request->get('stage', optional($stages->first())->id);


        $activeStage   = $stages->firstWhere('id', $activeStageId);

        
        // Simple count per stage — one fast aggregate query
        $stageCounts = DB::table('matrices')
                        ->selectRaw('stage_id, COUNT(DISTINCT user_id) as total')
                        ->groupBy('stage_id')
                        ->pluck('total', 'stage_id');

        // JOIN is far faster than whereHas for large tables
        $query = User::join('matrices', 'users.id', '=', 'matrices.user_id')
                     ->where('matrices.stage_id', $activeStageId)
                     ->select('users.*')
                     ->distinct();

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('users.username', 'like', $term)
                  ->orWhere('users.email',   'like', $term)
                  ->orWhere('users.mobile',  'like', $term);
            });
        }
 
        $users = $query->latest('users.created_at')->paginate(getPaginate());

        $stageNames = $stages->pluck('name', 'id');

        return view('admin.users.stages', compact(
            'pageTitle', 'stages', 'users', 'activeStage', 'activeStageId', 'stageCounts', 'stageNames'
        ));
    }

    protected function userData($scope = null)
    {
        if ($scope) {
            $users = User::$scope();
        } else {
            $users = User::query();
        }
        
        // Apply text search
        $users = $users->searchable(['username', 'email', 'firstname', 'lastname']);
        
        // Apply advanced filters
        $users = $this->applyAdvancedFilters($users);
        
        return $users->orderBy('id', 'desc')->paginate(getPaginate());
    }
    
    protected function applyAdvancedFilters($query)
    {
        // Filter by status
        if (request()->has('status') && request('status') !== '') {
            $status = request('status') == 1 ? Status::USER_ACTIVE : Status::USER_BAN;
            $query->where('status', $status);
        }
        
        return $query;
    }


    public function detail($id)
    {
        $user      = User::findOrFail($id);
        $total_ref = Transaction::where('user_id', $user->id)->where('remark', 'referral_commission')->sum('amount');

        $total_stage_out = Transaction::where('user_id', $user->id)->where('remark', 'stageOut_commission')->sum('amount');
 
        

        $pageTitle = 'User Detail - ' . $user->username;

        $totalDeposit     = Deposit::where('user_id', $user->id)->successful()->sum('amount');
        $totalWithdrawals = Withdrawal::where('user_id', $user->id)->approved()->sum('amount');
        $totalTransaction = Transaction::where('user_id', $user->id)->count();
        $countries        = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $totalBvCut       = BvLog::where('user_id', $user->id)->where('trx_type', '-')->sum('amount');
        $totalOrder       = Order::where('user_id', $user->id)->count();
        $totalPinGenerate = Pin::where('generate_user_id',$user->id)->count();
        $totalUsedPin = Pin::where('user_id',$user->id)->where('status', Status::ENABLE)->count();
        
        $plans = Plan::active()->orderBy('price')->get();
        $userMatrix = \App\Models\Matrix::where('user_id', $user->id)->first();
        $sponsorChangeCount = $user->sponsorChangeLogs()->count();

        return view('admin.users.detail', compact('pageTitle', 'user', 'totalDeposit', 'totalWithdrawals', 'totalTransaction', 'totalPinGenerate', 'totalUsedPin', 'countries', 'totalBvCut', 'totalOrder', 'total_ref', 'total_stage_out', 'plans', 'userMatrix', 'sponsorChangeCount'));
    }

    public function sponsorHistory($id)
    {
        $user      = User::with('refBy')->findOrFail($id);
        $pageTitle = 'Sponsor History - ' . $user->username;
        $logs      = $user->sponsorChangeLogs()
            ->with(['previousSponsor:id,firstname,lastname,username', 'newSponsor:id,firstname,lastname,username', 'admin:id,name,username'])
            ->latest('id')
            ->paginate(getPaginate());

        return view('admin.users.sponsor_history', compact('pageTitle', 'user', 'logs'));
    }

    /**
     * Admin allocates shares to a user directly.
     * Deducts from the user's Money Box balance.
     * The `force` flag lets admin bypass an insufficient-balance check.
     */
    // remember to edit the one at user/controller/planController.php
    public function buySharesForUser(Request $request, $id)
    {
        $request->validate([
            'plan_id'  => 'required|integer|exists:plans,id',
            'quantity' => 'required|integer|min:1',
            
        ]);

        $user     = User::findOrFail($id);
        $plan     = Plan::active()->findOrFail($request->plan_id);
        $quantity = (int) $request->quantity;
        $total    = $plan->price * $quantity;
        $force    = $request->boolean('force', false);

        if ($user->balance < $total && !$force) {
            return response()->json([
                'status'  => 'insufficient',
                'message' => 'User balance (' . showAmount($user->balance) . ') is less than ' . showAmount($total) . '. Tick "Force allocate" to proceed anyway.',
            ], 422);
        }

        DB::transaction(function () use ($user, $plan, $quantity, $total, $force) {
            // Deduct balance only when sufficient; force-allocate skips deduction
            /*
            if ($user->balance >= $total) {
                $user->balance -= $total;
                $user->save();
            }
            */
            
            $user->balance -= $total;
            $user->save();

            $rinvest            = new Rinvestment();
            $rinvest->plan_id   = $plan->id;
            $rinvest->user_id   = $user->id;
            $rinvest->unit_cost = $plan->price;
            $rinvest->units     = $quantity;
            $rinvest->five      = $total;
            $rinvest->trx       = getTrx();
            $rinvest->status    = 1;
            $rinvest->save();


            $txn               = new Transaction();
            $txn->user_id      = $user->id;
            $txn->amount       = $total;
            $txn->trx_type     = '-';
            $txn->details      = 'Admin allocated ' . $quantity . ' unit(s) of ' . $plan->name . ' shares';
            $txn->remark       = 'shares_purchase';
            $txn->trx          = $rinvest->trx;
            $txn->post_balance = $user->balance;
            $txn->charge       = 0;
            $txn->save();


            $totalPayable = $plan->price * $quantity;

            // the get the five percent to share to upliner.
            $five_percentage = $totalPayable * 0.05;
            $trx_no = $rinvest->trx;

            $this->sharesUplinerCommision($user, $trx_no, $totalPayable);
            
        });
 
        return response()->json([
            'status'  => 'success',
            'message' => $quantity . ' unit(s) of ' . $plan->name . ' successfully allocated to ' . $user->username . '.',
        ]);
    }

    protected function sharesUplinerCommision(User $user, $trx_no, $totalPayable)
    {
        $five_percentage = $totalPayable * 0.05;
        $refUser1st = User::find($user->ref_by); 
        if ($refUser1st) {

                //$this->referralLandComission($refUser1st, $details, $percentage, $trx_no);
                $details1 = "Land fractional referral bonus received from ".$user->username." 1st Gen";
                referralLandComission($refUser1st, $details1, 50, $five_percentage, $trx_no);

                //2nd referral
                $refUser_2nd = User::find($refUser1st->ref_by); 
                if ($refUser_2nd) {

                    $details2 = "Land fractional referral bonus received from ".$user->username." 2nd  Gen";

                    referralLandComission($refUser_2nd, $details2, 18, $five_percentage, $trx_no);
                    //3rd referral
                    $refUser_3rd = User::find($refUser_2nd->ref_by); 
                    if ($refUser_3rd) {

                        $details3 = "Land fractional referral bonus received from ".$user->username." 3rd  Gen";
                        referralLandComission($refUser_3rd, $details3, 12, $five_percentage, $trx_no);
                        //4th referral
                        $refUser_4th = User::find($refUser_3rd->ref_by); 
                        if ($refUser_4th) {

                            $details4 = "Land fractional referral bonus received from ".$user->username." 4th  Gen";
                            referralLandComission($refUser_4th, $details4, 10, $five_percentage, $trx_no);
                            //5th referral
                            $refUser_5th = User::find($refUser_4th->ref_by); 
                            if ($refUser_5th) {

                                $details5 = "Land fractional referral bonus received from ".$user->username." 5th  Gen";
                                referralLandComission($refUser_5th, $details5, 5, $five_percentage, $trx_no);
                                //6th referral
                                $refUser_6th = User::find($refUser_5th->ref_by); 
                                if ($refUser_6th) {

                                    $details6 = "Land fractional referral bonus received from ".$user->username." 6th  Gen";
                                    referralLandComission($refUser_6th, $details6, 3, $five_percentage, $trx_no);
                                    //7th referral
                                    $refUser_7th = User::find($refUser_6th->ref_by); 
                                    if ($refUser_7th) {

                                        $details7 = "Land fractional referral bonus received from ".$user->username." 7th  Gen";
                                        referralLandComission($refUser_7th, $details7, 2, $five_percentage, $trx_no); 
                                    }
                                }   
                            }                            
                        }                       
                    }  
                }
            }
    }

    
    public function generatePin($id)
    {
        $user = User::findOrFail($id);
        $pageTitle = "Generate pin - ". $user->username;
        $emptyMessage = "No data found";
        $pins = Pin::where('generate_user_id', $user->id)->latest()->paginate(getPaginate());
        return view('admin.pin.index', compact('pageTitle', 'emptyMessage', 'pins'));
    }


    public function kycDetails($id)
    {
        $pageTitle = 'KYC Details';
        $user      = User::findOrFail($id);
        return view('admin.users.kyc_detail', compact('pageTitle', 'user'));
    }
     public function usedPin($id)
    {
        $user = User::findOrFail($id);
        $pageTitle = "Used pin - ". $user->username;
        $emptyMessage = "No data found";
        $pins = Pin::where('user_id', $user->id)->latest()->paginate(getPaginate());
        return view('admin.pin.index', compact('pageTitle', 'emptyMessage', 'pins'));
    }

    public function kycApprove($id)
    {
        $user     = User::findOrFail($id);
        $user->kv = Status::KYC_VERIFIED;
        $user->save();

        notify($user, 'KYC_APPROVE', []);

        $notify[] = ['success', 'KYC approved successfully'];
        return to_route('admin.users.kyc.pending')->withNotify($notify);
    }

    public function kycReject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required'
        ]);
        $user                       = User::findOrFail($id);
        $user->kv                   = Status::KYC_UNVERIFIED;
        $user->kyc_rejection_reason = $request->reason;
        $user->save();

        notify($user, 'KYC_REJECT', [
            'reason' => $request->reason
        ]);

        $notify[] = ['success', 'KYC rejected successfully'];
        return to_route('admin.users.kyc.pending')->withNotify($notify);
    }
    
    public function update(Request $request, $id)
    {
        //dd($request->email);
        $user         = User::findOrFail($id);
        $countryData  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryArray = (array)$countryData;
        $countries    = implode(',', array_keys($countryArray));

        $countryCode = $request->country;
        $country     = $countryData->$countryCode->country;
        $dialCode    = $countryData->$countryCode->dial_code;

        $request->validate([
            'bname'     => 'sometimes|string|max:40',
            'aname'     => 'sometimes|string|max:40',
            'ano'       => 'sometimes|max:40',
            'firstname' => 'required|string|max:40',
            'lastname'  => 'required|string|max:40',
            'email'     => 'required|email|string|max:40',
            'mobile'    => 'required|string|max:40',
            'country'   => 'required|in:' . $countries,
        ]);
        // 'email'     => 'required|email|string|max:40|unique:users,email,' . $user->id,

        $exists = User::where('mobile', $request->mobile)->where('dial_code', $dialCode)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            $notify[] = ['error', 'The mobile number already exists.'];
            return back()->withNotify($notify);
        }
        

        $user->mobile    = $request->mobile;
        $user->bname = $request->bname;
        $user->aname = $request->aname;
        $user->ano = $request->ano;
        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;
        $user->email     = $request->email;

        $user->address      = $request->address;
        $user->city         = $request->city;
        $user->state        = $request->state;
        $user->zip          = $request->zip;
        $user->country_name = @$country;
        $user->dial_code    = $dialCode;
        $user->country_code = $countryCode;

        $user->ev = $request->ev ? Status::VERIFIED : Status::UNVERIFIED;
        $user->sv = $request->sv ? Status::VERIFIED : Status::UNVERIFIED;
        $user->ts = $request->ts ? Status::ENABLE : Status::DISABLE;
        if (!$request->kv) {
            $user->kv = Status::KYC_UNVERIFIED;
            if ($user->kyc_data) {
                foreach ($user->kyc_data as $kycData) {
                    if ($kycData->type == 'file') {
                        fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
                    }
                }
            }
            $user->kyc_data = null;
        } else {
            $user->kv = Status::KYC_VERIFIED;
        }
        $user->save();

        $notify[] = ['success', 'User details updated successfully'];
        return back()->withNotify($notify);
    }
     
    public function update_again(Request $request, $id){  
        // $c = current 
        //$n = new 
        $request->validate([
            'csponsor' => 'required|string|max:40',
            'nsponsor'  => 'required|string|max:40',
        ]);

        // check if new sponsor is in db
        $n_s_user = User::where('username', $request->nsponsor)->first();
       
        // check if the new sponsor exist
        if (!$n_s_user) {
            $notify[] = ['error', 'The new sponsor does not exist.'];
            return back()->withNotify($notify);
        }

            // check if current sponsor on form submit is in db
        $c_s_user = User::where('username', $request->csponsor)->first();
       
            // check if current sponsor exist
        if (!$c_s_user) {
            $notify[] = ['error', 'The current sponsor does not exist.'];
            return back()->withNotify($notify);
        }

            /* check if current sponsor on form submit is 
            the same with the one store on the user->refBy table. else there is tampering abort mission
            */
        $user = User::find($id);
        // to check if the user exist.
        if (!$user) {
            $notify[] = ['error', 'Error AD101 exists.'];
            return back()->withNotify($notify);
        }
        // To make sure you cannot sponsor yourself.
        if ($user->id == $n_s_user->id){
            $notify[] = ['error', 'You cannot sponsor yourself.'];
            return back()->withNotify($notify);
        }

        if ($c_s_user->id != $user->ref_by){
            $notify[] = ['error', 'Mission Impossible AD102 exist.'];
            return back()->withNotify($notify);
        }

        // No-op change would only pollute the sponsor history
        if ($n_s_user->id == $c_s_user->id) {
            $notify[] = ['error', 'The new sponsor is the same as the current sponsor.'];
            return back()->withNotify($notify);
        }

        // Get the old trx number 
        $trx = $user->trx;

        //dd($user->id);
         if ($user->id < $n_s_user->id ) {
            $notify[] = ['error', 'The New sponsor you filled came after the user was registered. AD106'];
            return back()->withNotify($notify);
        }


        DB::beginTransaction();
        try {

        // Lock the row and re-verify so a double submit can't log the same change twice
        $user = User::lockForUpdate()->find($user->id);
        if ($user->ref_by != $c_s_user->id) {
            DB::rollBack();
            $notify[] = ['error', 'Sponsor was changed by another request. Please reload and try again.'];
            return back()->withNotify($notify);
        }

        // 1)Change the ref_by which is where the sponsor is stored.
        $user->ref_by    = $n_s_user->id;
        $user->save();

        // Keep an audit trail of every sponsor change
        SponsorChangeLog::create([
            'user_id'             => $user->id,
            'previous_sponsor_id' => $c_s_user->id,
            'new_sponsor_id'      => $n_s_user->id,
            'admin_id'            => auth('admin')->id(),
        ]);

       

        //2) retrieve the money from the current sponsor
        $old_amout = $c_s_user->balance;
        //$c_s_user->balance  -= 700;
        $c_s_user->save();
         //update transaction
        $details = "you no longer sponsor ".$user->username;

        //$this->referralComission_change_of_sponsor($c_s_user, $details, '-', $c_s_user->balance, $trx);

        // 3) 
        //$n_s_user->balance  += 700;
        $n_s_user->save();

        $details1 = "Referral bonus gotten from ".$user->username;

        //$this->referralComission_change_of_sponsor($n_s_user, $details1, '+', $n_s_user->balance, $trx);

         DB::commit();
         $notify[] = ['success', 'Sponsors updated successfully'];

        } catch (\Throwable $e) {
            DB::rollBack();
            //throw $e;
            $notify[] = ['error', 'Error: ' . $e];
        }

        
        return back()->withNotify($notify);
    }
    
    // username change
    public function update_again1(Request $request, $id){  
       
        $request->validate([
            'username' => 'required|string',
            'nusername'  => 'required|string|max:40',
        ]);

        // check user
        $user = User::find($id);
        // check if new User is in db
        $nuser = User::where('username', $request->nusername)->first();
       
        // check if the new sponsor exist
        if (!$user) {
            $notify[] = ['error', 'This error cannot be solve by you AD209.'];
            return back()->withNotify($notify);
        }

        // check if the new sponsor exist
        if ($nuser) {
            $notify[] = ['error', 'The new Username exist.'];
            return back()->withNotify($notify);
        }

        DB::beginTransaction();
        try {
        // 1)Change the username.
        $user->username    = $request->nusername;
        $user->save();

         DB::commit();
         $notify[] = ['success', 'Username updated successfully'];

        } catch (\Throwable $e) {
            DB::rollBack();
            //throw $e;
            $notify[] = ['error', 'Error: ' . $e];
        }

        
        return back()->withNotify($notify);
    }

    

    Protected function referralComission_change_of_sponsor(User $user, $details, $trx_type, $post_balance, $trx){


                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = 700;
                $transaction->charge       = 0;
                $transaction->trx_type     = $trx_type;
                $transaction->details      = $details;
                $transaction->remark       = 'referral_commission';
                $transaction->trx          = $trx;
                $transaction->post_balance = $post_balance;
                $transaction->save();
         
    }


    public function addSubBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $user   = User::findOrFail($id);
        $amount = $request->amount;
        $trx    = getTrx();

        $transaction = new Transaction();

        if ($request->act == 'add') {
            $user->balance += $amount;

            $transaction->trx_type = '+';
            $transaction->remark   = 'balance_add';

            $notifyTemplate = 'BAL_ADD';

            $notify[] = ['success', 'Balance added successfully'];
        } else {
            /*
            if ($amount > $user->balance) {
                $notify[] = ['error', $user->username . ' doesn\'t have sufficient balance.'];
                return back()->withNotify($notify);
            }
            */

            $user->balance -= $amount;

            $transaction->trx_type = '-';
            $transaction->remark   = 'balance_subtract';

            $notifyTemplate = 'BAL_SUB';
            $notify[]       = ['success', 'Balance subtracted successfully'];
        }

        $user->save();

        $transaction->user_id      = $user->id;
        $transaction->amount       = $amount;
        $transaction->post_balance = $user->balance;
        $transaction->charge       = 0;
        $transaction->trx          = $trx;
        $transaction->details      = $request->remark;
        $transaction->save();

        notify($user, $notifyTemplate, [
            'trx'          => $trx,
            'amount'       => showAmount($amount, currencyFormat: false),
            'remark'       => $request->remark,
            'post_balance' => showAmount($user->balance, currencyFormat: false)
        ]);

        return back()->withNotify($notify);
    }
   

    //VISA wallet
    public function addSubBalanceVisa(Request $request, $id)
    {   
        /*
        if($request->stats > 1 || $request->stats < 0){
            
            $notify[] = ['success', 'Stats must be 0 or 1'];
            return back()->withNotify($notify);
        }
        //'stats' => 'required|numeric',
        */
        
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $user   = User::findOrFail($id);
        $amount = $request->amount;
        $trx    = getTrx();

        $transaction = new Transaction();

        if ($request->act == 'add') {
            $user->visa += $amount;
            $transaction->trx_type = '+';
            $transaction->remark   = 'visa_wallet_add';
            $notifyTemplate = 'BAL_ADD';
            $notify[] = ['success', 'VISA wallet added successfully'];
        } else {
            if ($amount > $user->visa) {
                $notify[] = ['error', $user->username . ' doesn\'t have sufficient money is VISA wallet.'];
                return back()->withNotify($notify);
            }

            $user->visa -= $amount;
            $transaction->trx_type = '-';
            $transaction->remark   = 'visa_wallet_subtract';

            $notifyTemplate = 'BAL_SUB';
            $notify[]       = ['success', 'VISA wallet subtracted successfully'];
        }

        $user->save();

        $transaction->user_id      = $user->id;
        $transaction->amount       = $amount;
        $transaction->post_balance = $user->visa;
        $transaction->charge       = 0;
        $transaction->trx          = $trx;
        $transaction->details      = $request->remark;
        $transaction->save();

        notify($user, $notifyTemplate, [
            'trx'          => $trx,
            'amount'       => showAmount($amount, currencyFormat: false),
            'remark'       => $request->remark,
            'post_balance' => showAmount($user->visa, currencyFormat: false)
        ]);

        return back()->withNotify($notify);
    }


    // Re-purchase wallet (repurchase wallet used for buying products)
    public function addSubBalanceProductWallet(Request $request, int $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $user   = User::findOrFail($id);
        $amount = (float) $request->amount;
        $trx    = getTrx();

        $transaction = new Transaction();

        if ($request->act == 'add') {
            $user->product_wallet  += $amount;
            $transaction->trx_type  = '+';
            $transaction->remark    = 'product_wallet_add';
            $notifyTemplate         = 'BAL_ADD';
            $notify[]               = ['success', 'Re-purchase wallet credited successfully'];
        } else {
            if ($amount > (float) $user->product_wallet) {
                $notify[] = ['error', $user->username . ' does not have sufficient Re-purchase wallet balance.'];
                return back()->withNotify($notify);
            }
            $user->product_wallet  -= $amount;
            $transaction->trx_type  = '-';
            $transaction->remark    = 'product_wallet_subtract';
            $notifyTemplate         = 'BAL_SUB';
            $notify[]               = ['success', 'Re-purchase wallet debited successfully'];
        }

        $user->save();

        $transaction->user_id      = $user->id;
        $transaction->amount       = $amount;
        $transaction->post_balance = $user->product_wallet;
        $transaction->charge       = 0;
        $transaction->trx          = $trx;
        $transaction->details      = $request->remark;
        $transaction->save();

        notify($user, $notifyTemplate, [
            'trx'          => $trx,
            'amount'       => showAmount($amount, currencyFormat: false),
            'remark'       => $request->remark,
            'post_balance' => showAmount($user->product_wallet, currencyFormat: false),
        ]);

        return back()->withNotify($notify);
    }

    public function login($id)
    {
        Auth::loginUsingId($id);
        return to_route('user.home');
    }

    public function status(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->status == Status::USER_ACTIVE) {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);
            $user->status     = Status::USER_BAN;
            $user->ban_reason = $request->reason;
            $notify[]         = ['success', 'User banned successfully'];
        } else {
            $user->status     = Status::USER_ACTIVE;
            $user->ban_reason = null;
            $notify[]         = ['success', 'User unbanned successfully'];
        }
        $user->save();
        return back()->withNotify($notify);
    }


    public function toggleWithdrawalBlock(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$user->withdrawal_blocked) {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);
            $user->withdrawal_blocked       = true;
            $user->withdrawal_block_reason  = $request->reason;
            $notify[] = ['success', 'Withdrawal access blocked for ' . $user->username];
        } else {
            $user->withdrawal_blocked       = false;
            $user->withdrawal_block_reason  = null;
            $notify[] = ['success', 'Withdrawal access restored for ' . $user->username];
        }

        $user->save();
        return back()->withNotify($notify);
    }


    public function showNotificationSingleForm($id)
    {
        $user = User::findOrFail($id);
        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.users.detail', $user->id)->withNotify($notify);
        }
        $pageTitle = 'Send Notification to ' . $user->username;
        return view('admin.users.notification_single', compact('pageTitle', 'user'));
    }

    public function sendNotificationSingle(Request $request, $id)
    {
        $request->validate([
            'message' => 'required',
            'via'     => 'required|in:email,sms,push',
            'subject' => 'required_if:via,email,push',
            'image'   => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        $imageUrl = null;
        if ($request->via == 'push' && $request->hasFile('image')) {
            $imageUrl = fileUploader($request->image, getFilePath('push'));
        }

        $user = User::findOrFail($id);
        [$success, $message] = (new AdminNoticeService())->send(
            $user,
            $request->via,
            $request->subject,
            $request->message,
            $imageUrl,
            auth('admin')->id()
        );

        $notify[] = [$success ? 'success' : 'warning', $success ? 'Notification sent successfully' : $message];
        return back()->withNotify($notify);
    }

    public function showNotificationAllForm()
    {
        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        $notifyToUser = User::notifyToUser();
        $users        = User::active()->count();
        $pageTitle    = 'Notification to Verified Users';

        if (session()->has('SEND_NOTIFICATION') && !request()->email_sent) {
            session()->forget('SEND_NOTIFICATION');
        }

        return view('admin.users.notification_all', compact('pageTitle', 'users', 'notifyToUser'));
    }

    public function sendNotificationAll(Request $request)
    {
        $request->validate([
            'via'                          => 'required|in:email,sms,push',
            'message'                      => 'required',
            'subject'                      => 'required_if:via,email,push',
            'start'                        => 'required|integer|gte:1',
            'batch'                        => 'required|integer|gte:1',
            'being_sent_to'                => 'required',
            'cooling_time'                 => 'required|integer|gte:1',
            'number_of_top_deposited_user' => 'required_if:being_sent_to,topDepositedUsers|integer|gte:0',
            'number_of_days'               => 'required_if:being_sent_to,notLoginUsers|integer|gte:0',
            'image'                        => ["nullable", 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ], [
            'number_of_days.required_if'               => "Number of days field is required",
            'number_of_top_deposited_user.required_if' => "Number of top deposited user field is required",
        ]);

        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }


        $template = NotificationTemplate::where('act', 'DEFAULT')->where($request->via . '_status', Status::ENABLE)->exists();
        if (!$template) {
            $notify[] = ['warning', 'Default notification template is not enabled'];
            return back()->withNotify($notify);
        }

        if ($request->being_sent_to == 'selectedUsers') {
            if (session()->has("SEND_NOTIFICATION")) {
                $request->merge(['user' => session()->get('SEND_NOTIFICATION')['user']]);
            } else {
                if (!$request->user || !is_array($request->user) || empty($request->user)) {
                    $notify[] = ['error', "Ensure that the user field is populated when sending an email to the designated user group"];
                    return back()->withNotify($notify);
                }
            }
        }

        $scope     = $request->being_sent_to;
        $userQuery = User::oldest()->active()->$scope();

        if (session()->has("SEND_NOTIFICATION")) {
            $totalUserCount = session('SEND_NOTIFICATION')['total_user'];
        } else {
            $totalUserCount = (clone $userQuery)->count() - ($request->start - 1);
        }


        if ($totalUserCount <= 0) {
            $notify[] = ['error', "Notification recipients were not found among the selected user base."];
            return back()->withNotify($notify);
        }


        $imageUrl = null;

        if ($request->via == 'push' && $request->hasFile('image')) {
            if (session()->has("SEND_NOTIFICATION")) {
                $request->merge(['image' => session()->get('SEND_NOTIFICATION')['image']]);
            }
            if ($request->hasFile("image")) {
                $imageUrl = fileUploader($request->image, getFilePath('push'));
            }
        }

        $users = (clone $userQuery)->skip($request->start - 1)->limit($request->batch)->get();

        foreach ($users as $user) {
            notify($user, 'DEFAULT', [
                'subject' => $request->subject,
                'message' => $request->message,
            ], [$request->via], pushImage: $imageUrl);
        }

        return $this->sessionForNotification($totalUserCount, $request);
    }


    private function sessionForNotification($totalUserCount, $request)
    {
        if (session()->has('SEND_NOTIFICATION')) {
            $sessionData                = session("SEND_NOTIFICATION");
            $sessionData['total_sent'] += $sessionData['batch'];
        } else {
            $sessionData               = $request->except('_token');
            $sessionData['total_sent'] = $request->batch;
            $sessionData['total_user'] = $totalUserCount;
        }

        $sessionData['start'] = $sessionData['total_sent'] + 1;

        if ($sessionData['total_sent'] >= $totalUserCount) {
            session()->forget("SEND_NOTIFICATION");
            $message = ucfirst($request->via) . " notifications were sent successfully";
            $url     = route("admin.users.notification.all");
        } else {
            session()->put('SEND_NOTIFICATION', $sessionData);
            $message = $sessionData['total_sent'] . " " . $sessionData['via'] . "  notifications were sent successfully";
            $url     = route("admin.users.notification.all") . "?email_sent=yes";
        }
        $notify[] = ['success', $message];
        return redirect($url)->withNotify($notify);
    }

    public function countBySegment($methodName)
    {
        return User::active()->$methodName()->count();
    }



    public function list()
    {
        $query = User::active();

        if (request()->search) {
            $query->where(function ($q) {
                $q->where('email', 'like', '%' . request()->search . '%')->orWhere('username', 'like', '%' . request()->search . '%');
            });
        }
        $users = $query->orderBy('id', 'desc')->paginate(getPaginate());
        return response()->json([
            'success' => true,
            'users'   => $users,
            'more'    => $users->hasMorePages()
        ]);
    }

    public function notificationLog($id)
    {
        $user      = User::findOrFail($id);
        $pageTitle = 'Notifications Sent to ' . $user->username;
        $logs      = NotificationLog::where('user_id', $id)->with('user')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.reports.notification_history', compact('pageTitle', 'logs', 'user'));
    }
 
     public function tree($username)
    {

        $user = User::where('username', $username)->first();

        if ($user) { 
            $tree      = showMatrixTree($user->id);
            $pageTitle = "Tree of " . $user->fullname;
            return view('admin.users.tree', compact('tree', 'pageTitle'));
        }

        $notify[] = ['error', 'Tree Not Found!!'];
        return redirect()->route('admin.dashboard')->withNotify($notify);
    }
  
    public function otherTree(Request $request, $username = null)
    {  
        //dd($request);
        if ($request->username) {
            $user = User::where('username', $request->username)->first();
        } else {
            $user = User::where('username', $username)->first();
        }
        if ($user) {
           
            $pageTitle = "Tree of " . $user->fullname;
            $tree      = showMatrixTree($user->id);
            //dd($tree);
            return view('admin.users.tree', compact('tree', 'pageTitle'));
        }

        $notify[] = ['error', 'Tree Not Found !'];
        return redirect()->route('admin.dashboard')->withNotify($notify);
    }
    public function userRef($id)
    {
        $user      = User::findOrFail($id);
        $pageTitle = 'Referred By ' . $user->username;
        $users     = User::searchable(['username', 'email'])->where('ref_by', $id)->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('pageTitle', 'users'));
    }


    public function matchingUpdate(Request $request)
    {
        $request->validate([
            'bv_price' => 'required|min:0',
            'total_bv' => 'required|min:0|integer',
            'max_bv'   => 'required|min:0|integer',
        ]);

        if ($request->matching_bonus_time == 'daily') {
            $when = $request->daily_time;
        } elseif ($request->matching_bonus_time == 'weekly') {
            $when = $request->weekly_time;
        } elseif ($request->matching_bonus_time == 'monthly') {
            $when = $request->monthly_time;
        }

        $setting                      = gs();
        $setting->bv_price            = $request->bv_price;
        $setting->total_bv            = $request->total_bv;
        $setting->max_bv              = $request->max_bv;
        $setting->cary_flash          = $request->cary_flash;
        $setting->matching_bonus_time = $request->matching_bonus_time;
        $setting->matching_when       = $when;
        $setting->save();

        $notify[] = ['success', 'Matching bonus has been updated.'];
        return back()->withNotify($notify);
    }

    public function toggleAmbassador(int $id)
    {
        $user = User::findOrFail($id);
        $user->ambassador = !$user->ambassador;
        $user->save();

        $status  = $user->ambassador ? 'tagged as Ambassador' : 'removed from Ambassador status';
        $notify[] = ['success', $user->fullname . ' has been ' . $status . '.'];
        return to_route('admin.users.detail', $user->id)->withNotify($notify);
    }
}


