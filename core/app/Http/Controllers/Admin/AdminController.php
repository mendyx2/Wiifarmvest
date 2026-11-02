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
    /**
     * Dashboard (Pages/Admin/Dashboard.tsx): headline figures, the reserve
     * pools, money over time and the last 30 days of logins.
     */
    public function dashboard()
    {
        $pageTitle = 'Dashboard';
        $logins    = UserLogin::where('created_at', '>=', now()->subDays(30))->get(['browser', 'os', 'country']);
        $top       = fn (string $col) => $logins->groupBy(fn ($l) => $l->$col ?: 'Unknown')->map->count()->sortDesc()->take(5)->map(fn ($n, $k) => ['label' => (string) $k, 'count' => $n])->values();
        $range     = [now()->subDays(29)->toDateString(), now()->toDateString()];
        // Every admin sees the dashboard; a group links to its page only for admins who may open it
        $admin = auth('admin')->user();
        $link  = fn (string $name) => \App\Http\Middleware\AdminAccessMiddleware::allows($admin, $name) ? route($name) : null;

        return Inertia::render('Admin/Dashboard', [
            'admin'  => (string) auth('admin')->user()->name,
            'today'  => now()->format('l, F j, Y'),
            'pools'  => \App\Models\CompanyPoolLedger::getBalances(),
            'groups' => [
                ['title' => 'Members', 'icon' => 'las la-users', 'url' => $link('admin.users.all'), 'stats' => [
                    ['Total members', number_format(User::count())],
                    ['Active', number_format(User::active()->count())],
                    ['Email unverified', number_format(User::emailUnverified()->count())],
                    ['Mobile unverified', number_format(User::mobileUnverified()->count())],
                ]],
                ['title' => 'Deposits', 'icon' => 'las la-wallet', 'url' => $link('admin.deposit.list'), 'stats' => [
                    ['Deposited', showAmount(Deposit::successful()->sum('amount'))],
                    ['Pending', number_format(Deposit::pending()->count()), 'warning'],
                    ['Rejected', number_format(Deposit::rejected()->count())],
                    ['Charges', showAmount(Deposit::successful()->sum('charge'))],
                ]],
                ['title' => 'Withdrawals', 'icon' => 'las la-hand-holding-usd', 'url' => $link('admin.withdraw.data.all'), 'stats' => [
                    ['Withdrawn', showAmount(Withdrawal::approved()->sum('amount'))],
                    ['Pending', number_format(Withdrawal::pending()->count()) . ' · ' . showAmount(Withdrawal::pending()->sum('amount')), 'warning'],
                    ['Rejected', number_format(Withdrawal::rejected()->count())],
                    ['Charges', showAmount(Withdrawal::approved()->sum('charge'))],
                ]],
                ['title' => 'Product orders', 'icon' => 'las la-shopping-cart', 'url' => $link('admin.order.index'), 'stats' => [
                    ['Sales', showAmount(Order::Active()->sum('total_price'))],
                    ['Pending value', showAmount(Order::InActive()->sum('total_price'))],
                    ['Orders', number_format(Order::count())],
                    ['Pending', number_format(Order::InActive()->count()), 'warning'],
                    ['Cancelled', number_format(Order::Cancel()->count())],
                ]],
                ['title' => 'Stockists', 'icon' => 'las la-store', 'url' => $link('admin.stockist.index'), 'stats' => [
                    ['Stockists', number_format(Stockist::count())],
                    // Active by both flags, as the stockist pages count it
                    ['Active', number_format(Stockist::active()->where('is_active', true)->count())],
                    ['Stock orders waiting', number_format(\App\Models\StockistOrder::where('status', 'pending')->count()), 'warning'],
                ]],
                ['title' => 'Pins', 'icon' => 'las la-key', 'url' => $link('admin.pin.index'), 'stats' => [
                    ['All pins', number_format(Pin::count())],
                    ['By members', number_format(Pin::whereNotNull('generate_user_id')->count()) . ' · ' . number_format(Pin::whereNotNull('generate_user_id')->where('status', 1)->count()) . ' used'],
                    ['By admins', number_format(Pin::whereNull('generate_user_id')->count()) . ' · ' . number_format(Pin::whereNull('generate_user_id')->where('status', 1)->count()) . ' used'],
                ]],
                ['title' => 'BV and commissions', 'icon' => 'las la-project-diagram', 'url' => null, 'stats' => [
                    ['Left BV', number_format((float) UserExtra::sum('bv_left'))],
                    ['Right BV', number_format((float) UserExtra::sum('bv_right'))],
                    ['BV cut', number_format((float) BvLog::where('trx_type', '-')->sum('amount'))],
                    ['Step commission', showAmount(Transaction::where('bonus_type', 2)->sum('amount'))],
                    ['Binary commission', showAmount(Transaction::where('remark', 'binary_commission')->sum('amount'))],
                ]],
            ],
            'logins' => ['browsers' => $top('browser'), 'os' => $top('os'), 'countries' => $top('country')],
            'charts' => [
                'from'         => $range[0],
                'to'           => $range[1],
                'money'        => $this->moneySeries(...$range),
                'transactions' => $this->transactionSeries(...$range),
            ],
            'currency' => gs('cur_sym'),
            'urls'     => ['money' => route('admin.chart.deposit.withdraw'), 'transactions' => route('admin.chart.transaction')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** start_date / end_date of a chart request: valid dates, in order, at most 2 years apart. */
    private function chartRange(Request $request): array
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date|before_or_equal:' . now()->addDay()->toDateString(),
        ]);
        $from = Carbon::parse($request->start_date);
        $to   = Carbon::parse($request->end_date);
        if ($from->diffInDays($to) > 731) {
            $from = $to->copy()->subDays(731);
        }
        return [$from->toDateString(), $to->toDateString()];
    }

    /**
     * Sums per day (up to 31 days) or per month, with a bucket for every day
     * or month in the range, zero when nothing happened. The buckets are
     * built with the database's own date function, so the same code runs on
     * MySQL and SQLite (DATE_FORMAT alone tied it to MySQL).
     *
     * @param array<string, \Illuminate\Database\Eloquent\Builder> $queries name => query
     */
    private function series(string $from, string $to, array $queries): array
    {
        $monthly = Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 31;
        $sqlite  = \DB::connection()->getDriverName() === 'sqlite';
        $bucket  = $sqlite ? ($monthly ? "strftime('%Y-%m', created_at)" : 'date(created_at)') : ($monthly ? "DATE_FORMAT(created_at, '%Y-%m')" : 'DATE(created_at)');

        $keys = [];
        for ($d = Carbon::parse($from)->startOfDay(); $d->lte(Carbon::parse($to)); $monthly ? $d->addMonthNoOverflow() : $d->addDay()) {
            $keys[$d->format($monthly ? 'Y-m' : 'Y-m-d')] = $d->format($monthly ? 'M Y' : 'd M');
        }

        $series = [];
        foreach ($queries as $name => $query) {
            $sums = $query->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
                ->selectRaw("{$bucket} AS bucket, SUM(amount) AS total")
                ->groupBy('bucket')->pluck('total', 'bucket');
            $series[] = ['name' => $name, 'data' => array_map(fn ($k) => round((float) ($sums[$k] ?? 0), 2), array_keys($keys))];
        }

        return ['labels' => array_values($keys), 'series' => $series];
    }

    private function moneySeries(string $from, string $to): array
    {
        return $this->series($from, $to, [
            'Deposited'       => Deposit::successful(),
            'Withdrawn'       => Withdrawal::approved(),
            // The "Invest" line summed remark "purchased_plan", which nothing writes: it was always 0
            'Share purchases' => Transaction::where('remark', 'shares_purchase'),
        ]);
    }

    private function transactionSeries(string $from, string $to): array
    {
        return $this->series($from, $to, [
            'Credits' => Transaction::where('trx_type', '+'),
            'Debits'  => Transaction::where('trx_type', '-'),
        ]);
    }

    public function depositAndWithdrawReport(Request $request)
    {
        return response()->json($this->moneySeries(...$this->chartRange($request)));
    }

    public function transactionReport(Request $request)
    {
        return response()->json($this->transactionSeries(...$this->chartRange($request)));
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

    /**
     * Report & Request (Pages/Admin/VendorReports.tsx): bug reports and
     * feature requests sent to the script's vendor with the purchase code.
     * Not linked from the panel. When the vendor's server does not answer,
     * the page says so (it used to redirect to the dashboard with an error
     * the React layout does not show).
     */
    public function requestReport()
    {
        $pageTitle = 'Your Listed Report & Request';
        $arr['app_name']      = systemDetails()['name'];
        $arr['app_url']       = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASECODE');
        $response = json_decode((string) CurlRequest::curlContent('https://license.viserlab.com/issue/get?' . http_build_query($arr)));

        $error = null;
        if (!$response || !@$response->status || !@$response->message) {
            $error = 'The vendor\'s server did not answer. Try again later.';
        } elseif ($response->status == 'error') {
            $error = is_string($response->message) ? $response->message : 'The vendor\'s server refused the request.';
        }

        return Inertia::render('Admin/VendorReports', [
            'error'   => $error,
            'reports' => $error ? [] : collect((array) @$response->message[0])->map(fn ($r) => [
                'type'    => (string) @$r->req_type,
                'message' => (string) @$r->message,
                'status'  => (string) @$r->status_text,
                'tone'    => preg_replace('/[^a-z]/', '', (string) @$r->status_class) ?: 'dark',
            ])->values(),
            'url' => route('admin.request.report'),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function reportSubmit(Request $request)
    {
        $request->validate([
            'type'    => 'required|in:bug,feature',
            'message' => 'required|string|max:5000',
        ]);

        $arr['app_name']      = systemDetails()['name'];
        $arr['app_url']       = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASECODE');
        $arr['req_type']      = $request->type;
        $arr['message']       = $request->message;
        $response = json_decode((string) CurlRequest::curlPostContent('https://license.viserlab.com/issue/add', $arr));

        if (!$response || !@$response->status || !@$response->message) {
            $notify[] = ['error', 'The vendor\'s server did not answer. Try again later.'];
        } elseif ($response->status == 'error') {
            $notify[] = ['error', is_string($response->message) ? $response->message : 'The vendor\'s server refused the request.'];
        } else {
            $notify[] = ['success', is_string($response->message) ? $response->message : 'Sent'];
        }
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

    /** A member's document (KYC file, payment proof), shown in the browser. */
    public function viewAttachment($fileHash)
    {
        return \App\Support\PrivateFiles::respond($this->attachmentPath($fileHash), false);
    }

    /** A member's document, as a download. */
    public function downloadAttachment($fileHash)
    {
        return \App\Support\PrivateFiles::respond($this->attachmentPath($fileHash), true);
    }

    /** The links carry the file's path, encrypted, so it cannot be changed or guessed. */
    private function attachmentPath(string $fileHash): string
    {
        try {
            return (string) decrypt($fileHash);
        } catch (\Exception) {
            abort(404);
        }
    }
}
