<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\WithdrawMethod;
use App\Services\WalletService;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Withdrawals: one list for every status (Pages/Admin/Money/Withdrawals.tsx)
 * with bulk approve / reject, and one request (WithdrawalDetail.tsx).
 *
 * Approving or rejecting first claims the request: its status changes only
 * if it is still pending at that moment. Only then is the member notified
 * or refunded, so a double click, two admins, or a bulk action overlapping a
 * single one can no longer refund the same request twice.
 */
class WithdrawalController extends Controller
{
    private const LISTS = [
        'pending'  => 'Pending Withdrawals',
        'approved' => 'Approved Withdrawals',
        'rejected' => 'Rejected Withdrawals',
    ];

    public function pending($userId = null)
    {
        return $this->listPage('pending', $userId);
    }

    public function approved($userId = null)
    {
        return $this->listPage('approved', $userId);
    }

    public function rejected($userId = null)
    {
        return $this->listPage('rejected', $userId);
    }

    public function all($userId = null)
    {
        return $this->listPage(null, $userId);
    }

    /** $scope is one of the fixed LISTS keys above, never request input. */
    private function listPage(?string $scope, $userId)
    {
        $pageTitle = $scope ? self::LISTS[$scope] : 'All Withdrawals';
        $request   = request();
        $methodId  = (int) $request->query('method');

        $query = ($scope ? Withdrawal::$scope() : Withdrawal::where('status', '!=', Status::PAYMENT_INITIATE))
            ->when($userId, fn ($q) => $q->where('user_id', (int) $userId))
            ->when($methodId, fn ($q) => $q->where('method_id', $methodId))
            ->searchable(['trx', 'user:username'])
            ->dateFilter();

        $summary = $scope ? null : [
            'successful' => showAmount((clone $query)->where('status', Status::PAYMENT_SUCCESS)->sum('amount')),
            'pending'    => showAmount((clone $query)->where('status', Status::PAYMENT_PENDING)->sum('amount')),
            'rejected'   => showAmount((clone $query)->where('status', Status::PAYMENT_REJECT)->sum('amount')),
        ];

        $withdrawals = $query->with(['user:id,firstname,lastname,username,balance', 'method:id,name'])
            ->orderByDesc('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (Withdrawal $w) => [
                'id'       => $w->id,
                'trx'      => (string) $w->trx,
                'method'   => ['id' => $w->method_id, 'name' => (string) __($w->method?->name ?? 'Unknown')],
                'user'     => AdminPresenter::user($w->user),
                'balance'  => showAmount($w->user?->balance ?? 0),
                'when'     => AdminPresenter::when($w->created_at),
                'amount'   => showAmount($w->amount),
                'charge'   => showAmount($w->charge),
                'net'      => showAmount($w->amount - $w->charge),
                'rate'     => showAmount($w->rate, currencyFormat: false) . ' ' . $w->currency,
                'final'    => showAmount($w->final_amount, currencyFormat: false) . ' ' . $w->currency,
                'status'   => $this->badge($w->status),
                'pending'  => (int) $w->status === Status::PAYMENT_PENDING,
                'url'      => route('admin.withdraw.data.details', $w->id),
            ]);

