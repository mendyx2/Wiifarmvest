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
use App\Support\AdminPresenter;
use Inertia\Inertia;

class ManageUsersController extends Controller
{

    public function allUsers()
    {
        return $this->listPage('All Users', null);
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
        return $this->listPage('Active Users', 'active');
    }

    public function bannedUsers()
    {
        return $this->listPage('Banned Users', 'banned');
    }

    public function emailUnverifiedUsers()
    {
        return $this->listPage('Email Unverified Users', 'emailUnverified');
    }

    public function kycUnverifiedUsers()
    {
        return $this->listPage('KYC Unverified Users', 'kycUnverified');
    }

    public function kycPendingUsers()
    {
        return $this->listPage('KYC Pending Users', 'kycPending');
    }

    public function emailVerifiedUsers()
    {
        return $this->listPage('Email Verified Users', 'emailVerified');
    }


    public function mobileUnverifiedUsers()
    {
        return $this->listPage('Mobile Unverified Users', 'mobileUnverified');
    }


    public function mobileVerifiedUsers()
    {
        return $this->listPage('Mobile Verified Users', 'mobileVerified');
    }


    public function usersWithBalance()
    {
        return $this->listPage('Users with Balance', 'withBalance');
    }

    public function paidUsers()
    {
        return $this->listPage('Paid Users', 'paidUser');
    }

    public function freeUsers()
    {
        return $this->listPage('Free Users', 'freeUser');
    }

