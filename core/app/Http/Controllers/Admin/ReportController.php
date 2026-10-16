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

class ReportController extends Controller
{
    public function financial(Request $request, FinancialReportService $reports)
    {
        $pageTitle   = 'Financial Report';
        [$from, $to] = FinancialReportService::rangeFromRequest($request->date);
        $report      = $reports->build($from, $to);

        return view('admin.reports.financial', compact('pageTitle', 'report'));
    }

    public function transaction(Request $request, $userId = null)
    {
        $pageTitle = 'Transaction Logs';

        $remarks = Transaction::distinct('remark')->orderBy('remark')->get('remark');

        $transactions = Transaction::searchable(['trx', 'user:username'])->filter(['trx_type', 'remark'])->dateFilter()->orderBy('id', 'desc')->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }
        $transactions = $transactions->paginate(getPaginate());

        return view('admin.reports.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    public function loginHistory(Request $request)
    {
        $pageTitle = 'User Login History';
        $loginLogs = UserLogin::orderBy('id', 'desc')->searchable(['user:username'])->dateFilter()->with('user')->paginate(getPaginate());
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs'));
    }

    public function loginIpHistory($ip)
    {
        $pageTitle = 'Login by - ' . $ip;
        $loginLogs = UserLogin::where('user_ip', $ip)->orderBy('id', 'desc')->with('user')->paginate(getPaginate());
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs', 'ip'));
    }

    public function notificationHistory(Request $request)
    {
        $pageTitle = 'Notification History';
        $logs      = NotificationLog::orderBy('id', 'desc')->searchable(['user:username'])->dateFilter()->with('user')->paginate(getPaginate());
        return view('admin.reports.notification_history', compact('pageTitle', 'logs'));
    }

    public function emailDetails($id)
    {
        $pageTitle = 'Email Details';
        $email     = NotificationLog::findOrFail($id);
        return view('admin.reports.email_details', compact('pageTitle', 'email'));
    }
 
    public function invest(Request $request, $userId = null)
    {
        $pageTitle = 'Investment Logs';

        $query = Rinvestment::searchable(['trx', 'user:username', 'plan:name'])->dateFilter();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $totalUnits  = (clone $query)->sum('units');
        $totalAmount = (clone $query)->sum('five');

        $investments = $query->with(['user', 'plan'])->latest()->paginate(getPaginate());

        return view('admin.reports.investments', compact('pageTitle', 'investments', 'totalUnits', 'totalAmount', 'userId'));
    }

    public function bvLog(Request $request, $userId = null)
    {
        
        if ($request->type) {
            if ($request->type == 'leftBV') {
                $pageTitle = "Left BV";
            } elseif ($request->type == 'rightBV') {
                $pageTitle = "Right BV";
            } elseif ($request->type == 'cutBV') {
                $pageTitle = "Cut BV";
            } else {
                $pageTitle = "All Paid BV";
            }
            $logs = $this->bvData($request->type);
        } else {
            $pageTitle = "BV Log";
            $logs      = $this->bvData();
        }
        if ($userId) {
            $logs = $logs->where('user_id', $userId);
        }

        $logs = $logs->latest('id')->paginate(getPaginate());

        return view('admin.reports.bvLog', compact('pageTitle', 'logs'));
    }

    protected function bvData($scope = null)
    {
        if ($scope) {
            $logs = BvLog::$scope();
        } else {
            $logs = BvLog::query();
        }
        return $logs->searchable(['user:username']);
    }

    public function refCom(Request $request, $userId = null)
    {
        $pageTitle    = 'Referral Commission Logs';
        $transactions = Transaction::searchable(['trx', 'user:username'])->where('remark', 'referral_commission')->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }
        $transactions = $transactions->latest()->paginate(getPaginate());

        return view('admin.reports.transactions', compact('pageTitle', 'transactions'));
    }

    public function stageOutCom(Request $request, $userId = null)
    {
        $pageTitle    = 'Stage-Out Commission Logs';
        $transactions = Transaction::searchable(['trx', 'user:username'])->where('remark', 'stageOut_commission')->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }
        $transactions = $transactions->latest()->paginate(getPaginate());

        return view('admin.reports.transactions', compact('pageTitle', 'transactions'));
    }

    public function binaryCom(Request $request, $userId = null)
    {
        $pageTitle    = 'Binary Commission Logs';
        $transactions = Transaction::searchable(['trx', 'user:username'])->where('remark', 'binary_commission')->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }
        $transactions = $transactions->latest()->paginate(getPaginate());

        return view('admin.reports.transactions', compact('pageTitle', 'transactions'));
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
