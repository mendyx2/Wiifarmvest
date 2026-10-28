<?php

namespace App\Http\Controllers\Admin;

use App\Models\RepurchaseAward;
use App\Models\RepurchaseAwardCredit;
use App\Models\RepurchasePv;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessAcbBonusJob;
use App\Support\AdminPresenter;
use Inertia\Inertia;

class RepurchaseAwardController extends Controller
{
    /** Shop → Repurchase Awards (Pages/Admin/Shop/RepurchaseAwards.tsx): the awards and the PV leaderboard. */
    public function index()
    {
        $pageTitle = 'Repurchase (Unilevel) Awards';
        $awards    = RepurchaseAward::withCount('credits')->orderBy('required_pv')->get();
        $active    = $awards->where('status', true);

        $pvUsers = RepurchasePv::with('user:id,username,firstname,lastname,email')
            ->orderByDesc('total_pv')
            ->paginate(20)
            ->through(function (RepurchasePv $pv) use ($active) {
                $total = (float) $pv->total_pv;
                $won   = $active->where('required_pv', '<=', $total)->sortByDesc('required_pv');
                $next  = $active->where('required_pv', '>', $total)->sortBy('required_pv')->first();
                return [
                    'id'       => $pv->id,
                    'user'     => AdminPresenter::user($pv->user),
                    'email'    => (string) $pv->user?->email,
                    'pv'       => number_format($total, 2),
                    'top'      => $won->first()?->title,
                    'more'     => max(0, $won->count() - 1),
                    'next'     => $next ? ['title' => $next->title, 'percent' => min(100, round($total / $next->required_pv * 100, 1))] : null,
                ];
            });

        return Inertia::render('Admin/Shop/RepurchaseAwards', [
            'awards' => $awards->map(fn (RepurchaseAward $a) => [
                'id'         => $a->id,
                'title'      => (string) $a->title,
                'requiredPv' => (float) $a->required_pv,
                'amount'     => (float) $a->amount,
                'active'     => (bool) $a->status,
                'qualified'  => RepurchasePv::where('total_pv', '>=', $a->required_pv)->count(),
                'credited'   => (int) $a->credits_count,
                'url'        => route('admin.repurchase-award.qualified', $a->id),
            ])->values(),
            'members' => $pvUsers,
            'urls'    => [
                'store'   => route('admin.repurchase-award.store'),
                'update'  => route('admin.repurchase-award.update', ':id'),
                'destroy' => route('admin.repurchase-award.destroy', ':id'),
                'toggle'  => route('admin.repurchase-award.toggle', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request, int $id = 0)
    {
        $request->validate([
            'title'       => 'required|string|max:150',
            'required_pv' => 'required|numeric|gt:0',
            'amount'      => 'required|numeric|gt:0',
        ]);

        $award              = $id ? RepurchaseAward::findOrFail($id) : new RepurchaseAward();
        $award->title       = trim($request->title);
        $award->required_pv = $request->required_pv;
        $award->amount      = $request->amount;
        if (!$id) {
            $award->status = true;
        }
        $award->save();

        $notify[] = ['success', $id ? 'Award updated successfully' : 'Award created successfully'];
        return back()->withNotify($notify);
    }

    public function destroy(int $id)
    {
        $award = RepurchaseAward::findOrFail($id);
        // Deleting a paid award orphaned its payment records; switching it off keeps them.
        if (RepurchaseAwardCredit::where('repurchase_award_id', $award->id)->exists()) {
            $notify[] = ['error', 'This award has already been paid to members. Switch it off instead of deleting it.'];
            return back()->withNotify($notify);
        }
        $award->delete();

        $notify[] = ['success', 'Award deleted'];
        return back()->withNotify($notify);
    }

    /** `state=on|off` from the React page; without it (old links) it flips. */
    public function toggle(Request $request, int $id)
    {
        $award = RepurchaseAward::findOrFail($id);
        $state = $request->input('state');
        $want  = $state === 'on' ? true : ($state === 'off' ? false : !$award->status);
        if ((bool) $award->status === $want) {
            $notify[] = ['info', 'The award was already ' . ($want ? 'active' : 'inactive') . '.'];
            return back()->withNotify($notify);
        }
        $award->status = $want;
        $award->save();

        $notify[] = ['success', $want ? 'Award activated' : 'Award deactivated'];
        return back()->withNotify($notify);
    }

    /** Members who reached an award's PV (Pages/Admin/Shop/RepurchaseQualified.tsx), with Credit buttons. */
    public function qualifiedUsers(int $id)
    {
        $award     = RepurchaseAward::findOrFail($id);
        $pageTitle = 'Qualified Users — ' . $award->title;

        $credited = RepurchaseAwardCredit::where('repurchase_award_id', $award->id)->pluck('user_id')->flip();

        $members = RepurchasePv::with('user:id,username,firstname,lastname,email')
            ->where('total_pv', '>=', $award->required_pv)
            ->orderByDesc('total_pv')
            ->paginate(30)
            ->through(fn (RepurchasePv $pv) => [
                'id'       => $pv->id,
                'userId'   => $pv->user_id,
                'user'     => AdminPresenter::user($pv->user),
                'email'    => (string) $pv->user?->email,
                'pv'       => number_format((float) $pv->total_pv, 2),
                'surplus'  => number_format((float) $pv->total_pv - (float) $award->required_pv, 2),
                'credited' => isset($credited[$pv->user_id]),
                'creditUrl' => $pv->user ? route('admin.repurchase-award.credit', [$award->id, $pv->user_id]) : null,
            ]);

        return Inertia::render('Admin/Shop/RepurchaseQualified', [
            'award'   => [
                'id'         => $award->id,
                'title'      => (string) $award->title,
                'requiredPv' => number_format((float) $award->required_pv, 2),
                'amount'     => '₦' . number_format((float) $award->amount, 2),
                'active'     => (bool) $award->status,
                'credited'   => $credited->count(),
            ],
            'members' => $members,
            'urls'    => ['index' => route('admin.repurchase-award.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /**
     * Pay an award to one member. The checks and the payment run in one
     * transaction with the member's row locked: the old code checked
     * "already credited" before the transaction (its lock did nothing
     * there), so a double click could pay the award twice.
     */
    public function creditUser(int $awardId, int $userId)
    {
        try {
            [$award, $user, $credit] = DB::transaction(function () use ($awardId, $userId) {
                $award = RepurchaseAward::findOrFail($awardId);
                $user  = User::lockForUpdate()->findOrFail($userId);

                if (!$award->status) {
                    throw new \DomainException('This award is switched off. Activate it before paying it.');
                }

                $pvRecord = RepurchasePv::where('user_id', $userId)->first();
                if (!$pvRecord || $pvRecord->total_pv < $award->required_pv) {
                    throw new \DomainException('User does not meet the PV threshold for this award.');
                }

                if (RepurchaseAwardCredit::where('user_id', $userId)->where('repurchase_award_id', $awardId)->exists()) {
                    throw new \DomainException('This award has already been credited to this user.');
                }

                $user->repurchase_award += $award->amount;
                $user->save();

                $credit = RepurchaseAwardCredit::create([
                    'user_id'             => $user->id,
                    'repurchase_award_id' => $awardId,
                    'amount'              => $award->amount,
                    'paid_by'             => auth('admin')->id(),
                    'paid_at'             => now(),
                    'acb_processed'       => false,
                ]);

                $trx               = new Transaction();
                $trx->user_id      = $user->id;
                $trx->amount       = $award->amount;
                $trx->charge       = 0;
                $trx->trx_type     = '+';
                $trx->details      = 'Repurchase Award: ' . $award->title;
                $trx->remark       = 'repurchase_award';
                $trx->trx          = getTrx();
                $trx->post_balance = $user->repurchase_award;
                $trx->save();

                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    payoutAmount: (float) $award->amount,
                    trx: $trx->trx,
                    description: "Repurchase Award '{$award->title}' paid to {$user->username}",
                    subPool: \App\Models\CompensationReserveLedger::SUB_POOL_SR,
                    bonusType: 'repurchase_award',
                    userId: $user->id
                );

                return [$award, $user, $credit];
            });
        } catch (\DomainException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        // ACB upline bonus, after the payment is committed.
        ProcessAcbBonusJob::dispatch($credit->id);

        $notify[] = ['success', '₦' . number_format($award->amount, 2) . ' credited to ' . $user->fullname . ' for "' . $award->title . '"'];
        return back()->withNotify($notify);
    }
}