    /** Users by matrix stage (super-admins), Pages/Admin/Users/Stages.tsx. */
    public function stages(Request $request)
    {
        $stages = MatrixStage::orderBy('level')->get();

        // Default to the first stage; an unknown ?stage= falls back to it too.
        $activeStage   = $stages->firstWhere('id', (int) $request->get('stage')) ?? $stages->first();
        $activeStageId = (int) optional($activeStage)->id;

        // One aggregate query for every tab's count.
        $stageCounts = DB::table('matrices')
            ->selectRaw('stage_id, COUNT(DISTINCT user_id) as total')
            ->groupBy('stage_id')
            ->pluck('total', 'stage_id');

        // A join is far faster than whereHas on large tables.
        $query = User::join('matrices', 'users.id', '=', 'matrices.user_id')
            ->where('matrices.stage_id', $activeStageId)
            ->select('users.*')
            ->distinct();

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('users.username', 'like', $term)
                  ->orWhere('users.email', 'like', $term)
                  ->orWhere('users.mobile', 'like', $term);
            });
        }

        $users = $query->latest('users.created_at')->paginate(getPaginate())->withQueryString();
        $first = $users->firstItem() ?? 0;
        $rows  = $users->through(function (User $user) use (&$first) {
            return [
                'id'       => $user->id,
                'number'   => $first++,
                'fullname' => $user->fullname ?: '—',
                'username' => (string) $user->username,
                'mobile'   => $user->mobile ?: '—',
                'email'    => (string) $user->email,
                'joined'   => showDateTime($user->created_at),
                'url'      => route('admin.users.detail', $user->id),
            ];
        });

        return Inertia::render('Admin/Users/Stages', [
            'stages' => $stages->map(fn ($stage) => [
                'id'    => $stage->id,
                'name'  => (string) $stage->name,
                'count' => (int) ($stageCounts[$stage->id] ?? 0),
            ])->values(),
            'active' => $activeStage ? ['id' => $activeStageId, 'name' => (string) $activeStage->name] : null,
            'users'  => $rows,
            'search' => (string) $request->query('search', ''),
        ])->withViewData(['pageTitle' => 'User Stages']);
    }

    /**
     * Manage Users lists (Pages/Admin/Users/List.tsx): one page for every
     * segment. The Blade page had two search boxes for the same ?search=;
     * there is one now, with the status filter next to it.
     */
    protected function listPage(string $title, ?string $scope, ?User $referrer = null)
    {
        // $scope is always one of the fixed segment names above, never request input.
        $users = $scope ? User::$scope() : User::query();
        if ($referrer) {
            $users->where('ref_by', $referrer->id);
        }

        $users = $users->searchable(['username', 'email', 'firstname', 'lastname']);
        $users = $this->applyAdvancedFilters($users);

        $kycLinks = request()->routeIs('admin.users.kyc.pending');

        $users = $users->with('refBy:id,username')->orderBy('id', 'desc')->paginate(getPaginate())->withQueryString()
            ->through(fn (User $user) => [
                'id'      => $user->id,
                'user'    => AdminPresenter::user($user),
                'email'   => (string) $user->email,
                'mobile'  => (string) $user->mobileNumber,
                // The old cell printed "@" even when there was no sponsor.
                'sponsor' => $user->refBy?->username,
                'joined'  => AdminPresenter::when($user->created_at),
                'balance' => showAmount($user->balance),
                'kycUrl'  => $kycLinks ? route('admin.users.kyc.details', $user->id) : null,
            ]);

        return Inertia::render('Admin/Users/List', [
            'title'   => $title,
            'users'   => $users,
            'filters' => [
                'search' => (string) request()->query('search', ''),
                'status' => (string) request()->query('status', ''),
            ],
        ])->withViewData(['pageTitle' => $title]);
    }

    protected function applyAdvancedFilters($query)
    {
        // ?status=1 active, ?status=0 banned; anything else (or nothing) shows both.
        $status = (string) request()->query('status', '');
        if ($status === '1' || $status === '0') {
            $query->where('status', $status === '1' ? Status::USER_ACTIVE : Status::USER_BAN);
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
        $user = User::with('refBy')->findOrFail($id);
        $logs = $user->sponsorChangeLogs()
            ->with(['previousSponsor:id,firstname,lastname,username', 'newSponsor:id,firstname,lastname,username', 'admin:id,name,username'])
            ->latest('id')
            ->paginate(getPaginate());

        $first = $logs->firstItem() ?? 0;
        $rows  = $logs->through(function ($log) use (&$first, $logs, $user) {
            $isFirst = $first === 1;

            return [
                'id'       => $log->id,
                'number'   => $first++,
                'when'     => AdminPresenter::when($log->created_at),
                'previous' => AdminPresenter::user($log->previousSponsor),
                'new'      => AdminPresenter::user($log->newSponsor),
                // The newest change is the one in force, if it still matches the member's sponsor.
                'current'  => $isFirst && (int) $log->new_sponsor_id === (int) $user->ref_by,
                'admin'    => $log->admin ? ['name' => (string) $log->admin->name, 'username' => (string) $log->admin->username] : null,
            ];
        });

        $title = 'Sponsor History - ' . $user->username;

        return Inertia::render('Admin/Users/SponsorHistory', [
            'title'   => $title,
            'member'  => AdminPresenter::user($user),
            'sponsor' => AdminPresenter::user($user->refBy),
            'logs'    => $rows,
        ])->withViewData(['pageTitle' => $title]);
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


    /** One member's KYC submission (Pages/Admin/Users/KycDetail.tsx), with approve / reject. */
    public function kycDetails($id)
    {
        $user = User::findOrFail($id);

        $file = function (string $path, string $name) {
            $hash = encrypt($path);
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            return [
                'view'     => route('admin.view.attachment', $hash),
                'download' => route('admin.download.attachment', $hash),
                'image'    => in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
            ];
        };

        $fields = collect((array) ($user->kyc_data ?? []))
            ->filter(fn ($f) => !empty($f->value))
            ->map(fn ($f) => [
                'name'  => __((string) $f->name),
                'type'  => (string) $f->type,
                'value' => $f->type === 'checkbox' ? implode(', ', (array) $f->value) : ($f->type === 'file' ? null : __((string) $f->value)),
                'file'  => $f->type === 'file' ? $file(getFilePath('verify') . '/' . $f->value, (string) $f->value) : null,
            ])->values();

        $guarantor = \App\Models\GuarantorRequest::where('user_id', $user->id)->with('guarantor')->latest()->first();

        return Inertia::render('Admin/Users/KycDetail', [
            'member'   => AdminPresenter::user($user),
            'hasData'  => (bool) $user->kyc_data,
            'fields'   => $fields,
            'identity' => [
                'nin'      => $user->nin ?: null,
                'cardType' => $user->id_card_type ? ucwords(str_replace('_', ' ', $user->id_card_type)) : null,
                'card'     => $user->id_card_image ? $file(getFilePath('verify') . '/id_cards/' . $user->id_card_image, (string) $user->id_card_image) : null,
            ],
            'guarantor' => $guarantor ? [
                'name'      => $guarantor->guarantor ? trim($guarantor->guarantor->firstname . ' ' . $guarantor->guarantor->lastname) : null,
                'username'  => $guarantor->guarantor?->username,
                'status'    => AdminPresenter::badge(ucfirst((string) $guarantor->status), match ($guarantor->status) {
                    'pending' => 'badge--warning', 'accepted' => 'badge--success', 'declined' => 'badge--danger', default => 'badge--dark',
                }),
                'responded' => $guarantor->responded_at?->format('d M Y, g:i A'),
            ] : null,
            'pending'         => (int) $user->kv === Status::KYC_PENDING,
            'rejectionReason' => (int) $user->kv === Status::KYC_UNVERIFIED ? ($user->kyc_rejection_reason ?: null) : null,
            'urls'            => [
                'approve' => route('admin.users.kyc.approve', $user->id),
                'reject'  => route('admin.users.kyc.reject', $user->id),
            ],
        ])->withViewData(['pageTitle' => 'KYC Details']);
    }

     public function usedPin($id)
    {
        $user = User::findOrFail($id);
        $pageTitle = "Used pin - ". $user->username;
        $emptyMessage = "No data found";
        $pins = Pin::where('user_id', $user->id)->latest()->paginate(getPaginate());
        return view('admin.pin.index', compact('pageTitle', 'emptyMessage', 'pins'));
    }

    /**
     * Only a submission that is waiting can be approved or rejected. These
     * used to work on any member, so a stale tab could approve a member whose
     * documents had been rejected, or reject an already verified one.
     */
    public function kycApprove($id)
    {
        $user = User::findOrFail($id);
        if ((int) $user->kv !== Status::KYC_PENDING) {
            return back()->withNotify([['error', 'This KYC submission is no longer pending.']]);
        }

        $user->kv = Status::KYC_VERIFIED;
        $user->save();

        notify($user, 'KYC_APPROVE', []);

        $notify[] = ['success', 'KYC approved successfully'];
        return to_route('admin.users.kyc.pending')->withNotify($notify);
    }

    public function kycReject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
        $user = User::findOrFail($id);
        if ((int) $user->kv !== Status::KYC_PENDING) {
            return back()->withNotify([['error', 'This KYC submission is no longer pending.']]);
        }

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
        // Same page as Report → Notification History, for one member.
        return app(ReportController::class)->notificationPage(request(), User::findOrFail($id));
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
        $user = User::findOrFail($id);

        return $this->listPage('Referred By ' . $user->username, null, $user);
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