        return Inertia::render('Admin/Money/Withdrawals', [
            'title'       => $pageTitle,
            'withdrawals' => $withdrawals,
            'summary'     => $summary,
            'methods'     => WithdrawMethod::orderBy('name')->get(['id', 'name'])->map(fn ($m) => ['id' => $m->id, 'name' => (string) $m->name]),
            'filters'     => ['search' => (string) $request->query('search', ''), 'date' => AdminPresenter::dateRange($request->query('date')), 'method' => $methodId ? (string) $methodId : ''],
            'urls'        => [
                'bulk'     => route('admin.withdraw.data.bulk-action'),
                'approved' => route('admin.withdraw.data.approved', ['user_id' => $userId]),
                'pending'  => route('admin.withdraw.data.pending', ['user_id' => $userId]),
                'rejected' => route('admin.withdraw.data.rejected', ['user_id' => $userId]),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function badge($status): array
    {
        return match ((int) $status) {
            Status::PAYMENT_PENDING => AdminPresenter::badge('Pending', 'badge--warning'),
            Status::PAYMENT_SUCCESS => AdminPresenter::badge('Approved', 'badge--success'),
            Status::PAYMENT_REJECT  => AdminPresenter::badge('Rejected', 'badge--danger'),
            default                 => AdminPresenter::badge('Initiated', 'badge--dark'),
        };
    }

    public function details($id)
    {
        $withdrawal = Withdrawal::where('id', $id)->where('status', '!=', Status::PAYMENT_INITIATE)->with(['user', 'method'])->firstOrFail();
        $user       = $withdrawal->user;
        $pageTitle  = ($user?->username ?? 'Member') . ' Withdraw Requested ' . showAmount($withdrawal->amount);

        return Inertia::render('Admin/Money/WithdrawalDetail', [
            'title'      => $pageTitle,
            'withdrawal' => [
                'id'       => $withdrawal->id,
                'method'   => (string) __($withdrawal->method?->name ?? 'Unknown'),
                'date'     => showDateTime($withdrawal->created_at),
                'trx'      => (string) $withdrawal->trx,
                'user'     => AdminPresenter::user($user),
                'amount'   => showAmount($withdrawal->amount),
                'charge'   => showAmount($withdrawal->charge),
                'net'      => showAmount($withdrawal->after_charge),
                'rate'     => '1 ' . gs('cur_text') . ' = ' . showAmount($withdrawal->rate, currencyFormat: false) . ' ' . $withdrawal->currency,
                'payable'  => showAmount($withdrawal->final_amount, currencyFormat: false) . ' ' . $withdrawal->currency,
                'status'   => $this->badge($withdrawal->status),
                'feedback' => $withdrawal->admin_feedback,
                'pending'  => (int) $withdrawal->status === Status::PAYMENT_PENDING,
            ],
            'bank'   => $user ? ['bank' => (string) $user->bname, 'name' => (string) $user->aname, 'number' => (string) $user->ano, 'balance' => showAmount($user->balance)] : null,
            'fields' => AdminPresenter::formFields($withdrawal->withdraw_information),
            'urls'   => ['approve' => route('admin.withdraw.data.approve'), 'reject' => route('admin.withdraw.data.reject')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function approve(Request $request)
    {
        $request->validate(['id' => 'required|integer', 'details' => 'required|string|max:255']);

        if (!$this->approveOne((int) $request->id, $request->details)) {
            $notify[] = ['error', 'This withdrawal has already been processed'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Withdrawal approved successfully'];
        return to_route('admin.withdraw.data.pending')->withNotify($notify);
    }

    public function reject(Request $request)
    {
        $request->validate(['id' => 'required|integer', 'details' => 'required|string|max:255']);

        if (!$this->rejectOne((int) $request->id, $request->details)) {
            $notify[] = ['error', 'This withdrawal has already been processed'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Withdrawal rejected successfully'];
        return to_route('admin.withdraw.data.pending')->withNotify($notify);
    }

    /**
     * Approve or reject many pending requests. Each one is claimed on its
     * own: one that was already processed is skipped and counted, where the
     * old loop stopped with a 404 halfway through, leaving the rest undone.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'withdrawal_ids'   => 'required|array|max:200',
            'withdrawal_ids.*' => 'integer',
            'action'           => 'required|in:approve,cancel',
            'details'          => 'nullable|string|max:255',
        ]);

        $approve = $request->action === 'approve';
        $note    = $request->details ?: ($approve ? 'Bulk approval' : 'Bulk rejection');
        $done    = 0;
        foreach (array_unique(array_map('intval', $request->withdrawal_ids)) as $id) {
            $done += (int) ($approve ? $this->approveOne($id, $note) : $this->rejectOne($id, $note));
        }
        $skipped = count(array_unique($request->withdrawal_ids)) - $done;

        $notify[] = ['success', ($approve ? 'Approved ' : 'Rejected ') . $done . ' withdrawal(s)' . ($skipped ? "; {$skipped} already processed and skipped" : '') . '.'];
        return back()->withNotify($notify);
    }

    private function approveOne(int $id, string $details): bool
    {
        $claimed = Withdrawal::whereKey($id)->where('status', Status::PAYMENT_PENDING)
            ->update(['status' => Status::PAYMENT_SUCCESS, 'admin_feedback' => $details]);
        if (!$claimed) {
            return false;
        }

        $withdraw = Withdrawal::with('user', 'method')->find($id);
        notify($withdraw->user, 'WITHDRAW_APPROVE', $this->shortCodes($withdraw) + ['admin_details' => $details]);

        return true;
    }

    /** Claim, refund the amount to the Money Box (locked, via WalletService), then notify. */
    private function rejectOne(int $id, string $details): bool
    {
        $withdraw = DB::transaction(function () use ($id, $details) {
            $claimed = Withdrawal::whereKey($id)->where('status', Status::PAYMENT_PENDING)
                ->update(['status' => Status::PAYMENT_REJECT, 'admin_feedback' => $details]);
            if (!$claimed) {
                return null;
            }

            $withdraw = Withdrawal::with('user', 'method')->find($id);
            app(WalletService::class)->credit($withdraw->user, (float) $withdraw->amount, 'withdraw_reject', 'Refunded for withdrawal rejection', $withdraw->trx);

            return $withdraw;
        });
        if (!$withdraw) {
            return false;
        }

        $withdraw->user->refresh();
        notify($withdraw->user, 'WITHDRAW_REJECT', $this->shortCodes($withdraw) + [
            'post_balance'  => showAmount($withdraw->user->balance, currencyFormat: false),
            'admin_details' => $details,
        ]);

        return true;
    }

    private function shortCodes(Withdrawal $w): array
    {
        return [
            'method_name'     => $w->method?->name,
            'method_currency' => $w->currency,
            'method_amount'   => showAmount($w->final_amount, currencyFormat: false),
            'amount'          => showAmount($w->amount, currencyFormat: false),
            'charge'          => showAmount($w->charge, currencyFormat: false),
            'rate'            => showAmount($w->rate, currencyFormat: false),
            'trx'             => $w->trx,
        ];
    }
}
