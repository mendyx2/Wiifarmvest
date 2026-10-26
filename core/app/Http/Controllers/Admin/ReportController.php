<?php

namespace App\Http\Controllers\Admin;

use App\Models\BvLog;
use App\Models\User;
use App\Models\Refund;
use App\Models\UserLogin;
use App\Models\Transaction;
use App\Models\Rinvestment;
use Illuminate\Http\Request;
use App\Models\NotificationLog;
use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Support\Facades\DB;
use App\Constants\Status;
use App\Support\AdminPresenter;
use Inertia\Inertia;

class ReportController extends Controller
{
    /** Pages/Admin/Reports/Financial.tsx; the numbers come from FinancialReportService. */
    public function financial(Request $request, FinancialReportService $reports)
    {
        [$from, $to] = FinancialReportService::rangeFromRequest($request->date);
        $report      = $reports->build($from, $to);
        $standing    = $report['standing'];

        $group = fn (array $g, string $bg, ?string $note = null) => $this->financialGroup($g, $bg, $note);

        $netChange = !is_null($standing['net_change']) && abs($standing['net_change']) < 1000
            ? ' (' . ($standing['net_change'] > 0 ? '+' : '') . $standing['net_change'] . '%)'
            : '';

        return Inertia::render('Admin/Reports/Financial', [
            'period'    => $report['period'],
            'allTime'   => $report['is_all_time'],
            'totals'    => [
                'income'  => showAmount($report['income']['total']),
                'outflow' => showAmount($report['outflow']['total']),
                'net'     => showAmount($standing['net']),
            ],
            'income'    => array_map(fn ($g) => $group($g, 'success'), $report['income']['groups']),
            'cashIn'    => $group(['label' => 'Cash In — not added to income', 'icon' => 'las la-wallet'] + $report['cash_in'], '1',
                __('Members spend this money on subscriptions, shares and products, which are already counted in Income. Adding it again would count the same naira twice.')),
            'outflow'   => array_map(fn ($g) => $group($g, '9'), $report['outflow']['groups']),
            'cashOut'   => $group(['label' => 'Cash Out — not added to outflow', 'icon' => 'las la-money-bill-wave'] + $report['cash_out'], '1',
                __('Withdrawals pay out bonuses and balances that were already counted in Outflow when they were credited. Loans are lent, not spent, and come back as repayments.')),
            'unclassified' => $report['unclassified'] ? $group([
                'label' => 'Unclassified Credits — not in Income or Outflow',
                'icon'  => 'las la-exclamation-triangle',
                'lines' => array_map(fn ($row) => [
                    'label' => $row['remark'], 'amount' => $row['amount'], 'count' => $row['count'],
                    'note'  => $row['ledger'] . ' ledger · ' . number_format($row['count']) . ' records',
                    'icon'  => 'las la-question-circle', 'link' => route('admin.report.transaction', ['remark' => $row['remark']]),
                ], $report['unclassified']),
            ], 'warning', __('These credits use a ledger remark the report does not know yet. Add each one to config/financial_report.php so it is counted in the right place.')) : null,
            'owedMembers' => $group(['label' => 'Owed to members & stockists', 'icon' => 'las la-users'] + $report['owed']['members'], '10'),
            'owedCompany' => $group(['label' => 'Owed to the company', 'icon' => 'las la-building'] + $report['owed']['company'], 'success'),
            'standing'  => [
                'verdict'      => $standing['verdict'],
                'trend'        => $standing['trend'],
                'net'          => showAmount($standing['net']),
                'netBreakdown' => showAmount($standing['income']) . ' − ' . showAmount($standing['outflow']),
                'margin'       => is_null($standing['margin']) ? '—' : $standing['margin'] . '%',
                'trendNote'    => showAmount($standing['recent_net']) . ' vs ' . showAmount($standing['previous_net']) . $netChange,
                'compareLabel' => __($standing['compare_label']),
                'owedMembers'  => showAmount($report['owed']['members']['total']),
                'owedCompany'  => showAmount($report['owed']['company']['total']),
            ],
            'date'      => AdminPresenter::dateRange($request->query('date')),
        ])->withViewData(['pageTitle' => 'Financial Report']);
    }

