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
     * Safe to run on a schedule: the period's pool row is locked and checked
     * again inside the payment transaction, so two runs at once cannot both
     * pay it. A named period is only looked up (asking for a past period used
     * to create an empty pool row). Notes Step 124.
     *
     * @param  string|null  $period  e.g. "Q4-2026"; defaults to current quarter
     * @return array  summary of distribution results
     */
    public function distribute(?string $period = null): array
    {
        $ledger = $period === null
            ? GlobalPoolLedger::currentPeriod()
            : GlobalPoolLedger::where('period', $period)->where('status', GlobalPoolLedger::STATUS_ACCUMULATING)->first();

        if (!$ledger) {
            $done = GlobalPoolLedger::where('period', $period)->where('status', '!=', GlobalPoolLedger::STATUS_ACCUMULATING)->exists();
            return ['status' => $done ? 'already_distributed' : 'empty_pool', 'period' => $period, 'pool' => 0];
        }
        if ((float) $ledger->accumulated_amount <= 0) {
            return ['status' => 'empty_pool', 'period' => $ledger->period, 'pool' => 0];
        }

        // Find qualifying leaders: users who have earned a high-rank award
        $qualifiedUserIds = $this->getQualifiedUserIds();

        if ($qualifiedUserIds->isEmpty()) {
            Log::info('[GlobalShare] No qualified leaders found for period ' . $ledger->period);
            return [
                'status'    => 'no_qualified_leaders',
                'period'    => $ledger->period,
                'pool'      => (float) $ledger->accumulated_amount,
                'qualified' => 0,
            ];
        }

        $result = DB::transaction(function () use ($qualifiedUserIds, $ledger) {
            // Re-read under a lock: another run may have paid this period a moment ago
            $locked = GlobalPoolLedger::whereKey($ledger->id)->lockForUpdate()->first();
            if (!$locked || $locked->status !== GlobalPoolLedger::STATUS_ACCUMULATING) {
                return null;
            }

            $pool           = (float) $locked->accumulated_amount;
            $sharePerLeader = round($pool / $qualifiedUserIds->count(), 2);
            $totalPaid      = 0.0;
            $paid           = 0;
            $failed         = 0;

            foreach ($qualifiedUserIds as $userId) {
                try {
                    $this->wallet->credit(
                        $userId,
                        $sharePerLeader,
                        'global_share',
                        sprintf(
                            'Global Share Pool distribution for %s — ₦%s shared among %d qualified leaders',
                            $locked->period,
                            number_format($pool, 2),
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

            $locked->markDistributed($paid, $totalPaid);

            return compact('pool', 'sharePerLeader', 'paid', 'failed');
        });

        if ($result === null) {
            return ['status' => 'already_distributed', 'period' => $ledger->period];
        }

        Log::info('[GlobalShare] Distribution complete.', [
            'period'    => $ledger->period,
            'pool'      => $result['pool'],
            'qualified' => $qualifiedUserIds->count(),
            'paid'      => $result['paid'],
            'failed'    => $result['failed'],
            'per_share' => $result['sharePerLeader'],
        ]);

        return [
            'status'    => 'distributed',
            'period'    => $ledger->period,
            'pool'      => $result['pool'],
            'qualified' => $qualifiedUserIds->count(),
            'paid'      => $result['paid'],
            'failed'    => $result['failed'],
            'per_share' => $result['sharePerLeader'],
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
