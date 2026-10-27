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
use App\Support\TreePresenter;
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
        $user->password = Hash::make($request->password);
        $user->save();

        // The old code put the whole exception (with its stack trace) into the toast.
        $notify[] = ['success', 'Password updated successfully'];
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


    /**
     * The member page (Pages/Admin/Users/Detail.tsx): balances, quick links,
     * account controls, and the edit forms. Each action button is shown only
     * when AdminAccessMiddleware would let this admin use it (the Blade page
     * showed the wallet buttons to "Owner" admins, who then got a 403).
     */
    public function detail($id)
    {
        $user  = User::with(['refBy', 'project', 'stockist'])->findOrFail($id);
        $admin = auth('admin')->user();
        $can   = fn (string $route) => \App\Http\Middleware\AdminAccessMiddleware::allows($admin, $route, 'POST');

        $totalRef         = Transaction::where('user_id', $user->id)->where('remark', 'referral_commission')->sum('amount');
        $totalDeposit     = Deposit::where('user_id', $user->id)->successful()->sum('amount');
        $totalWithdrawals = Withdrawal::where('user_id', $user->id)->approved()->sum('amount');
        $totalTransaction = Transaction::where('user_id', $user->id)->count();
        $userMatrix       = Matrix::where('user_id', $user->id)->first();

        // $route: the link's route name, so the page knows whether it opens a React page.
        $widget = fn (string $title, string $value, string $icon, string $bg, ?string $route = null, array $params = []) => [
            'title' => $title, 'value' => $value, 'icon' => $icon, 'bg' => $bg,
            'link'  => $route ? route($route, $params) : null,
            'react' => $route ? \App\Support\AdminLayout::isReact($route) : false,
        ];
        $money  = fn ($v) => showAmount((float) ($v ?? 0));

        $main = [
            $widget('Balance', $money($user->balance), 'las la-money-bill-wave-alt', 'indigo'),
            $widget('VISA wallet', $money($user->visa), 'la la-money', '8'),
        ];
        if ($user->stockist) {
            $main[] = $widget('Stockist Balance', $money($user->stockist->wallet), 'las la-store', '6', 'admin.stockist.details', [$user->stockist->id]);
        }
        array_push($main,
            $widget('Referral Commission', $money($totalRef), 'la la-user', '17', 'admin.report.referral.commission', [$user->id]),
            $widget('Deposits', $money($totalDeposit), 'las la-wallet', '8', 'admin.deposit.list', [$user->id]),
            $widget('Withdrawals', $money($totalWithdrawals), 'la la-bank', '6', 'admin.withdraw.data.all', [$user->id]),
            $widget('Transactions', (string) $totalTransaction, 'las la-exchange-alt', '17', 'admin.report.transaction', [$user->id]),
            $widget('Total Invest', $money($user->total_invest), 'la la-money', '17', 'admin.report.invest', [$user->id]),
            $widget('Total Invest Commission', $money($user->total_binary_com), 'las la-coins', '6', 'admin.report.binary.commission', [$user->id]),
        );

        $wallets = [
            $widget('Re-purchase wallet', $money($user->product_wallet), 'las la-shopping-bag', '9'),
            $widget('Direct Bonus (Lifetime)', $money($user->direct_bonus), 'las la-hand-holding-usd', '2'),
            $widget('Indirect Bonus (Lifetime)', $money($user->indirect_bonus), 'las la-network-wired', '8'),
            $widget('Upgrade Bonus (Lifetime)', $money($user->upgrade_bonus), 'las la-arrow-up', '14'),
            $widget('Unilevel Bonus (Lifetime)', $money($user->unilevel_bonus), 'las la-sitemap', '17'),
            $widget('Matching Bonus (Lifetime)', $money($user->matching_bonus), 'las la-balance-scale', '6'),
            $widget('Repurchase Award Wallet', $money($user->repurchase_award), 'las la-medal', '14'),
            $widget('Key-In Bonus (Lifetime)', $money($user->key_in_bonus), 'las la-keyboard', '17'),
            $widget('ACB Bonus (Achievers Celebrated)', $money($user->acb), 'las la-star', '2'),
        ];

        $pv = $userMatrix ? [
            'position' => $userMatrix->position ? ucfirst((string) $userMatrix->position) : null,
            'positionClass' => match ($userMatrix->position) { 'left' => 'primary', 'right' => 'danger', default => 'success' },
            'widgets' => [
                $widget('PV Left Ranking', number_format((float) $userMatrix->pv_left, 2), 'las la-arrow-alt-circle-left', '1'),
                $widget('PV Right Ranking', number_format((float) $userMatrix->pv_right, 2), 'las la-arrow-alt-circle-right', '13'),
                $widget('PV Left (Matched)', number_format((float) $userMatrix->pv_left_pairing, 2), 'las la-check-double', '4'),
                $widget('PV Right (Matched)', number_format((float) $userMatrix->pv_right_pairing, 2), 'las la-check-double', '16'),
            ],
        ] : null;

        $countries = collect((array) json_decode(file_get_contents(resource_path('views/partials/country.json'))))
            ->map(fn ($c, $code) => ['code' => (string) $code, 'name' => __((string) $c->country), 'dial' => (string) $c->dial_code])->values();

        $title = 'User Detail - ' . $user->username;

        return Inertia::render('Admin/Users/Detail', [
            'title'  => $title,
            'member' => [
                'id'          => $user->id,
                'fullname'    => (string) $user->fullname,
                'username'    => (string) $user->username,
                'email'       => (string) $user->email,
                'mobile'      => $user->dial_code . $user->mobile,
                'trx'         => (string) $user->trx,
                'state'       => (string) $user->state,
                'address'     => (string) $user->address,
                'joined'      => showDateTime($user->created_at),
                'image'       => getImage(getFilePath('userProfile') . '/' . $user->image, null, true),
                'project'     => $user->project ? ['title' => __($user->project->title), 'color' => (string) $user->project->color, 'icon' => (string) $user->project->icon] : null,
                'sponsor'     => AdminPresenter::user($user->refBy),
                'active'      => (int) $user->status === Status::USER_ACTIVE,
                'banReason'   => $user->ban_reason,
                'withdrawalBlocked' => (bool) $user->withdrawal_blocked,
                'withdrawalBlockReason' => $user->withdrawal_block_reason,
                'ambassador'  => (bool) $user->ambassador,
                'hasKyc'      => (bool) $user->kyc_data,
                'balance'     => (float) $user->balance,
                'balanceLabel'       => showAmount($user->balance),
                'productWalletLabel' => showAmount((float) $user->product_wallet),
            ],
            'widgets' => ['main' => $main, 'wallets' => $wallets, 'pv' => $pv],
            'form'    => [
                'firstname' => (string) $user->firstname,
                'lastname'  => (string) $user->lastname,
                'email'     => (string) $user->email,
                'bname'     => (string) $user->bname,
                'aname'     => (string) $user->aname,
                'ano'       => (string) $user->ano,
                'mobile'    => (string) $user->mobile,
                'address'   => (string) $user->address,
                'city'      => (string) $user->city,
                'state'     => (string) $user->state,
                'zip'       => (string) $user->zip,
                'country'   => (string) $user->country_code,
                'ev'        => (bool) $user->ev,
                'sv'        => (bool) $user->sv,
                'ts'        => (bool) $user->ts,
                'kv'        => (int) $user->kv === Status::KYC_VERIFIED,
            ],
            'countries' => $countries,
            'plans'     => Plan::active()->orderBy('price')->get()->map(fn (Plan $plan) => [
                'id' => $plan->id, 'name' => (string) $plan->name, 'price' => (float) $plan->price, 'priceLabel' => showAmount($plan->price),
            ])->values(),
            'currency'           => (string) gs('cur_text'),
            'sponsorChangeCount' => $user->sponsorChangeLogs()->count(),
            'links' => [
                'logins'         => route('admin.report.login.history', ['search' => $user->username]),
                'notifications'  => route('admin.users.notification.log', $user->id),
                'welcomePacks'   => route('admin.welcome-pack.index', ['user' => $user->username]),
                'kyc'            => route('admin.users.kyc.details', $user->id),
                'tree'           => route('admin.users.other.tree', $user->username),
                'referrals'      => route('admin.users.referral', $user->id),
                'sponsorHistory' => route('admin.users.sponsor.history', $user->id),
                'loginAs'        => route('admin.users.login', $user->id),
            ],
            'urls' => [
                'update'        => route('admin.users.update', $user->id),
                'sponsor'       => route('admin.users.update_again', $user->id),
                'username'      => route('admin.users.update_again1', $user->id),
                'password'      => route('admin.users.update_password', $user->id),
                'balance'       => route('admin.users.add.sub.balance', $user->id),
                'visa'          => route('admin.users.add.sub.balance.visa', $user->id),
                'productWallet' => route('admin.users.add.sub.balance.product.wallet', $user->id),
                'status'        => route('admin.users.status', $user->id),
                'withdrawal'    => route('admin.users.withdrawal.block', $user->id),
                'ambassador'    => route('admin.users.ambassador.toggle', $user->id),
                'buyShares'     => route('admin.users.buy.shares', $user->id),
            ],
            'can' => [
                'edit'       => $can('admin.users.update'),
                'sponsor'    => $can('admin.users.update_again'),
                'username'   => $can('admin.users.update_again1'),
                'password'   => $can('admin.users.update_password'),
                'wallets'    => $can('admin.users.add.sub.balance'),
                'status'     => $can('admin.users.status'),
                'withdrawal' => $can('admin.users.withdrawal.block'),
                'ambassador' => $can('admin.users.ambassador.toggle'),
                'buyShares'  => $can('admin.users.buy.shares'),
                'loginAs'    => \App\Http\Middleware\AdminAccessMiddleware::allows($admin, 'admin.users.login'),
            ],
        ])->withViewData(['pageTitle' => $title]);
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
            'quantity' => 'required|integer|min:1|max:100000',
        ]);

        User::findOrFail($id);
        $plan     = Plan::active()->findOrFail($request->plan_id);
        $quantity = (int) $request->quantity;
        $total    = $plan->price * $quantity;
        $force    = $request->boolean('force', false);

        try {
            $user = DB::transaction(function () use ($id, $plan, $quantity, $total, $force) {
                // Locked, so a double click cannot allocate (and charge) twice
                // on the same balance check.
                $user = User::lockForUpdate()->findOrFail($id);

                if ($user->balance < $total && !$force) {
                    throw new \DomainException('User balance (' . showAmount($user->balance) . ') is less than ' . showAmount($total) . '. Tick "Force allocate" to proceed anyway.');
                }

                // "Force allocate" still deducts: the balance goes below zero by
                // the shortfall. The old dialog said it skipped the deduction,
                // but that branch was commented out in the code. Unchanged here;
                // the dialog now says what really happens.
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

                $this->sharesUplinerCommision($user, $rinvest->trx, $total);

                return $user;
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->withNotify([['success', $quantity . ' unit(s) of ' . $plan->name . ' successfully allocated to ' . $user->username . '.']]);
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

        $fields = AdminPresenter::formFields($user->kyc_data, skipEmpty: true);

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
        $user         = User::findOrFail($id);
        $countryData  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countries    = implode(',', array_keys((array) $countryData));

        // Validated before the country is looked up: a missing or unknown
        // country used to end in a server error instead of a message.
        $request->validate([
            'bname'     => 'nullable|string|max:40',
            'aname'     => 'nullable|string|max:40',
            'ano'       => 'nullable|string|max:40',
            'firstname' => 'required|string|max:40',
            'lastname'  => 'required|string|max:40',
            'email'     => 'required|email|string|max:40',
            'mobile'    => 'required|string|max:40',
            'address'   => 'nullable|string|max:255',
            'city'      => 'nullable|string|max:255',
            'state'     => 'nullable|string|max:255',
            'zip'       => 'nullable|string|max:40',
            'country'   => 'required|in:' . $countries,
        ]);

        $countryCode = $request->country;
        $country     = $countryData->$countryCode->country;
        $dialCode    = $countryData->$countryCode->dial_code;

        $exists = User::where('mobile', $request->mobile)->where('dial_code', $dialCode)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            return back()->withErrors(['mobile' => 'The mobile number already exists.']);
        }

        $user->mobile    = $request->mobile;
        $user->bname     = $request->bname;
        $user->aname     = $request->aname;
        $user->ano       = $request->ano;
        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;
        $user->email     = $request->email;

        $user->address      = $request->address;
        $user->city         = $request->city;
        $user->state        = $request->state;
        $user->zip          = $request->zip;
        $user->country_name = $country;
        $user->dial_code    = $dialCode;
        $user->country_code = $countryCode;

        $user->ev = $request->boolean('ev') ? Status::VERIFIED : Status::UNVERIFIED;
        $user->sv = $request->boolean('sv') ? Status::VERIFIED : Status::UNVERIFIED;
        $user->ts = $request->boolean('ts') ? Status::ENABLE : Status::DISABLE;
        if (!$request->boolean('kv')) {
            // Turning KYC off deletes the submitted documents, as before; the
            // page asks for confirmation first.
            if ((int) $user->kv === Status::KYC_VERIFIED || $user->kyc_data) {
                $user->kv = Status::KYC_UNVERIFIED;
                if ($user->kyc_data) {
                    foreach ($user->kyc_data as $kycData) {
                        if ($kycData->type == 'file') {
                            fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
                        }
                    }
                }
                $user->kyc_data = null;
            }
        } else {
            $user->kv = Status::KYC_VERIFIED;
        }
        $user->save();

        $notify[] = ['success', 'User details updated successfully'];
        return back()->withNotify($notify);
    }

    /**
     * Change a member's sponsor. The current sponsor is read from the member
     * record: the old form sent it from a read-only box and refused when the
     * two differed ("Mission Impossible AD102"), and a member without a
     * sponsor could never be given one.
     */
    public function update_again(Request $request, $id)
    {
        $request->validate([
            'nsponsor' => 'required|string|max:40',
        ], [], ['nsponsor' => 'new sponsor']);

        $newSponsor = User::where('username', $request->nsponsor)->first();
        if (!$newSponsor) {
            return back()->withErrors(['nsponsor' => 'The new sponsor does not exist.']);
        }

        $user = User::findOrFail($id);
        if ($user->id == $newSponsor->id) {
            return back()->withErrors(['nsponsor' => 'A member cannot sponsor themselves.']);
        }
        if ((int) $user->ref_by === (int) $newSponsor->id) {
            return back()->withErrors(['nsponsor' => 'The new sponsor is the same as the current sponsor.']);
        }
        if ($user->id < $newSponsor->id) {
            return back()->withErrors(['nsponsor' => 'The new sponsor joined after this member, so cannot be their sponsor.']);
        }

        $previousRefBy = $user->ref_by;

        try {
            $changed = DB::transaction(function () use ($user, $newSponsor, $previousRefBy) {
                // Lock and re-check, so a double submit cannot log the same change twice.
                $locked = User::lockForUpdate()->find($user->id);
                if ((int) $locked->ref_by !== (int) $previousRefBy) {
                    return false;
                }

                $locked->ref_by = $newSponsor->id;
                $locked->save();

                SponsorChangeLog::create([
                    'user_id'             => $locked->id,
                    'previous_sponsor_id' => User::whereKey($previousRefBy)->exists() ? $previousRefBy : null,
                    'new_sponsor_id'      => $newSponsor->id,
                    'admin_id'            => auth('admin')->id(),
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withNotify([['error', 'The sponsor could not be changed. Please try again.']]);
        }

        if (!$changed) {
            return back()->withNotify([['error', 'Sponsor was changed by another request. Please reload and try again.']]);
        }

        return back()->withNotify([['success', 'Sponsor updated successfully']]);
    }

    // username change
    /** Change a member's username: the same rules as sign-up. */
    public function update_again1(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nusername' => ['required', 'string', 'min:6', 'max:40', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
        ], [
            'nusername.regex'  => 'Username can contain only letters, numbers and underscore.',
            'nusername.unique' => 'That username is already taken.',
        ], ['nusername' => 'new username']);

        $user->username = $request->nusername;
        $user->save();

        return back()->withNotify([['success', 'Username updated successfully']]);
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


    /**
     * Money Box. Subtracting may take it below zero (the sufficiency check was
     * switched off on purpose in the old code; unchanged).
     */
    public function addSubBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        User::findOrFail($id);
        $amount = (float) $request->amount;
        $add    = $request->act === 'add';
        $trx    = getTrx();

        // One transaction with the member's row locked: two adjustments at
        // the same moment used to overwrite each other's balance.
        try {
            $user = DB::transaction(function () use ($id, $amount, $add, $trx, $request) {
                $user = User::lockForUpdate()->findOrFail($id);
                $user->balance = (float) $user->balance + ($add ? $amount : -$amount);
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = 0;
                $transaction->trx_type     = $add ? '+' : '-';
                $transaction->remark       = $add ? 'balance_add' : 'balance_subtract';
                $transaction->trx          = $trx;
                $transaction->details      = $request->remark;
                $transaction->save();

                return $user;
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        notify($user, $add ? 'BAL_ADD' : 'BAL_SUB', [
            'trx'          => $trx,
            'amount'       => showAmount($amount, currencyFormat: false),
            'remark'       => $request->remark,
            'post_balance' => showAmount($user->balance, currencyFormat: false),
        ]);

        return back()->withNotify([['success', $add ? 'Balance added successfully' : 'Balance subtracted successfully']]);
    }

    //VISA wallet
    public function addSubBalanceVisa(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        User::findOrFail($id);
        $amount = (float) $request->amount;
        $add    = $request->act === 'add';
        $trx    = getTrx();

        // One transaction with the member's row locked: two adjustments at
        // the same moment used to overwrite each other's balance.
        try {
            $user = DB::transaction(function () use ($id, $amount, $add, $trx, $request) {
                $user = User::lockForUpdate()->findOrFail($id);
                if (!$add && $amount > (float) $user->visa) {
                    throw new \DomainException($user->username . ' doesn\'t have sufficient money in the VISA wallet.');
                }
                $user->visa = (float) $user->visa + ($add ? $amount : -$amount);
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $amount;
                $transaction->post_balance = $user->visa;
                $transaction->charge       = 0;
                $transaction->trx_type     = $add ? '+' : '-';
                $transaction->remark       = $add ? 'visa_wallet_add' : 'visa_wallet_subtract';
                $transaction->trx          = $trx;
                $transaction->details      = $request->remark;
                $transaction->save();

                return $user;
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        notify($user, $add ? 'BAL_ADD' : 'BAL_SUB', [
            'trx'          => $trx,
            'amount'       => showAmount($amount, currencyFormat: false),
            'remark'       => $request->remark,
            'post_balance' => showAmount($user->visa, currencyFormat: false),
        ]);

        return back()->withNotify([['success', $add ? 'VISA wallet added successfully' : 'VISA wallet subtracted successfully']]);
    }

    // Re-purchase wallet (repurchase wallet used for buying products)
    public function addSubBalanceProductWallet(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        User::findOrFail($id);
        $amount = (float) $request->amount;
        $add    = $request->act === 'add';
        $trx    = getTrx();

        // One transaction with the member's row locked: two adjustments at
        // the same moment used to overwrite each other's balance.
        try {
            $user = DB::transaction(function () use ($id, $amount, $add, $trx, $request) {
                $user = User::lockForUpdate()->findOrFail($id);
                if (!$add && $amount > (float) $user->product_wallet) {
                    throw new \DomainException($user->username . ' does not have sufficient Re-purchase wallet balance.');
                }
                $user->product_wallet = (float) $user->product_wallet + ($add ? $amount : -$amount);
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $amount;
                $transaction->post_balance = $user->product_wallet;
                $transaction->charge       = 0;
                $transaction->trx_type     = $add ? '+' : '-';
                $transaction->remark       = $add ? 'product_wallet_add' : 'product_wallet_subtract';
                $transaction->trx          = $trx;
                $transaction->details      = $request->remark;
                $transaction->save();

                return $user;
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        notify($user, $add ? 'BAL_ADD' : 'BAL_SUB', [
            'trx'          => $trx,
            'amount'       => showAmount($amount, currencyFormat: false),
            'remark'       => $request->remark,
            'post_balance' => showAmount($user->product_wallet, currencyFormat: false),
        ]);

        return back()->withNotify([['success', $add ? 'Re-purchase wallet credited successfully' : 'Re-purchase wallet debited successfully']]);
    }

    public function login($id)
    {
        Auth::loginUsingId($id);
        return to_route('user.home');
    }

    /**
     * Ban or unban. The page sends which one it means (?state=ban|unban):
     * the old button just flipped the status, so a second click from a stale
     * tab undid the first.
     */
    public function status(Request $request, $id)
    {
        $user   = User::findOrFail($id);
        $active = (int) $user->status === Status::USER_ACTIVE;
        $want   = $request->input('state', $active ? 'ban' : 'unban');

        if (($want === 'ban') !== $active) {
            return back()->withNotify([['info', $active ? 'This user is already active.' : 'This user is already banned.']]);
        }

        if ($want === 'ban') {
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

    /** Block or restore withdrawals (?state=block|unblock, for the same reason as status()). */
    public function toggleWithdrawalBlock(Request $request, $id)
    {
        $user    = User::findOrFail($id);
        $blocked = (bool) $user->withdrawal_blocked;
        $want    = $request->input('state', $blocked ? 'unblock' : 'block');

        if (($want === 'block') === $blocked) {
            return back()->withNotify([['info', $blocked ? 'Withdrawals are already blocked for this user.' : 'Withdrawals are already open for this user.']]);
        }

        if ($want === 'block') {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);
            $user->withdrawal_blocked      = true;
            $user->withdrawal_block_reason = $request->reason;
            $notify[] = ['success', 'Withdrawal access blocked for ' . $user->username];
        } else {
            $user->withdrawal_blocked      = false;
            $user->withdrawal_block_reason = null;
            $notify[] = ['success', 'Withdrawal access restored for ' . $user->username];
        }

        $user->save();
        return back()->withNotify($notify);
    }

    /** Channels switched on in General Settings (email, SMS, push), in that order. */
    private function notificationChannels(): array
    {
        return array_keys(array_filter(['email' => (bool) gs('en'), 'sms' => (bool) gs('sn'), 'push' => (bool) gs('pn')]));
    }

    /** Send Notification to one member (Pages/Admin/Users/NotifyOne.tsx). */
    public function showNotificationSingleForm($id)
    {
        $user = User::findOrFail($id);
        if (!$this->notificationChannels()) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.users.detail', $user->id)->withNotify($notify);
        }

        $title = 'Send Notification to ' . $user->username;

        return Inertia::render('Admin/Users/NotifyOne', [
            'title'    => $title,
            'member'   => AdminPresenter::user($user),
            'channels' => $this->notificationChannels(),
            'urls'     => ['send' => route('admin.users.notification.single', $user->id)],
        ])->withViewData(['pageTitle' => $title]);
    }

    public function sendNotificationSingle(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:65000',
            'via'     => 'required|in:' . implode(',', $this->notificationChannels() ?: ['email', 'sms', 'push']),
            'subject' => 'required_if:via,email,push|nullable|string|max:255',
            'image'   => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if (!$this->notificationChannels()) {
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

    /**
     * The audience names the bulk sender accepts: the keys of
     * User::notifyToUser(). The request used to name the query method itself
     * (User::...->$scope()), so being_sent_to=delete (or the count URL
     * /count-by-segment/delete) would have deleted every active member.
     */
    private function audience(?string $name): ?string
    {
        return $name !== null && array_key_exists($name, User::notifyToUser()) ? $name : null;
    }

    /** Send Notification to many members (Pages/Admin/Users/NotifyAll.tsx), in batches. */
    public function showNotificationAllForm()
    {
        if (!$this->notificationChannels()) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        $run = session('SEND_NOTIFICATION');

        return Inertia::render('Admin/Users/NotifyAll', [
            'channels'  => $this->notificationChannels(),
            'audiences' => collect(User::notifyToUser())->map(fn ($label, $key) => ['value' => $key, 'label' => __($label)])->values(),
            'activeUsers' => User::active()->count(),
            // A run in progress: the page counts down and asks for the next batch.
            'run' => $run ? [
                'via'      => $run['via'],
                'subject'  => $run['subject'] ?? null,
                'audience' => __(User::notifyToUser()[$run['being_sent_to']] ?? ''),
                'total'    => (int) $run['total_user'],
                'sent'     => (int) $run['total_sent'],
                'batch'    => (int) $run['batch'],
                'cooling'  => (int) $run['cooling_time'],
            ] : null,
            'urls' => [
                'send'  => route('admin.users.notification.all.send'),
                'count' => route('admin.users.segment.count', ':audience'),
                'users' => route('admin.users.list'),
            ],
        ])->withViewData(['pageTitle' => 'Notification to Verified Users']);
    }

    /**
     * One batch. The first request carries the message and the settings; they
     * are kept in the session, and each following batch only sends
     * `continue=1`. `cancel=1` stops a run.
     */
    public function sendNotificationAll(Request $request)
    {
        if ($request->boolean('cancel')) {
            session()->forget('SEND_NOTIFICATION');
            return to_route('admin.users.notification.all')->withNotify([['info', 'Sending stopped.']]);
        }

        if (!$this->notificationChannels()) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        $run = session('SEND_NOTIFICATION');

        if (!$run || !$request->boolean('continue')) {
            $request->validate([
                'via'                          => 'required|in:' . implode(',', $this->notificationChannels()),
                'message'                      => 'required|string|max:65000',
                'subject'                      => 'required_if:via,email,push|nullable|string|max:255',
                'start'                        => 'required|integer|gte:1',
                'batch'                        => 'required|integer|gte:1|lte:1000',
                'being_sent_to'                => 'required|in:' . implode(',', array_keys(User::notifyToUser())),
                'cooling_time'                 => 'required|integer|gte:1|lte:3600',
                'number_of_top_deposited_user' => 'required_if:being_sent_to,topDepositedUsers|nullable|integer|gte:1',
                'number_of_days'               => 'required_if:being_sent_to,notLoginUsers|nullable|integer|gte:0',
                'user'                         => 'required_if:being_sent_to,selectedUsers|array',
                'user.*'                       => 'integer',
                'image'                        => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            ], [
                'number_of_days.required_if'               => 'Number of days field is required',
                'number_of_top_deposited_user.required_if' => 'Number of top deposited user field is required',
                'user.required_if'                         => 'Choose at least one member.',
            ]);

            $template = NotificationTemplate::where('act', 'DEFAULT')->where($request->via . '_status', Status::ENABLE)->exists();
            if (!$template) {
                return back()->withNotify([['warning', 'Default notification template is not enabled']]);
            }

            $run = [
                'via'           => $request->via,
                'subject'       => $request->subject,
                'message'       => $request->message,
                'being_sent_to' => $this->audience($request->being_sent_to),
                'user'          => array_map('intval', (array) $request->user),
                'number_of_top_deposited_user' => $request->number_of_top_deposited_user,
                'number_of_days' => $request->number_of_days,
                'start'         => (int) $request->start,
                'batch'         => (int) $request->batch,
                'cooling_time'  => (int) $request->cooling_time,
                // Stored once: the old code only attached the image to the first batch.
                'image'         => $request->via === 'push' && $request->hasFile('image') ? fileUploader($request->image, getFilePath('push')) : null,
                'total_sent'    => 0,
            ];
            $run['total_user'] = $this->audienceCount($run);

            if ($run['total_user'] <= 0) {
                return back()->withNotify([['error', 'Notification recipients were not found among the selected user base.']]);
            }
        }

        // The scopes read their options from the request (selected ids, top N, days).
        $request->merge(['user' => $run['user'], 'number_of_top_deposited_user' => $run['number_of_top_deposited_user'], 'number_of_days' => $run['number_of_days']]);

        // Offset from the admin's own start: the old code restarted from
        // "sent + 1" after the first batch, ignoring it.
        $offset = ($run['start'] - 1) + $run['total_sent'];
        $take   = min($run['batch'], $run['total_user'] - $run['total_sent']);
        $users  = $this->audienceQuery($run)->skip($offset)->limit($take)->get();

        foreach ($users as $user) {
            notify($user, 'DEFAULT', [
                'subject' => $run['subject'],
                'message' => $run['message'],
            ], [$run['via']], pushImage: $run['image']);
        }

        $run['total_sent'] += $take;
        $done = $users->isEmpty() || $run['total_sent'] >= $run['total_user'];

        if ($done) {
            session()->forget('SEND_NOTIFICATION');
            $message = ucfirst($run['via']) . ' notifications were sent successfully';
        } else {
            session()->put('SEND_NOTIFICATION', $run);
            $message = $run['total_sent'] . ' ' . $run['via'] . ' notifications were sent successfully';
        }

        return to_route('admin.users.notification.all')->withNotify([['success', $message]]);
    }

    /** Members of a run's audience, oldest first; the scope name is checked against the list. */
    private function audienceQuery(array $run)
    {
        $scope = $this->audience($run['being_sent_to']) ?? 'selectedUsers';
        if ($scope === 'topDepositedUsers') {
            // Biggest depositors first. The old query sorted by join date first
            // (oldest()), so it took the oldest depositors, not the top ones.
            // The scope's take(N) is replaced by skip()/limit(); the total is capped at N instead.
            return User::active()->topDepositedUsers()->reorder('deposits_sum_amount', 'desc')->orderBy('users.id');
        }

        return User::oldest()->active()->$scope();
    }

    /** How many members a run will reach, from its start position (and at most the top N). */
    private function audienceCount(array $run): int
    {
        request()->merge(['user' => $run['user'], 'number_of_top_deposited_user' => $run['number_of_top_deposited_user'], 'number_of_days' => $run['number_of_days']]);
        $scope = $this->audience($run['being_sent_to']) ?? 'selectedUsers';
        $count = $scope === 'topDepositedUsers'
            ? min(User::active()->hasDepositedUsers()->count(), (int) $run['number_of_top_deposited_user'])
            : User::active()->$scope()->count();

        return max(0, $count - ($run['start'] - 1));
    }

    /** Active members in one audience, for the form's hint (JSON). */
    public function countBySegment($methodName)
    {
        $scope = $this->audience($methodName);
        if (!$scope || in_array($scope, ['selectedUsers', 'topDepositedUsers', 'notLoginUsers'], true)) {
            return response()->json(['count' => null]);
        }

        return response()->json(['count' => User::active()->$scope()->count()]);
    }

    /** Member picker for "Selected Users" (JSON): only what the picker shows. */
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
            // The whole User models were sent before (mobile, address, bank details…).
            'users'   => ['data' => $users->getCollection()->map(fn (User $u) => [
                'id' => $u->id, 'username' => (string) $u->username, 'email' => (string) $u->email, 'fullname' => (string) $u->fullname,
            ])->values()],
            'more'    => $users->hasMorePages(),
        ]);
    }

    public function notificationLog($id)
    {
        // Same page as Report → Notification History, for one member.
        return app(ReportController::class)->notificationPage(request(), User::findOrFail($id));
    }
 
    /** Old URL (/admin/users/tree/{username}): the same page as otherTree(). */
    public function tree($username)
    {
        return to_route('admin.users.other.tree', $username);
    }

    /** A member's binary tree (Pages/Admin/Users/Tree.tsx), searchable by username. */
    public function otherTree(Request $request, $username = null)
    {
        $name = (string) ($request->username ?: $username);
        $user = $name !== '' && $name !== 'search' ? User::where('username', $name)->first() : null;

        if (!$user) {
            $notify[] = ['error', $name !== '' && $name !== 'search' ? 'No member with the username "' . $name . '".' : 'Enter a username to see a tree.'];
            return $request->header('referer') ? back()->withNotify($notify) : to_route('admin.users.all')->withNotify($notify);
        }

        $tree   = showMatrixTree($user->id);
        $parent = parentInMatrix($user->id, 1);
        $title  = 'Tree of ' . $user->fullname;

        return Inertia::render('Admin/Users/Tree', [
            'title'  => $title,
            'owner'  => AdminPresenter::user($user),
            'parent' => $parent ? ['username' => (string) $parent->username, 'url' => route('admin.users.other.tree', $parent->username)] : null,
            'nodes'  => TreePresenter::nodes($tree, slots: false, admin: true),
            'images' => TreePresenter::images(),
            'urls'   => ['tree' => route('admin.users.other.tree')],
        ])->withViewData(['pageTitle' => $title]);
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

    /** Tag or untag as Ambassador (?state=on|off, for the same reason as status()). */
    public function toggleAmbassador(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $is   = (bool) $user->ambassador;
        $want = $request->input('state', $is ? 'off' : 'on');

        if (($want === 'on') === $is) {
            return back()->withNotify([['info', $is ? 'Already an Ambassador.' : 'Not an Ambassador.']]);
        }

        $user->ambassador = $want === 'on';
        $user->save();

        $status   = $user->ambassador ? 'tagged as Ambassador' : 'removed from Ambassador status';
        $notify[] = ['success', $user->fullname . ' has been ' . $status . '.'];
        return back()->withNotify($notify);
    }
}


