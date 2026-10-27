<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Pin;
use App\Models\User;
use App\Models\Admin;
use App\Models\BvLog;
use App\Models\MatrixStage;
use App\Models\Order;
use App\Models\Stockist;
use App\Models\Deposit;
use App\Lib\CurlRequest;
use App\Constants\Status;
use App\Models\UserExtra;
use App\Models\UserLogin;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use App\Support\AdminLayout;
use Spatie\Permission\Models\Role;
use Psy\Readline\Transient;


class AdminController extends Controller
{ 
    public function __construct()
    {
       
        $this->middleware('admin.role:super-admin');
    }
    public function dashboard()
    {
        $admin = auth('admin')->user();
        //$admin->assignRole('super-admin');


       //dd($admin->hasRole('super-admin')); // returns true

        //dd($admin);

        // if ($admin->name = 'Super Admin') {
        //  $admin->assignRole('admin');
        //    $admin->hasAllRoles(Role::all());

        //    dd($admin->getPermissionNames());
        // }

        $pageTitle = 'Dashboard';

        //Stockist
        $widget['total_stockist']             = Stockist::count();
        //Order
        $widget['total_order']              = Order::count(); 
        $widget['total_cancel_order']       = Order::Cancel()->count();
        $widget['total_order_sales']        = Order::Active()->sum('total_price');
        $widget['total_order_sales_pending'] = Order::InActive()->sum('total_price');
        $widget['total_pending_order']      = Order::InActive()->count();


        // Active by both flags, as the stockist pages count it.
        $widget['active_stockist']          = Stockist::active()->where('is_active', true)->count();
        // Stock orders from stockists waiting for approval (the old count read the retired "sorders" table).
        $widget['stockist_pending_order']   = \App\Models\StockistOrder::where('status', 'pending')->count();
        
        // User Info
        $widget['total_users']             = User::count();

        $widget['verified_users']          = User::active()->count();

        $widget['email_unverified_users']  = User::emailUnverified()->count();
        $widget['mobile_unverified_users'] = User::mobileUnverified()->count();


        //Pin section 
        $widget['total_pins'] = Pin::count();
        $widget['total_pins_user'] = Pin::where('generate_user_id', '!=', NULL)->count();
        $widget['total_pins_admin'] = Pin::where('generate_user_id', NULL)->count();

        $widget['total_admin_used_pins'] = Pin::where('generate_user_id', NULL)->where('status', 1)->count();
        $widget['total_user_used_pins'] = Pin::where('generate_user_id', '!=', NULL)->where('status', 1)->count();




        // user Browsing, Country, Operating Log
        $userLoginData = UserLogin::where('created_at', '>=', Carbon::now()->subDays(30))->get(['browser', 'os', 'country']);

        $chart['user_browser_counter'] = $userLoginData->groupBy('browser')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_os_counter'] = $userLoginData->groupBy('os')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_country_counter'] = $userLoginData->groupBy('country')->map(function ($item, $key) {
            return collect($item)->count();
        })->sort()->reverse()->take(5);

 
        $deposit['total_deposit_amount']        = Deposit::successful()->sum('amount');
        
        $deposit['total_deposit_pending']       = Deposit::pending()->count();
        $deposit['total_deposit_rejected']      = Deposit::rejected()->count();
        $deposit['total_deposit_charge']        = Deposit::successful()->sum('charge');

        $withdrawals['total_withdraw_amount']   = Withdrawal::approved()->sum('amount');
        $withdrawals['total_withdraw_pending']  = Withdrawal::pending()->count();
        $withdrawals['total_withdraw_pending_sum']  = Withdrawal::pending()->sum('amount');
        $withdrawals['total_withdraw_rejected'] = Withdrawal::rejected()->count();
        $withdrawals['total_withdraw_charge']   = Withdrawal::approved()->sum('charge');

        $bv['bvLeft'] = UserExtra::sum('bv_left');
        $bv['bvRight'] = UserExtra::sum('bv_right');
        $bv['totalBvCut'] = BvLog::where('trx_type', '-')->sum('amount');

        
        
        //$widget['last7days_income'] = Transaction::whereDate('created_at', '>=', Carbon::now()->subDays(6))->where('remark', 'purchased_plan')->sum('amount');
        

        

        $widget['total_step_com'] = Transaction::where('bonus_type', 2)->sum('amount');
        $widget['total_binary_com'] = Transaction::where('remark', 'binary_commission')->sum('amount');

        // Blueprint: 4-Pool Financial Governance Balances
        $widget['pools'] = \App\Models\CompanyPoolLedger::getBalances();

