<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Deposits: one list (Pages/Admin/Money/Deposits.tsx) for every status the
 * sidebar links to, and one deposit with Approve / Reject
 * (Pages/Admin/Money/DepositDetail.tsx).
 */
class DepositController extends Controller
{
    private const LISTS = [
        'pending'    => 'Pending Deposits',
        'approved'   => 'Approved Deposits',
        'successful' => 'Successful Deposits',
        'rejected'   => 'Rejected Deposits',
        'initiated'  => 'Initiated Deposits',
    ];

    public function pending($userId = null)
    {
        return $this->listPage('pending', $userId);
    }

    public function approved($userId = null)
    {
        return $this->listPage('approved', $userId);
    }

    public function successful($userId = null)
    {
        return $this->listPage('successful', $userId);
    }

    public function rejected($userId = null)
    {
        return $this->listPage('rejected', $userId);
    }

    public function initiated($userId = null)
    {
        return $this->listPage('initiated', $userId);
    }

    public function deposit($userId = null)
    {
        return $this->listPage(null, $userId);
    }

    /** $scope is one of the fixed LISTS keys above, never request input. */
    private function listPage(?string $scope, $userId)
    {
        $pageTitle = $scope ? self::LISTS[$scope] : 'Deposit History';
        $request   = request();

        $query = ($scope ? Deposit::$scope() : Deposit::query())
            ->when($userId, fn ($q) => $q->where('user_id', (int) $userId))
            ->searchable(['trx', 'user:username'])
            ->dateFilter();

        // ?method= is a gateway alias, or Google Pay's code. An unknown alias used to answer 404.
        $method = (string) $request->query('method', '');
        if ($method !== '') {
            $code = (string) $method === (string) Status::GOOGLE_PAY ? Status::GOOGLE_PAY : Gateway::where('alias', $method)->value('code');
            $query->where('method_code', $code ?? -1);
        }

        // Totals by status, for the "All" page (same filters as the list)
        $summary = $scope ? null : [
            'successful' => showAmount((clone $query)->where('status', Status::PAYMENT_SUCCESS)->sum('amount')),
            'pending'    => showAmount((clone $query)->where('status', Status::PAYMENT_PENDING)->sum('amount')),
            'rejected'   => showAmount((clone $query)->where('status', Status::PAYMENT_REJECT)->sum('amount')),
            'initiated'  => showAmount((clone $query)->where('status', Status::PAYMENT_INITIATE)->sum('amount')),
        ];

        $deposits = $query->with(['user:id,firstname,lastname,username', 'gateway:code,name,alias'])
            ->orderByDesc('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (Deposit $d) => [
                'id'         => $d->id,
                'trx'        => (string) $d->trx,
                'method'     => $this->methodLabel($d),
                'methodKey'  => $d->method_code < 5000 ? (string) $d->gateway?->alias : (string) $d->method_code,
                'user'       => AdminPresenter::user($d->user),
                'when'       => AdminPresenter::when($d->created_at),
                'amount'     => showAmount($d->amount),
                'charge'     => showAmount($d->charge),
                'total'      => showAmount($d->amount + $d->charge),
                'rate'       => showAmount($d->rate, currencyFormat: false) . ' ' . $d->method_currency,
                'final'      => showAmount($d->final_amount, currencyFormat: false) . ' ' . $d->method_currency,
                'status'     => $this->badge($d->status),
                'url'        => route('admin.deposit.details', $d->id),
            ]);

