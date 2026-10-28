<?php

namespace App\Jobs;

use App\Models\RepurchaseAwardCredit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Achievers Celebrated Bonus (ACB) processor.
 *
 * When a member receives a repurchase award, this job walks up to 3 generations
 * of their upline (via ref_by). Each upline enrolled in the acb_users table
 * receives a share of the award amount:
 *   Gen 1 → 5%   Gen 2 → 2%   Gen 3 → 1%
 *
 * Idempotent: the credit record's acb_processed flag prevents double-payment
 * even if the job is retried.
 */
class ProcessAcbBonusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 120;

    public const RATES = [1 => 0.05, 2 => 0.02, 3 => 0.01];

    public function __construct(
        public readonly int $creditId
    ) {
        $this->onQueue('bonuses');
    }

    public function handle(): void
    {
        $credit = RepurchaseAwardCredit::lockForUpdate()->find($this->creditId);

        if (!$credit || $credit->acb_processed) {
            return;
        }

        $awardedUser  = User::find($credit->user_id);
        $awardAmount  = (float) $credit->amount;

        if (!$awardedUser) {
            $credit->update(['acb_processed' => true]);
            return;
        }

        DB::transaction(function () use ($credit, $awardedUser, $awardAmount) {
            $current = $awardedUser;

            foreach (self::RATES as $gen => $pct) {
                $upline = $current->ref_by ? User::lockForUpdate()->find($current->ref_by) : null;

                if (!$upline) {
                    break;
                }

                if ($upline->isAcb()) {
                    $bonus = round($awardAmount * $pct, 2);
                    $upline->increment('acb', $bonus);
                    $upline->refresh();

                    $txn               = new Transaction();
                    $txn->user_id      = $upline->id;
                    $txn->amount       = $bonus;
                    $txn->charge       = 0;
                    $txn->trx_type     = '+';
                    $txn->remark       = 'acb_bonus';
                    $txn->details      = 'ACB Gen-' . $gen . ' bonus: ' . $awardedUser->username . ' received repurchase award';
                    $txn->trx          = getTrx();
                    $txn->post_balance = $upline->acb;
                    $txn->save();

                    app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                        payoutAmount: $bonus,
                        trx: $txn->trx,
                        description: "ACB Gen-{$gen} bonus: {$awardedUser->username} repurchase award",
                        subPool: \App\Models\CompensationReserveLedger::SUB_POOL_SR,
                        bonusType: 'acb_bonus',
                        userId: $upline->id
                    );
                }

                $current = $upline;
            }

            $credit->update(['acb_processed' => true]);
        });
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessAcbBonusJob failed for credit #{$this->creditId}: " . $e->getMessage());
    }
}
