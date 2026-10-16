<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAward;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fires when a user earns a regular award (UserAward creation).
 * Walks 3 upline generations via ref_by; any upline enrolled in acb_users
 * earns a percentage of the award's payment_amount.
 *   Gen 1 → 5%   Gen 2 → 2%   Gen 3 → 1%
 *
 * Idempotent via user_awards.acb_processed.
 */
class ProcessAwardAcbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 120;
    public int $timeout = 60;

    private const RATES = [1 => 0.05, 2 => 0.02, 3 => 0.01];

    public function __construct(public readonly int $userAwardId)
    {
        $this->onQueue('bonuses');
    }

    public function handle(): void
    {
        $userAward = UserAward::with('award')->lockForUpdate()->find($this->userAwardId);

        if (!$userAward || $userAward->acb_processed) {
            return;
        }

        $awardedUser = User::find($userAward->user_id);
        $awardAmount = (float) ($userAward->award->payment_amount ?? 0);

        if (!$awardedUser || $awardAmount <= 0) {
            $userAward->update(['acb_processed' => true]);
            return;
        }

        DB::transaction(function () use ($userAward, $awardedUser, $awardAmount) {
            $current   = $awardedUser;
            $awardName = $userAward->award->name;

            foreach (self::RATES as $gen => $pct) {
                $upline = $current->ref_by
                    ? User::lockForUpdate()->find($current->ref_by)
                    : null;

                if (!$upline) {
                    break;
                }

                if ($upline) {
                    $bonus = round($awardAmount * $pct, 2);
                    $upline->increment('acb', $bonus);
                    $upline->refresh();

                    $uTrx = getTrx();
                    $txn               = new Transaction();
                    $txn->user_id      = $upline->id;
                    $txn->amount       = $bonus;
                    $txn->charge       = 0;
                    $txn->trx_type     = '+';
                    $txn->remark       = 'ucb_bonus';
                    $txn->details      = 'UCB Gen-' . $gen . ' (' . ($pct * 100) . '%) — ' . $awardedUser->username . ' qualified for ' . $awardName;
                    $txn->trx          = $uTrx;
                    $txn->post_balance = $upline->acb;
                    $txn->save();

                    // Debit from isolated Member Compensation Reserve Ledger (Strategic Reserve pool)
                    try {
                        app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                            $bonus,
                            $uTrx,
                            'UCB to ' . $upline->username . ' for ' . $awardedUser->username . ' achieving ' . $awardName,
                            \App\Models\CompensationReserveLedger::SUB_POOL_SR,
                            'ucb_bonus',
                            $upline->id
                        );
                    } catch (\Throwable $e) {
                        Log::warning('[RevenueAllocation] Failed to debit UCB bonus: ' . $e->getMessage());
                    }
                }

                $current = $upline;
            }

            $userAward->update(['acb_processed' => true]);
        });
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessAwardAcbJob failed for user_award #{$this->userAwardId}: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
