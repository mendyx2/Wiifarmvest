<?php

namespace App\Services;

use App\Models\Award;
use App\Models\GlobalPoolLedger;
use App\Models\User;
use App\Models\UserAward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Global Share Pool Service
 *
 * Blueprint: Strategic Reserve — Global Share: 1.25%
 *
 * Distributes accumulated pool funds to qualified leaders
 * who have achieved specific rank awards.
 */
class GlobalSharePoolService
{
    public function __construct(private readonly WalletService $wallet)
    {
    }

    /**
     * Minimum award sort_order required to qualify for global share pool.
     * This should correspond to the "Diamond Director" rank or equivalent.
     * Adjust via admin settings or env as needed.
     */
    public const MIN_QUALIFYING_AWARD_SORT_ORDER = 5;

    /**
     * Distribute the global share pool for a given period.
     *
     * @param  string|null  $period  e.g. "Q4-2026"; defaults to current quarter
     * @return array  summary of distribution results
     */
    public function distribute(?string $period = null): array
    {
        $ledger = GlobalPoolLedger::currentPeriod($period);

        if ($ledger->status !== GlobalPoolLedger::STATUS_ACCUMULATING) {
            return ['status' => 'already_distributed', 'period' => $ledger->period];
        }

        $pool = (float) $ledger->accumulated_amount;
        if ($pool <= 0) {
            return ['status' => 'empty_pool', 'period' => $ledger->period, 'pool' => 0];
        }

        // Find qualifying leaders: users who have earned a high-rank award
        $qualifiedUserIds = $this->getQualifiedUserIds();

        if ($qualifiedUserIds->isEmpty()) {
            Log::info('[GlobalShare] No qualified leaders found for period ' . $ledger->period);
            return [
                'status'    => 'no_qualified_leaders',
                'period'    => $ledger->period,
                'pool'      => $pool,
                'qualified' => 0,
            ];
        }

        $sharePerLeader = round($pool / $qualifiedUserIds->count(), 2);
        $totalPaid      = 0.0;
        $paid           = 0;
        $failed         = 0;

        DB::transaction(function () use ($qualifiedUserIds, $sharePerLeader, $ledger, &$totalPaid, &$paid, &$failed) {
            foreach ($qualifiedUserIds as $userId) {
                try {
                    $this->wallet->credit(
                        $userId,
                        $sharePerLeader,
                        'global_share',
                        sprintf(
                            'Global Share Pool distribution for %s — ₦%s shared among %d qualified leaders',
                            $ledger->period,
                            number_format($ledger->accumulated_amount, 2),
                            $qualifiedUserIds->count()
                        ),
                        getTrx(),
                        wallet: 'balance',
                    );
                    $totalPaid += $sharePerLeader;
                    $paid++;
                } catch (\Throwable $e) {
                    Log::error('[GlobalShare] Failed to pay user #' . $userId . ': ' . $e->getMessage());
                    $failed++;
                }
            }

            $ledger->markDistributed($paid, $totalPaid);
        });

        Log::info('[GlobalShare] Distribution complete.', [
            'period'    => $ledger->period,
            'pool'      => $pool,
            'qualified' => $qualifiedUserIds->count(),
            'paid'      => $paid,
            'failed'    => $failed,
            'per_share' => $sharePerLeader,
        ]);

        return [
            'status'    => 'distributed',
            'period'    => $ledger->period,
            'pool'      => $pool,
            'qualified' => $qualifiedUserIds->count(),
            'paid'      => $paid,
            'failed'    => $failed,
            'per_share' => $sharePerLeader,
        ];
    }

    /**
     * Get user IDs who qualify for the global share pool.
     * Qualification: Users who have achieved at least the minimum qualifying award rank.
     */
    protected function getQualifiedUserIds(): \Illuminate\Support\Collection
    {
        // Get award IDs for ranks at or above the qualifying threshold
        $qualifyingAwardIds = Award::active()
            ->where('sort_order', '>=', self::MIN_QUALIFYING_AWARD_SORT_ORDER)
            ->pluck('id');

        if ($qualifyingAwardIds->isEmpty()) {
            return collect();
        }

        // Get distinct users who hold any of these qualifying awards
        return UserAward::whereIn('award_id', $qualifyingAwardIds)
            ->distinct()
            ->pluck('user_id');
    }
}