    /**
     * One panel of boxes, as the old reports/partials/financial_boxes.blade.php drew
     * it: panels with more than 4 figures hide the empty ones and name them below.
     */
    private function financialGroup(array $group, string $bg, ?string $note): array
    {
        $hideEmpty = count($group['lines']) > 4;
        $box = fn (array $l) => [
            'label' => __($l['label']),
            'icon'  => $l['icon'],
            'link'  => $l['link'],
            'value' => showAmount($l['amount']),
            'note'  => $l['note'] ? __($l['note']) : null,
        ];

        return [
            'label' => __($group['label']),
            'icon'  => $group['icon'],
            'total' => isset($group['total']) ? showAmount($group['total']) : null,
            'bg'    => $bg,
            'boxes' => array_values(array_map($box, $hideEmpty ? array_filter($group['lines'], fn ($l) => $l['amount'] != 0) : $group['lines'])),
            'empty' => array_values(array_map(fn ($l) => __($l['label']), $hideEmpty ? array_filter($group['lines'], fn ($l) => $l['amount'] == 0) : [])),
            'note'  => $note,
        ];
    }

    public function transaction(Request $request, $userId = null)
    {
        $remarks = Transaction::distinct('remark')->orderBy('remark')->pluck('remark')
            ->map(fn ($r) => ['value' => (string) $r, 'label' => __(keyToTitle((string) $r))])->values();

        $transactions = Transaction::searchable(['trx', 'user:username'])->filter(['trx_type', 'remark'])->dateFilter()->orderBy('id', 'desc')->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }

