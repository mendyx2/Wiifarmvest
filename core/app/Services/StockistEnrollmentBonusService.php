<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\CompensationReserveLedger;
use App\Models\Stockist;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stockist Enrollment Bonus Service
 *
 * Proposed Business Compensation Plan (Section 12, Page 7 & 8): when a member
 * becomes a stockist, ten generations of their upline are paid once.
 *
 * BASIC STORE (₦3 Million / 3,000 PV):
 * - Gen 1: ₦50,000
 * - Gen 2: ₦30,000
 * - Gen 3 to 10: ₦20,000 each (Total = ₦240,000)
 *
 * MEGA STORE (₦10 Million / 10,000 PV):
 * - Gen 1: ₦150,000
 * - Gen 2: ₦100,000
 * - Gen 3 to 10: ₦50,000 each (Total = ₦650,000)
 *
 * Generation 1 is the stockist's sponsor (sponsor_user_id, else the member
 * who referred them); each next generation is the one who referred the last.
 * A banned member is not paid, and the generation still counts (no
 * compression). The store's PV goes up the matrix tree for recognition awards.
 *
 * Paid once per stockist: stockists.enrollment_bonus_paid_at is claimed in the
 * same database transaction as the payments (notes Step 124).
 */
class StockistEnrollmentBonusService
{
    public const REMARK = 'stockist_enrollment_bonus';

    private const BASIC_SCHEDULE = [
        1  => 50000.00,
        2  => 30000.00,
        3  => 20000.00,
        4  => 20000.00,
        5  => 20000.00,
        6  => 20000.00,
        7  => 20000.00,
        8  => 20000.00,
        9  => 20000.00,
        10 => 20000.00,
    ];

    private const MEGA_SCHEDULE = [
        1  => 150000.00,
        2  => 100000.00,
        3  => 50000.00,
        4  => 50000.00,
        5  => 50000.00,
        6  => 50000.00,
        7  => 50000.00,
        8  => 50000.00,
        9  => 50000.00,
        10 => 50000.00,
    ];

    public function __construct(private readonly WalletService $wallet)
    {
    }

    /**
     * Who would be paid what for this stockist, read from the referral chain
     * as it is now. The dry run of stockist:enrollment-bonus shows this, and
     * process() pays exactly this.
     *
     * @return array{store: string, pv: float, rows: list<array{gen: int, user: User, amount: float, pay: bool}>, total: float}
     */
    public function plan(Stockist $stockist): array
    {
        $user      = $stockist->user;
        $isMega    = $stockist->isMega();
        $schedule  = $isMega ? self::MEGA_SCHEDULE : self::BASIC_SCHEDULE;
        $rows      = [];
        $total     = 0.0;

        $seen    = $user ? [$user->id => true] : [];
        $current = $user ? User::find($stockist->sponsor_user_id ?: $user->ref_by) : null;

        for ($gen = 1; $gen <= 10 && $current && !isset($seen[$current->id]); $gen++) {
            $seen[$current->id] = true;
            $pay    = (int) $current->status === Status::USER_ACTIVE;   // banned: not paid, the generation still counts
            $rows[] = ['gen' => $gen, 'user' => $current, 'amount' => $schedule[$gen], 'pay' => $pay];
            $total += $pay ? $schedule[$gen] : 0;
            $current = $current->ref_by ? User::find($current->ref_by) : null;
        }

        return [
            'store' => $isMega ? 'Mega Store (₦10M)' : 'Basic Store (₦3M)',
            'pv'    => $isMega ? 10000.00 : 3000.00,
            'rows'  => $rows,
            'total' => $total,
        ];
    }

    /**
     * Pay a stockist's enrollment bonuses.
     *
     * @return array{paid: int, total: float, skipped: int}|null  null when this
     *   stockist's bonuses were already paid (nothing is paid twice)
     */
    public function process(Stockist $stockist, ?string $trx = null): ?array
    {
        $user = $stockist->user;
        if (!$user) {
            return null;
        }
        $trx ??= getTrx();

        return DB::transaction(function () use ($stockist, $user, $trx) {
            $claimed = Stockist::whereKey($stockist->id)->whereNull('enrollment_bonus_paid_at')
                ->update(['enrollment_bonus_paid_at' => now()]);
            if (!$claimed) {
                return null;
            }

            $plan    = $this->plan($stockist);
            $summary = ['paid' => 0, 'total' => 0.0, 'skipped' => 0];

            foreach ($plan['rows'] as $row) {
                if (!$row['pay']) {
                    $summary['skipped']++;
                    continue;
                }

                $this->wallet->credit(
                    $row['user'],
                    $row['amount'],
                    self::REMARK,
                    sprintf('Stockist %s enrollment bonus, generation %d: %s (%s)', $plan['store'], $row['gen'], $stockist->business_name ?: $user->username, $user->username),
                    $trx,
                );
                $summary['paid']++;
                $summary['total'] += $row['amount'];

                // Debit from isolated Member Compensation Reserve Ledger (Commerce Pool)
                try {
                    app(RevenueAllocationService::class)->disburseCompensation(
                        $row['amount'],
                        $trx,
                        sprintf('Stockist %s enrollment bonus Gen-%d to %s', $plan['store'], $row['gen'], $row['user']->username),
                        CompensationReserveLedger::SUB_POOL_CP,
                        'stockist_setup_bonus',
                        $row['user']->id
                    );
                } catch (\Throwable $e) {
                    Log::warning('[RevenueAllocation] Failed to debit stockist enrollment bonus: ' . $e->getMessage());
                }
            }

            // Distribute store PV up the matrix tree for recognition awards
            try {
                updatePV($user, sprintf('Stockist %s PV from %s', $plan['store'], $user->username), $plan['pv']);
            } catch (\Throwable $e) {
                Log::warning('[StockistEnrollment] Failed to distribute store PV: ' . $e->getMessage());
            }

            Log::info(sprintf('[StockistEnrollment] %s: %d upline paid ₦%s (%d banned skipped) for stockist #%d (%s)', $plan['store'], $summary['paid'], number_format($summary['total'], 2), $summary['skipped'], $stockist->id, $user->username));

            return $summary;
        });
    }
}
