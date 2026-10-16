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

class RepurchaseAwardController extends Controller
{
    public function index()
    {
        $pageTitle = 'Repurchase (Unilevel) Awards';
        $awards    = RepurchaseAward::orderBy('required_pv')->get();

        $qualifiedCounts = [];
        $creditedCounts  = [];
        foreach ($awards as $award) {
            $qualifiedCounts[$award->id] = RepurchasePv::where('total_pv', '>=', $award->required_pv)->count();
            $creditedCounts[$award->id]  = RepurchaseAwardCredit::where('repurchase_award_id', $award->id)->count();
        }

        $pvUsers = RepurchasePv::with('user')
            ->orderByDesc('total_pv')
            ->paginate(20);

        return view('admin.repurchase-award.index', compact('pageTitle', 'awards', 'qualifiedCounts', 'creditedCounts', 'pvUsers'));
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
        RepurchaseAward::findOrFail($id)->delete();

        $notify[] = ['success', 'Award deleted'];
        return back()->withNotify($notify);
    }

    public function toggle(int $id)
    {
        $award         = RepurchaseAward::findOrFail($id);
        $award->status = !$award->status;
        $award->save();

        $notify[] = ['success', 'Award status updated'];
        return back()->withNotify($notify);
    }

    public function qualifiedUsers(int $id)
    {
        $award     = RepurchaseAward::findOrFail($id);
        $pageTitle = 'Qualified Users — ' . $award->title;

        $pvUsers = RepurchasePv::with('user')
            ->where('total_pv', '>=', $award->required_pv)
            ->orderByDesc('total_pv')
            ->paginate(30);

        // Build a set of already-credited user IDs for this award
        $creditedUserIds = RepurchaseAwardCredit::where('repurchase_award_id', $award->id)
            ->pluck('user_id')
            ->flip();

        return view('admin.repurchase-award.qualified', compact('pageTitle', 'award', 'pvUsers', 'creditedUserIds'));
    }

    public function creditUser(int $awardId, int $userId)
    {
        $award = RepurchaseAward::findOrFail($awardId);
        $user  = User::lockForUpdate()->findOrFail($userId);

        // Verify the user actually qualifies
        $pvRecord = RepurchasePv::where('user_id', $userId)->first();
        if (!$pvRecord || $pvRecord->total_pv < $award->required_pv) {
            $notify[] = ['error', 'User does not meet the PV threshold for this award.'];
            return back()->withNotify($notify);
        }

        // Guard: already credited for this award
        $alreadyCredited = RepurchaseAwardCredit::where('user_id', $userId)
            ->where('repurchase_award_id', $awardId)
            ->exists();

        if ($alreadyCredited) {
            $notify[] = ['warning', 'This award has already been credited to this user.'];
            return back()->withNotify($notify);
        }

        $credit = null;

        DB::transaction(function () use ($award, $user, $awardId, &$credit) {
            $user->increment('repurchase_award', $award->amount);

            $credit = RepurchaseAwardCredit::create([
                'user_id'              => $user->id,
                'repurchase_award_id'  => $awardId,
                'amount'               => $award->amount,
                'paid_by'              => auth('admin')->id(),
                'paid_at'              => now(),
                'acb_processed'        => false,
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
        });

        // Dispatch ACB upline bonus processing after the transaction commits
        if ($credit) {
            ProcessAcbBonusJob::dispatch($credit->id);
        }

        $notify[] = ['success', '₦' . number_format($award->amount, 2) . ' credited to ' . $user->fullname . ' for "' . $award->title . '"'];
        return back()->withNotify($notify);
    }
}