        return $this->transactionPage('Transaction Logs', $transactions, $remarks);
    }

    public function loginHistory(Request $request)
    {
        $logs = UserLogin::orderBy('id', 'desc')->searchable(['user:username'])->dateFilter()->with('user');

        return $this->loginPage('User Login History', $logs, null);
    }

    public function loginIpHistory($ip)
    {
        $logs = UserLogin::where('user_ip', $ip)->orderBy('id', 'desc')->with('user');

        return $this->loginPage('Login by - ' . $ip, $logs, (string) $ip);
    }

    public function notificationHistory(Request $request)
    {
        return $this->notificationPage($request, null);
    }

    /**
     * Pages/Admin/Reports/NotificationHistory.tsx: every notification, or one
     * member's (Manage Users → member → Notifications, which needs the
     * "view users" permission instead of "Report", so it keeps its own route).
     */
    public function notificationPage(Request $request, ?User $user)
    {
        $query = NotificationLog::orderBy('id', 'desc')->with('user');
        $query = $user ? $query->where('user_id', $user->id) : $query->searchable(['user:username'])->dateFilter();

        $logs = $query->paginate(getPaginate())->withQueryString()
            ->through(fn (NotificationLog $log) => [
                'id'      => $log->id,
                'user'    => AdminPresenter::user($log->user),
                'when'    => AdminPresenter::when($log->created_at),
                'type'    => (string) $log->notification_type,
                'typeLabel' => keyToTitle((string) $log->notification_type),
                'sender'  => __((string) $log->sender),
                'subject' => $log->subject ? __($log->subject) : null,
                'sentTo'  => (string) $log->sent_to,
                // Emails open in a sandboxed frame; SMS and push messages are shown as text.
                'detailUrl' => $log->notification_type === 'email' ? route('admin.report.email.details', $log->id) : null,
                'message'   => $log->notification_type === 'email' ? null : (string) $log->message,
                'image'     => $log->notification_type !== 'email' && $log->image ? asset(getFilePath('push') . '/' . $log->image) : null,
            ]);

        $title = $user ? 'Notifications Sent to ' . $user->username : 'Notification History';

        return Inertia::render('Admin/Reports/NotificationHistory', [
            'title'   => $title,
            'logs'    => $logs,
            // One member's list has no filters, as before.
            'filters' => $user ? null : $this->filters($request),
        ])->withViewData(['pageTitle' => $title]);
    }

    /**
     * The body of one email, for the frame on the Notification History page.
     * The page loads it in <iframe sandbox>: email HTML can carry text a
     * member typed (a name, a ticket subject), and it must not run as the admin.
     */
    public function emailDetails($id)
    {
        $pageTitle = 'Email Details';
        $email     = NotificationLog::findOrFail($id);

        return response()->view('admin.reports.email_details', compact('pageTitle', 'email'))
            ->header('Content-Security-Policy', "script-src 'none'; frame-ancestors 'self'")
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function invest(Request $request, $userId = null)
    {
        $query = Rinvestment::searchable(['trx', 'user:username', 'plan:name'])->dateFilter();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $totalUnits  = (clone $query)->sum('units');
        $totalAmount = (clone $query)->sum('five');

        $investments = $query->with(['user', 'plan'])->latest()->paginate(getPaginate())->withQueryString()
            ->through(fn (Rinvestment $inv) => [
                'id'       => $inv->id,
                'user'     => AdminPresenter::user($inv->user),
                'plan'     => $inv->plan?->name ?? '—',
                'units'    => number_format((float) $inv->units),
                'unitCost' => showAmount($inv->unit_cost),
                'total'    => showAmount($inv->five),
                'trx'      => (string) $inv->trx,
                // Same as GlobalStatus::badgeData(), without the HTML.
                'status'   => (int) $inv->status === Status::ENABLE
                    ? AdminPresenter::badge('Enabled', 'badge--success')
                    : AdminPresenter::badge('Disabled', 'badge--warning'),
                'when'     => AdminPresenter::when($inv->created_at),
            ]);

        return Inertia::render('Admin/Reports/Investments', [
            'investments' => $investments,
            'totals'      => [
                'units'  => number_format((float) $totalUnits),
                'amount' => showAmount($totalAmount),
                'count'  => number_format($investments->total()),
            ],
            'filters' => $this->filters($request),
        ])->withViewData(['pageTitle' => 'Investment Logs']);
    }

    public function bvLog(Request $request, $userId = null)
    {
        $titles = ['leftBV' => 'Left BV', 'rightBV' => 'Right BV', 'cutBV' => 'Cut BV', 'paidBV' => 'All Paid BV'];
        // Any other ?type= (including a made-up one) showed "All Paid BV" over the full log.
        $type      = array_key_exists((string) $request->type, $titles) ? (string) $request->type : null;
        $pageTitle = $type ? $titles[$type] : 'BV Log';

        $logs = $this->bvData($type);
        if ($userId) {
            $logs = $logs->where('user_id', $userId);
        }

        $logs = $logs->with('user')->latest('id')->paginate(getPaginate())->withQueryString()
            ->through(fn (BvLog $log) => [
                'id'       => $log->id,
                'user'     => AdminPresenter::user($log->user),
                'amount'   => ($log->trx_type === '+' ? '+ ' : '- ') . getAmount($log->amount),
                'credit'   => $log->trx_type === '+',
                'position' => (int) $log->position === Status::LEFT
                    ? AdminPresenter::badge('Left', 'badge--success')
                    : AdminPresenter::badge('Right', 'badge--primary'),
                'date'     => showDateTime($log->created_at),
                'details'  => (string) $log->details,
            ]);

        return Inertia::render('Admin/Reports/BvLog', [
            'logs'    => $logs,
            'title'   => $pageTitle,
            'filters' => $this->filters($request),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    protected function bvData($scope = null)
    {
        // $scope comes from ?type= in the URL. Only allow the real query scopes:
        // calling any other static method by name (e.g. ?type=truncate) would run it.
        if (in_array($scope, ['leftBV', 'rightBV', 'cutBV', 'paidBV'], true)) {
            $logs = BvLog::$scope();
        } else {
            $logs = BvLog::query();
        }
        return $logs->searchable(['user:username']);
    }

    public function refCom(Request $request, $userId = null)
    {
        return $this->commissionPage('Referral Commission Logs', 'referral_commission', $userId);
    }

    public function stageOutCom(Request $request, $userId = null)
    {
        return $this->commissionPage('Stage-Out Commission Logs', 'stageOut_commission', $userId);
    }

    public function binaryCom(Request $request, $userId = null)
    {
        return $this->commissionPage('Binary Commission Logs', 'binary_commission', $userId);
    }

    /** One remark's transactions (no Type/Remark filters, as before). */
    private function commissionPage(string $title, string $remark, $userId)
    {
        $transactions = Transaction::searchable(['trx', 'user:username'])->where('remark', $remark)->dateFilter()->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }

        return $this->transactionPage($title, $transactions->latest('id'), null);
    }

    /** Pages/Admin/Reports/Transactions.tsx: the full log, or one commission type when $remarks is null. */
    private function transactionPage(string $title, $query, $remarks)
    {
        $transactions = $query->paginate(getPaginate())->withQueryString()
            ->through(fn (Transaction $trx) => [
                'id'          => $trx->id,
                'user'        => AdminPresenter::user($trx->user),
                'trx'         => (string) $trx->trx,
                'when'        => AdminPresenter::when($trx->created_at),
                'amount'      => $trx->trx_type . ' ' . showAmount($trx->amount),
                'credit'      => $trx->trx_type === '+',
                'postBalance' => showAmount($trx->post_balance),
                'details'     => __((string) $trx->details),
            ]);

        return Inertia::render('Admin/Reports/Transactions', [
            'title'        => $title,
            'transactions' => $transactions,
            'remarks'      => $remarks,
            'filters'      => $this->filters(request()),
        ])->withViewData(['pageTitle' => $title]);
    }

    private function loginPage(string $title, $query, ?string $ip)
    {
        $logs = $query->paginate(getPaginate())->withQueryString()
            ->through(fn (UserLogin $log) => [
                'id'       => $log->id,
                'user'     => AdminPresenter::user($log->user),
                'when'     => AdminPresenter::when($log->created_at),
                'ip'       => (string) $log->user_ip,
                'ipUrl'    => $log->user_ip ? route('admin.report.login.ipHistory', [$log->user_ip]) : null,
                'location' => trim(__((string) $log->city) . ', ' . __((string) $log->country), ', '),
                'browser'  => __((string) $log->browser),
                'os'       => __((string) $log->os),
            ]);

        return Inertia::render('Admin/Reports/Logins', [
            'title'   => $title,
            'logs'    => $logs,
            'ip'      => $ip,
            'filters' => $this->filters(request()),
            'urls'    => ['all' => route('admin.report.login.history')],
        ])->withViewData(['pageTitle' => $title]);
    }

    /** The list filters, sent back so the inputs show what is applied. */
    private function filters(Request $request): array
    {
        return [
            'search'   => (string) $request->query('search', ''),
            'trx_type' => (string) $request->query('trx_type', ''),
            'remark'   => (string) $request->query('remark', ''),
            'type'     => (string) $request->query('type', ''),
            'date'     => AdminPresenter::dateRange($request->query('date')),
        ];
    }

    public function stageTwice(Request $request)
    {
        $pageTitle = 'Double Stage-Out Bonus';
        $stage     = (int) ($request->stage ?? 1);
        $stage     = in_array($stage, [1, 2, 3]) ? $stage : 1;
        $search    = $request->search;

        $details = 'Step out Bonus gotten from completing stage:' . $stage;

        $duplicateUserIds = Transaction::select('user_id')
            ->where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->pluck('user_id');

        $query = Transaction::with('user')
            ->where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->whereIn('user_id', $duplicateUserIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('trx', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('username', 'LIKE', "%{$search}%")
                      ->orWhere('firstname', 'LIKE', "%{$search}%")
                      ->orWhere('lastname', 'LIKE', "%{$search}%"));
            });
        }

        $transactions = $query->latest()->paginate(getPaginate())->appends($request->query());

        $countPerUser = Transaction::select('user_id', DB::raw('COUNT(*) as total'))
            ->where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->whereIn('user_id', $duplicateUserIds)
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        // Full refund records keyed by user_id for O(1) lookup + detail display
        $refundedMap = Refund::with('admin')
            ->where('stage_id', $stage)
            ->whereIn('user_id', $duplicateUserIds)
            ->get()
            ->keyBy('user_id');

        return view('admin.reports.stage_twice', compact(
            'pageTitle', 'transactions', 'stage', 'countPerUser', 'search', 'refundedMap'
        ));
    }

    public function stageTwiceRefund(Request $request, $id)
    {
        $trx = Transaction::findOrFail($id);

        if ($trx->remark !== 'stageOut_commission') {
            $notify[] = ['error', 'Only stage-out commission transactions can be refunded.'];
            return back()->withNotify($notify);
        }

        // Derive stage from the transaction details
        if (!preg_match('/stage:(\d+)$/', $trx->details, $m)) {
            $notify[] = ['error', 'Could not determine stage from transaction details.'];
            return back()->withNotify($notify);
        }
        $stageId = (int) $m[1];

        // Pre-flight: ensure the user account still exists (may have been hard-deleted)
        if (!User::where('id', $trx->user_id)->exists()) {
            $notify[] = ['error', 'This user account no longer exists and cannot be refunded.'];
            return back()->withNotify($notify);
        }

        // Pre-flight check (fast path, before acquiring locks)
        if (Refund::where('user_id', $trx->user_id)->where('stage_id', $stageId)->exists()) {
            $notify[] = ['error', 'A refund has already been issued for this user on Stage ' . $stageId . '.'];
            return back()->withNotify($notify);
        }

        try {
            DB::transaction(function () use ($trx, $stageId) {
                // Lock the user row to prevent concurrent balance updates
                $user = User::where('id', $trx->user_id)->lockForUpdate()->first();

                // Guard: user could be deleted between the pre-flight check and the lock
                if (!$user) {
                    throw new \RuntimeException('user_deleted');
                }

                // Re-check inside the lock — guards against race conditions
                $existingRefund = Refund::where('user_id', $user->id)
                    ->where('stage_id', $stageId)
                    ->lockForUpdate()
                    ->first();

                if ($existingRefund) {
                    throw new \RuntimeException('already_refunded');
                }

                $balanceBefore = $user->balance;
                $user->balance = $balanceBefore - $trx->amount; // allowed to go negative
                $user->save();

                $refundTrx = getTrx();

                // Deduction transaction — original is untouched
                $deduction               = new Transaction();
                $deduction->user_id      = $user->id;
                $deduction->amount       = $trx->amount;
                $deduction->charge       = 0;
                $deduction->trx_type     = '-';
                $deduction->post_balance = $user->balance;
                $deduction->remark       = 'stage_twice_refund';
                $deduction->trx          = $refundTrx;
                $deduction->details      = 'Refund of duplicate Step out Bonus (Stage ' . $stageId . ') — Original TRX: ' . $trx->trx;
                $deduction->save();

                // Refund audit record
                $refund                 = new Refund();
                $refund->user_id        = $user->id;
                $refund->stage_id       = $stageId;
                $refund->original_trx   = $trx->trx;
                $refund->refund_trx     = $refundTrx;
                $refund->amount         = $trx->amount;
                $refund->balance_before = $balanceBefore;
                $refund->balance_after  = $user->balance;
                $refund->admin_id       = auth('admin')->id();
                $refund->note           = 'Duplicate stage-out bonus deduction initiated by admin.';
                $refund->save();
            });
        } catch (\RuntimeException $e) {
            switch ($e->getMessage()) {
                case 'user_deleted':
                    $notify[] = ['error', 'This user account no longer exists and cannot be refunded.'];
                    return back()->withNotify($notify);
                case 'already_refunded':
                    $notify[] = ['error', 'A refund has already been issued for this user on Stage ' . $stageId . '.'];
                    return back()->withNotify($notify);
                default:
                    throw $e;
            }
        }

        $notify[] = ['success', 'Refund applied. Amount deducted from user balance and recorded.'];
        return back()->withNotify($notify);
    }

    public function stageTwiceDownload(Request $request)
    {
        $stage   = (int) ($request->stage ?? 1);
        $stage   = in_array($stage, [1, 2, 3]) ? $stage : 1;
        $search  = $request->search;
        $rawIds  = array_filter(array_map('intval', explode(',', $request->ids ?? '')));

        $details = 'Step out Bonus gotten from completing stage:' . $stage;

        $duplicateUserIds = Transaction::select('user_id')
            ->where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->pluck('user_id');

        $query = Transaction::with('user')
            ->where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->whereIn('user_id', $duplicateUserIds);

        // Scope to only the rows the admin checked
        if (!empty($rawIds)) {
            $query->whereIn('id', $rawIds);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('trx', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('username', 'LIKE', "%{$search}%")
                      ->orWhere('firstname', 'LIKE', "%{$search}%")
                      ->orWhere('lastname', 'LIKE', "%{$search}%"));
            });
        }

        $allTransactions = $query->latest()->get();

        $countPerUser = Transaction::select('user_id', DB::raw('COUNT(*) as total'))
            ->where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->whereIn('user_id', $duplicateUserIds)
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $refundedUserIds = Refund::where('stage_id', $stage)
            ->pluck('user_id')
            ->mapWithKeys(fn ($id) => [$id => true])
            ->toArray();

        $filename = 'double-stageout-stage-' . $stage . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($allTransactions, $stage, $countPerUser, $refundedUserIds) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM — makes Excel auto-detect encoding correctly
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['#', 'Full Name', 'Username', 'TRX', 'Times Received', 'Date & Time', 'Amount', 'Post Balance', 'Stage', 'Refund Status']);

            foreach ($allTransactions as $i => $trx) {
                fputcsv($out, [
                    $i + 1,
                    $trx->user?->fullname,
                    $trx->user?->username,
                    $trx->trx,
                    ($countPerUser[$trx->user_id] ?? 1) . 'x',
                    $trx->created_at->format('Y-m-d H:i:s'),
                    $trx->amount,
                    $trx->post_balance,
                    $stage,
                    array_key_exists($trx->user_id, $refundedUserIds) ? 'Refunded' : 'Pending',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
