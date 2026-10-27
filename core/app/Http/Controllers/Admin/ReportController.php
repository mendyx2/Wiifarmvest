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
use App\Http\Middleware\AdminAccessMiddleware;
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

    /**
     * Double Stage-Out Bonus (Pages/Admin/Reports/StageTwice.tsx): members
     * paid a stage's step-out bonus more than once, with a one-time deduction
     * per member and stage.
     */
    public function stageTwice(Request $request)
    {
        $pageTitle = 'Double Stage-Out Bonus';
        $stage     = $this->stageTwiceStage($request);
        $search    = trim((string) $request->search);

        [$query, $countPerUser, $userIds] = $this->stageTwiceQuery($stage, $search);

        $refunds = Refund::with('admin')
            ->where('stage_id', $stage)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $rows = $query->with('user:id,firstname,lastname,username')
            ->latest()
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(function (Transaction $trx) use ($countPerUser, $refunds) {
                $refund = $refunds[$trx->user_id] ?? null;

                return [
                    'id'          => $trx->id,
                    'trx'         => (string) $trx->trx,
                    'user'        => AdminPresenter::user($trx->user),
                    'times'       => (int) ($countPerUser[$trx->user_id] ?? 1),
                    'when'        => AdminPresenter::when($trx->created_at),
                    'amount'      => showAmount($trx->amount),
                    'postBalance' => showAmount($trx->post_balance),
                    'refund'      => $refund ? [
                        'originalTrx'   => (string) $refund->original_trx,
                        'refundTrx'     => (string) $refund->refund_trx,
                        'amount'        => showAmount($refund->amount),
                        'balanceBefore' => showAmount($refund->balance_before),
                        'balanceAfter'  => showAmount($refund->balance_after),
                        'admin'         => $refund->admin?->name ?? $refund->admin?->username ?? 'System',
                        'date'          => showDateTime($refund->created_at),
                        'note'          => (string) $refund->note,
                    ] : null,
                ];
            });

        $admin = auth('admin')->user();

        return Inertia::render('Admin/Reports/StageTwice', [
            'stage'  => $stage,
            'rows'   => $rows,
            'search' => $search,
            'urls'   => [
                'download' => route('admin.report.stage.twice.download'),
                'refund'   => AdminAccessMiddleware::allows($admin, 'admin.report.stage.twice.refund', 'POST')
                    ? route('admin.report.stage.twice.refund', ':id') : null,
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /**
     * Take one step-out bonus back from a member who was paid it more than
     * once for a stage: a new deduction transaction (the original stays) and a
     * Refund record. At most one per member and stage; the balance may go
     * negative.
     */
    public function stageTwiceRefund(Request $request, $id)
    {
        $trx = Transaction::findOrFail($id);

        // The stage comes from the bonus's own description
        if ($trx->remark !== 'stageOut_commission' || !preg_match('/stage:(\d+)$/', (string) $trx->details, $m)) {
            $notify[] = ['error', 'Only stage-out commission transactions can be refunded.'];
            return back()->withNotify($notify);
        }
        $stageId = (int) $m[1];

        // Only a member who really was paid this stage's bonus twice. The old
        // check took any stage-out bonus, so a member paid once could be
        // charged back by posting that transaction's id.
        $times = Transaction::where('user_id', $trx->user_id)
            ->where('remark', 'stageOut_commission')
            ->where('details', $trx->details)
            ->count();
        if ($times < 2) {
            $notify[] = ['error', 'This member received the Stage ' . $stageId . ' bonus only once; there is nothing to refund.'];
            return back()->withNotify($notify);
        }

        try {
            DB::transaction(function () use ($trx, $stageId) {
                // Lock the member: serialises refunds and balance changes for them
                $user = User::where('id', $trx->user_id)->lockForUpdate()->first();
                if (!$user) {
                    throw new \RuntimeException('user_deleted');
                }

                // Checked inside the lock, so two clicks cannot both refund
                if (Refund::where('user_id', $user->id)->where('stage_id', $stageId)->exists()) {
                    throw new \RuntimeException('already_refunded');
                }

                $balanceBefore = $user->balance;
                $user->balance = $balanceBefore - $trx->amount; // allowed to go negative
                $user->save();

                $refundTrx = getTrx();

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
            $message = match ($e->getMessage()) {
                'user_deleted'     => 'This user account no longer exists and cannot be refunded.',
                'already_refunded' => 'A refund has already been issued for this user on Stage ' . $stageId . '.',
                default            => throw $e,
            };
            return back()->withNotify([['error', $message]]);
        }

        $notify[] = ['success', 'Refund applied. Amount deducted from user balance and recorded.'];
        return back()->withNotify($notify);
    }

    /** The ticked rows (or the whole filtered list) as a CSV file. */
    public function stageTwiceDownload(Request $request)
    {
        $request->validate(['ids' => 'nullable|string|max:5000']);

        $stage  = $this->stageTwiceStage($request);
        $search = trim((string) $request->search);
        $ids    = array_filter(array_map('intval', explode(',', (string) $request->ids)));

        [$query, $countPerUser] = $this->stageTwiceQuery($stage, $search);

        $transactions = $query->when($ids, fn ($q) => $q->whereIn('id', $ids))->with('user')->latest()->get();
        $refunded     = Refund::where('stage_id', $stage)->pluck('user_id')->flip();
        $filename     = 'double-stageout-stage-' . $stage . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($transactions, $stage, $countPerUser, $refunded) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM, so Excel detects the encoding
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['#', 'Full Name', 'Username', 'TRX', 'Times Received', 'Date & Time', 'Amount', 'Post Balance', 'Stage', 'Refund Status']);

            foreach ($transactions as $i => $trx) {
                fputcsv($out, [
                    $i + 1,
                    self::csvText($trx->user?->fullname),
                    self::csvText($trx->user?->username),
                    $trx->trx,
                    ($countPerUser[$trx->user_id] ?? 1) . 'x',
                    $trx->created_at->format('Y-m-d H:i:s'),
                    $trx->amount,
                    $trx->post_balance,
                    $stage,
                    isset($refunded[$trx->user_id]) ? 'Refunded' : 'Pending',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function stageTwiceStage(Request $request): int
    {
        $stage = (int) $request->stage;
        return in_array($stage, [1, 2, 3], true) ? $stage : 1;
    }

    /**
     * The stage's bonus transactions of members paid it at least twice,
     * filtered by search; with how many times each member was paid, and
     * their ids.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Builder, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection}
     */
    private function stageTwiceQuery(int $stage, string $search): array
    {
        $details = 'Step out Bonus gotten from completing stage:' . $stage;

        $countPerUser = Transaction::select('user_id', DB::raw('COUNT(*) as total'))
            ->where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->pluck('total', 'user_id');
        $userIds = $countPerUser->keys();

        $query = Transaction::where('remark', 'stageOut_commission')
            ->where('details', $details)
            ->whereIn('user_id', $userIds)
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('trx', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%"))));

        return [$query, $countPerUser, $userIds];
    }

    /** A name in a CSV cell: one starting with = + - @ would run as a formula in Excel. */
    private static function csvText(?string $value): string
    {
        $value = (string) $value;
        return $value !== '' && str_contains("=+-@\t\r", $value[0]) ? "'" . $value : $value;
    }
}