        return Inertia::render('Admin/Money/Deposits', [
            'title'    => $pageTitle,
            'deposits' => $deposits,
            'summary'  => $summary,
            'filters'  => ['search' => (string) $request->query('search', ''), 'date' => AdminPresenter::dateRange($request->query('date')), 'method' => $method],
            'urls'     => [
                'successful' => route('admin.deposit.successful', ['user_id' => $userId]),
                'pending'    => route('admin.deposit.pending', ['user_id' => $userId]),
                'rejected'   => route('admin.deposit.rejected', ['user_id' => $userId]),
                'initiated'  => route('admin.deposit.initiated', ['user_id' => $userId]),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function methodLabel(Deposit $d): string
    {
        return $d->method_code < 5000 ? (string) __($d->gateway?->name ?? 'Unknown') : 'Google Pay';
    }

    private function badge($status): array
    {
        return match ((int) $status) {
            Status::PAYMENT_PENDING  => AdminPresenter::badge('Pending', 'badge--warning'),
            Status::PAYMENT_SUCCESS  => AdminPresenter::badge('Succeed', 'badge--success'),
            Status::PAYMENT_REJECT   => AdminPresenter::badge('Rejected', 'badge--danger'),
            default                  => AdminPresenter::badge('Initiated', 'badge--dark'),
        };
    }

    public function details($id)
    {
        $deposit   = Deposit::where('id', $id)->with(['user', 'gateway'])->firstOrFail();
        $pageTitle = ($deposit->user?->username ?? 'Member') . ' requested ' . showAmount($deposit->amount);
        $manual    = $deposit->method_code >= 1000;

        return Inertia::render('Admin/Money/DepositDetail', [
            'title'   => $pageTitle,
            'deposit' => [
                'id'       => $deposit->id,
                'method'   => $this->methodLabel($deposit),
                'date'     => showDateTime($deposit->created_at),
                'trx'      => (string) $deposit->trx,
                'user'     => AdminPresenter::user($deposit->user),
                'amount'   => showAmount($deposit->amount),
                'charge'   => showAmount($deposit->charge),
                'total'    => showAmount($deposit->amount + $deposit->charge),
                'rate'     => '1 ' . gs('cur_text') . ' = ' . showAmount($deposit->rate, currencyFormat: false) . ' ' . $deposit->baseCurrency(),
                'final'    => showAmount($deposit->final_amount, currencyFormat: false) . ' ' . $deposit->method_currency,
                'status'   => $this->badge($deposit->status),
                'feedback' => $deposit->admin_feedback,
                'pending'  => (int) $deposit->status === Status::PAYMENT_PENDING,
            ],
            // What the member filled in (manual gateways), or the gateway's own reply (automatic ones)
            'fields'  => $manual ? AdminPresenter::formFields($deposit->detail) : [],
            'gateway' => !$manual && $deposit->detail ? AdminPresenter::flatten($deposit->detail) : [],
            'urls'    => ['approve' => route('admin.deposit.approve', $deposit->id), 'reject' => route('admin.deposit.reject')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function approve($id)
    {
        $deposit = Deposit::where('id', $id)->where('status', Status::PAYMENT_PENDING)->first();

        // userDataUpdate() claims the deposit atomically, so it is credited once.
        if (!$deposit || !PaymentController::userDataUpdate($deposit, true)) {
            $notify[] = ['error', 'This deposit has already been processed'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Deposit request approved successfully'];
        return to_route('admin.deposit.pending')->withNotify($notify);
    }

    /**
     * Reject a pending deposit. The status changes only if it is still
     * pending at that moment: the old read-then-save could mark a deposit
     * "rejected" that an approval had just credited.
     */
    public function reject(Request $request)
    {
        $request->validate([
            'id'      => 'required|integer',
            'message' => 'required|string|max:255',
        ]);

        $claimed = DB::transaction(fn () => Deposit::whereKey($request->id)->where('status', Status::PAYMENT_PENDING)
            ->update(['status' => Status::PAYMENT_REJECT, 'admin_feedback' => $request->message]));

        if (!$claimed) {
            $notify[] = ['error', 'This deposit has already been processed'];
            return back()->withNotify($notify);
        }

        $deposit = Deposit::with('user')->find($request->id);
        notify($deposit->user, 'DEPOSIT_REJECT', [
            'method_name'       => $deposit->methodName(),
            'method_currency'   => $deposit->method_currency,
            'method_amount'     => showAmount($deposit->final_amount, currencyFormat: false),
            'amount'            => showAmount($deposit->amount, currencyFormat: false),
            'charge'            => showAmount($deposit->charge, currencyFormat: false),
            'rate'              => showAmount($deposit->rate, currencyFormat: false),
            'trx'               => $deposit->trx,
            'rejection_message' => $request->message,
        ]);

        $notify[] = ['success', 'Deposit request rejected successfully'];
        return to_route('admin.deposit.pending')->withNotify($notify);
    }
}
