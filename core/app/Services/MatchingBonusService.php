<?php

namespace App\Services;

use App\Contracts\MatchingBonusServiceInterface;
use App\Models\Matrix;
use App\Models\MatchingBonusLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchingBonusService implements MatchingBonusServiceInterface
{
    public function __construct(
        private readonly LeadershipBonusService $leadership,
        private readonly AutoshipService $autoship,
    ) {
    }

    public const MATCHING_NUMBER = 50;

    private const MATCHING_RATIO = 0.80;

    // -----------------------------------------------------------------------

    public function calculateMatches(float $pvLeft, float $pvRight): int
    {
        if ($pvLeft < self::MATCHING_NUMBER || $pvRight < self::MATCHING_NUMBER) {
            return 0;
        }
        // this picks the minimum noumber so as to calculate the total match you can get.

        return (int) floor(min($pvLeft, $pvRight) / self::MATCHING_NUMBER);
    }

    public function hasAutoshipFulfilled(int $userId): bool
    {
        return $this->autoship->isFulfilled($userId);
    }

    public function processMatrix(Matrix $matrix): ?MatchingBonusLog
    {
        $matrixId = $matrix->id;
        //dd($matrix);

        try {
            $log = DB::transaction(function () use ($matrixId) {
                $matrix = Matrix::lockForUpdate()->find($matrixId);
                //dd($matrix);

                if (!$matrix) {
                    Log::warning('[MatchingBonus] Matrix not found inside lock.', ['matrix_id' => $matrixId]);
                    return null;
                }

                $pvLeft  = (float) $matrix->pv_left_pairing;
                $pvRight = (float) $matrix->pv_right_pairing; 
                $matches = $this->calculateMatches($pvLeft, $pvRight);
                

                if ($matches == 0) {
                    return null;
                }

                $user = User::with('project')->lockForUpdate()->find($matrix->user_id);

                if (!$user) {
                    Log::error('[MatchingBonus] User not found.', ['matrix_id' => $matrixId, 'user_id' => $matrix->user_id]);
                    return null;
                }

                if (!$user->project) {
                    Log::warning('[MatchingBonus] User has no project — skipping.', ['user_id' => $user->id, 'matrix_id' => $matrixId]);
                    return null;
                }

                $bonusPerMatch = (float) $user->project->pairing_per_day;
                //dd($bonusPerMatch);
                if ($bonusPerMatch <= 0) {
                    Log::info('[MatchingBonus] Project pairing_per_day is 0 — no payment.', ['user_id' => $user->id, 'matrix_id' => $matrixId]);
                    return null;
                }

                // ── Daily pairing limit enforcement ────────────────────────
                $maxPairingSlots = (int) $user->project->max_pairing_slots;
                if ($maxPairingSlots > 0) { 
                    $todayMatches = MatchingBonusLog::dailyMatchCount($user->id, now()->toDateString());
                    $remainingSlots = $maxPairingSlots - $todayMatches;

                    if ($remainingSlots <= 0) {
                        Log::info('[MatchingBonus] Daily pairing limit reached.', [
                            'user_id' => $user->id,
                            'matrix_id' => $matrixId,
                            'max_slots' => $maxPairingSlots,
                            'today_matches' => $todayMatches,
                        ]);
                        return null;
                    }

                    // Enforce the remaining daily limit
                    $matches = min($matches, $remainingSlots);

                    if ($matches > $remainingSlots) {
                        Log::info('[MatchingBonus] Daily pairing limit enforced.', [
                            'user_id' => $user->id,
                            'matrix_id' => $matrixId,
                            'calculated_matches' => $this->calculateMatches($pvLeft, $pvRight),
                            'allowed_matches' => $matches,
                            'max_slots' => $maxPairingSlots,
                            'today_matches' => $todayMatches,
                        ]);
                    }
                }
                // ──────────────────────────────────────────────────────────

                $pvDeducted = $matches * self::MATCHING_NUMBER;
                $totalBonus = round($matches * $bonusPerMatch, 2);
                $trxRef     = getTrx();

                // ── Autoship split ─────────────────────────────────────────
                $fulfilled = $this->hasAutoshipFulfilled($user->id);

                if ($fulfilled) {
                    $matchingAmount = $totalBonus;
                    $autoshipAmount = 0.0;
                } else {
                    $matchingAmount = round($totalBonus * self::MATCHING_RATIO, 2);
                    // derive remainder to avoid floating-point drift (80+20 must = 100)
                    $autoshipAmount = (float) bcsub((string) $totalBonus, (string) $matchingAmount, 2);
                }
                // ──────────────────────────────────────────────────────────

                // 1. Deduct PV from both legs.
                $matrix->pv_left_pairing  = $pvLeft  - $pvDeducted;
                $matrix->pv_right_pairing = $pvRight - $pvDeducted;
                $matrix->save();

                // 2. Credit wallets.
                $user->matching_bonus = bcadd((string) $user->matching_bonus, (string) $matchingAmount, 2);
                if ($autoshipAmount > 0) {
                    $user->autoship = bcadd((string) ($user->autoship ?? '0'), (string) $autoshipAmount, 2);
                }
                $user->save();

                // 3. Persist the match log.
                $log = MatchingBonusLog::create([
                    'user_id'            => $user->id,
                    'matrix_id'          => $matrix->id,
                    'project_id'         => $user->project_id,
                    'matches'            => $matches,
                    'pv_per_match'       => self::MATCHING_NUMBER,
                    'bonus_per_match'    => $bonusPerMatch,
                    'total_bonus'        => $totalBonus,
                    'autoship_fulfilled' => $fulfilled,
                    'autoship_amount'    => $autoshipAmount,
                    'pv_left_before'     => $pvLeft,
                    'pv_right_before'    => $pvRight,
                    'pv_left_after'      => $matrix->pv_left_pairing,
                    'pv_right_after'     => $matrix->pv_right_pairing,
                    'trx'                => $trxRef,
                    'processed_at'       => now(),
                ]);

                // 4a. Matching bonus ledger entry.
                $splitNote = $autoshipAmount > 0 ? ' (80% — autoship active)' : '';

                $txn               = new Transaction();
                $txn->user_id      = $user->id;
                $txn->amount       = $matchingAmount;
                $txn->charge       = 0;
                $txn->trx_type     = '+';
                $txn->remark       = 'matching_bonus';
                $txn->details      = "{$matches} match(es) × " . showAmount($bonusPerMatch, currencyFormat: false)
                                   . ' = ' . showAmount($totalBonus, currencyFormat: false) . $splitNote;
                $txn->trx          = $trxRef;
                $txn->post_balance = $user->matching_bonus;
                $txn->save();

                // 4b. Autoship ledger entry (only when split).
                if ($autoshipAmount > 0) {
                    $txnA               = new Transaction();
                    $txnA->user_id      = $user->id;
                    $txnA->amount       = $autoshipAmount;
                    $txnA->charge       = 0;
                    $txnA->trx_type     = '+';
                    $txnA->remark       = 'autoship';
                    $txnA->details      = 'Autoship hold (20% of matching bonus) — buy ' . AutoshipService::MIN_PV . ' PV of products this month to unlock';
                    $txnA->trx          = getTrx();
                    $txnA->post_balance = $user->autoship;
                    $txnA->save();
                }

                // 5. Notify user.
                notify($user, 'MATCHING_BONUS', [
                    'amount'       => showAmount($matchingAmount, currencyFormat: false),
                    'matches'      => $matches,
                    'paid_bv'      => $pvDeducted,
                    'post_balance' => showAmount($user->matching_bonus, currencyFormat: false),
                    'trx'          => $trxRef,
                ]);

                Log::info('[MatchingBonus] Processed successfully.', [
                    'user_id'            => $user->id,
                    'matrix_id'          => $matrix->id,
                    'matches'            => $matches,
                    'total_bonus'        => $totalBonus,
                    'matching_amount'    => $matchingAmount,
                    'autoship_amount'    => $autoshipAmount,
                    'autoship_fulfilled' => $fulfilled,
                    'trx'                => $trxRef,
                ]);

                // Blueprint: Financial Governance (Phase 4) — Debit Member Compensation Reserve
                try {
                    app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                        $totalBonus,
                        $trxRef,
                        'Binary matching bonus payout to ' . $user->username
                    );
                } catch (\Throwable $e) {
                    Log::warning('[RevenueAllocation] Failed to debit compensation reserve: ' . $e->getMessage());
                }

                return $log;
            });

        } catch (\Throwable $e) {
            Log::error('[MatchingBonus] processMatrix failed.', [
                'matrix_id' => $matrixId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        if ($log) {
            $this->payLeaders($log);
        }

        return $log;
    }

    private function payLeaders(MatchingBonusLog $log): void
    {
        try {
            $this->leadership->payForMatching($log->id);
        } catch (\Throwable $e) {
            Log::error('[Leadership] Paying leaders failed; leadership:process will retry.', [
                'matching_bonus_log_id' => $log->id,
                'error'                 => $e->getMessage(),
            ]);
        }
    }
}