        return view('admin.dashboard', compact('pageTitle', 'widget', 'chart','deposit','withdrawals','bv'));
    }


    public function depositAndWithdrawReport(Request $request) {

        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format = $diffInDays > 30 ? '%M-%Y'  : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }
        $deposits = Deposit::successful()
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();


        $withdrawals = Withdrawal::approved()
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $invests = Transaction::where('remark','purchased_plan')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();
        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on' => $date,
                'deposits' => getAmount($deposits->where('created_on', $date)->first()?->amount ?? 0),
                'withdrawals' => getAmount($withdrawals->where('created_on', $date)->first()?->amount ?? 0),
                'invests' => getAmount($invests->where('created_on', $date)->first()?->amount ?? 0)
            ];
        }

        $data = collect($data);

        // Monthly Deposit & Withdraw Report Graph
        $report['created_on']   = $data->pluck('created_on');
        $report['data']     = [
            [
                'name' => 'Deposited',
                'data' => $data->pluck('deposits')
            ],
            [
                'name' => 'Withdrawn',
                'data' => $data->pluck('withdrawals')
            ],
            [
                'name' => 'Invest',
                'data' => $data->pluck('invests')
            ]
        ];

        return response()->json($report);
    }

    public function transactionReport(Request $request) {

        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format = $diffInDays > 30 ? '%M-%Y'  : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $plusTransactions   = Transaction::where('trx_type','+')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $minusTransactions  = Transaction::where('trx_type','-')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();


        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on' => $date,
                'credits' => getAmount($plusTransactions->where('created_on', $date)->first()?->amount ?? 0),
                'debits' => getAmount($minusTransactions->where('created_on', $date)->first()?->amount ?? 0)
            ];
        }

        $data = collect($data);

        // Monthly Deposit & Withdraw Report Graph
        $report['created_on']   = $data->pluck('created_on');
        $report['data']     = [
            [
                'name' => 'Plus Transactions',
                'data' => $data->pluck('credits')
            ],
            [
                'name' => 'Minus Transactions',
                'data' => $data->pluck('debits')
            ]
        ];

        return response()->json($report);
    }


    private function getAllDates($startDate, $endDate) {
        $dates = [];
        $currentDate = new \DateTime($startDate);
        $endDate = new \DateTime($endDate);

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->format('d-F-Y');
            $currentDate->modify('+1 day');
        }

        return $dates;
    }

    private function  getAllMonths($startDate, $endDate) {
        if ($endDate > now()) {
            $endDate = now()->format('Y-m-d');
        }

        $startDate = new \DateTime($startDate);
        $endDate = new \DateTime($endDate);

        $months = [];

        while ($startDate <= $endDate) {
            $months[] = $startDate->format('F-Y');
            $startDate->modify('+1 month');
        }

        return $months;
    }


    /**
     * Profile and password on one page (Pages/Admin/Profile.tsx). They were
     * two pages with the same profile card, one form each.
     */
    public function profile()
    {
        $admin = auth('admin')->user();

        return Inertia::render('Admin/Profile', [
            'admin' => [
                'name'     => (string) $admin->name,
                'username' => (string) $admin->username,
                'email'    => (string) $admin->email,
                'image'    => getImage(getFilePath('adminProfile') . '/' . $admin->image, getFileSize('adminProfile')),
            ],
            'imageSize' => getFileSize('adminProfile'),
            'urls'      => [
                'profile'  => route('admin.profile.update'),
                'password' => route('admin.password.update'),
            ],
        ])->withViewData(['pageTitle' => 'Profile']);
    }

    public function profileUpdate(Request $request)
    {
        $user = auth('admin')->user();

        $request->validate([
            'name'  => 'required|string|max:40',
            // Two admins with one email would share password-reset codes.
            'email' => 'required|email|max:191|unique:admins,email,' . $user->id,
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('adminProfile'), getFileSize('adminProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return to_route('admin.profile')->withNotify($notify);
    }

    /** The old Password Setting page: its form is on the profile page now. */
    public function password()
    {
        return to_route('admin.profile');
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            // Was min:5. Applies to the next change only; current passwords keep working.
            'password'     => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth('admin')->user();
        if (!Hash::check($request->old_password, $user->password)) {
            return back()->withErrors(['old_password' => 'The current password is not correct.']);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        $notify[] = ['success', 'Password changed successfully.'];
        return to_route('admin.profile')->withNotify($notify);
    }

    public function notifications()
    {
        $notifications = AdminNotification::orderBy('id', 'desc')->paginate(getPaginate())
            ->through(fn (AdminNotification $n) => [
                'id'        => $n->id,
                'title'     => __($n->title),
                'time'      => diffForHumans($n->created_at),
                'unread'    => (int) $n->is_read === Status::NO,
                'url'       => route('admin.notification.read', $n->id),
                'deleteUrl' => route('admin.notifications.delete.single', $n->id),
            ]);

        return Inertia::render('Admin/Notifications', [
            'notifications' => $notifications,
            'hasUnread'     => AdminNotification::where('is_read', Status::NO)->exists(),
            'urls'          => [
                'readAll'   => route('admin.notifications.read.all'),
                'deleteAll' => route('admin.notifications.delete.all'),
            ],
        ])->withViewData(['pageTitle' => 'Notifications']);
    }


    public function notificationRead($id){
        $notification = AdminNotification::findOrFail($id);
        $notification->is_read = Status::YES;
        $notification->save();
        $url = $notification->click_url;
        if ($url == '#') {
            $url = url()->previous();
        }
        return redirect($url);
    }

    public function requestReport()
    {
        $pageTitle = 'Your Listed Report & Request';
        $arr['app_name'] = systemDetails()['name'];
        $arr['app_url'] = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASECODE');
        $url = "https://license.viserlab.com/issue/get?".http_build_query($arr);
        $response = CurlRequest::curlContent($url);
        $response = json_decode($response);
        if (!$response || !@$response->status || !@$response->message) {
            return to_route('admin.dashboard')->withErrors('Something went wrong');
        }
        if ($response->status == 'error') {
            return to_route('admin.dashboard')->withErrors($response->message);
        }
        $reports = $response->message[0];
        return view('admin.reports',compact('reports','pageTitle'));
    }

    public function reportSubmit(Request $request)
    {
        $request->validate([
            'type'=>'required|in:bug,feature',
            'message'=>'required',
        ]);
        $url = 'https://license.viserlab.com/issue/add';

        $arr['app_name'] = systemDetails()['name'];
        $arr['app_url'] = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASECODE');
        $arr['req_type'] = $request->type;
        $arr['message'] = $request->message;
        $response = CurlRequest::curlPostContent($url,$arr);
        $response = json_decode($response);
        if (!$response || !@$response->status || !@$response->message) {
            return to_route('admin.dashboard')->withErrors('Something went wrong');
        }
        if ($response->status == 'error') {
            return back()->withErrors($response->message);
        }
        $notify[] = ['success',$response->message];
        return back()->withNotify($notify);
    }

    /** Entries for the top-bar search box (Layouts/AdminLayout.tsx), filtered by permission. */
    public function searchIndex()
    {
        return response()->json(AdminLayout::searchIndex(auth('admin')->user()));
    }

    public function readAllNotification(){
        AdminNotification::where('is_read',Status::NO)->update([
            'is_read'=>Status::YES
        ]);
        $notify[] = ['success','Notifications read successfully'];
        return back()->withNotify($notify);
    }

    public function deleteAllNotification(){
        AdminNotification::truncate();
        $notify[] = ['success','Notifications deleted successfully'];
        return back()->withNotify($notify);
    }

    public function deleteSingleNotification($id){
        AdminNotification::where('id',$id)->delete();
        $notify[] = ['success','Notification deleted successfully'];
        return back()->withNotify($notify);
    }

    public function viewAttachment($fileHash)
    {
        try {
            $filePath = decrypt($fileHash);
        } catch (\Exception $e) {
            abort(403, 'Invalid file reference');
        }

        $storageRoot  = dirname(base_path());
        $absolutePath = $storageRoot . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');
        $realPath     = realpath($absolutePath);
        $allowedBase  = realpath($storageRoot . DIRECTORY_SEPARATOR . 'assets');

        if (!$realPath || !$allowedBase || !str_starts_with($realPath, $allowedBase . DIRECTORY_SEPARATOR)) {
            abort(403, 'Access denied');
        }

        if (!file_exists($realPath)) {
            abort(404);
        }

        return response()->file($realPath);
    }

    public function downloadAttachment($fileHash)
    {
        try {
            $filePath = decrypt($fileHash);
        } catch (\Exception $e) {
            abort(403, 'Invalid file reference');
        }

        // Files are stored relative to the project root (parent of core/)
        $storageRoot  = dirname(base_path());
        $absolutePath = $storageRoot . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');
        $realPath     = realpath($absolutePath);
        $allowedBase  = realpath($storageRoot . DIRECTORY_SEPARATOR . 'assets');

        if (!$realPath || !$allowedBase || !str_starts_with($realPath, $allowedBase . DIRECTORY_SEPARATOR)) {
            abort(403, 'Access denied');
        }

        if (!file_exists($realPath)) {
            $notify[] = ['error', 'File does not exist'];
            return back()->withNotify($notify);
        }

        $filename = slug(gs('site_name')) . '-attachment.' . pathinfo($realPath, PATHINFO_EXTENSION);
        return response()->download($realPath, $filename);
    }


}
