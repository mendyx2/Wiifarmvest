<?php

namespace App\Services;

use App\Models\CompensationReserveLedger;
use App\Models\Stockist;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stockist Enrollment Bonus Service
 *
 * Proposed Business Compensation Plan (Section 12, Page 7 & 8):
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
 */
class StockistEnrollmentBonusService
{
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

    public function process(Stockist $stockist, string $trx): void
    {
        $user = $stockist->user;
        if (!$user) {
            return;
        }

        $isMega   = $stockist->isMega();
        $schedule = $isMega ? self::MEGA_SCHEDULE : self::BASIC_SCHEDULE;
        $storeType = $isMega ? 'Mega Store (₦10M)' : 'Basic Store (₦3M)';
        $pvAmount  = $isMega ? 10000.00 : 3000.00;

        DB::transaction(function () use ($stockist, $user, $schedule, $storeType, $pvAmount, $trx) {
            // First generation is the sponsor who onboarded the stockist
            $sponsorId = $stockist->sponsor_user_id ?: $user->ref_by;
            $current = $sponsorId ? User::find($sponsorId) : null;

            for ($gen = 1; $gen <= 10; $gen++) {
                if (!$current) {
                    break;
                }

                $bonusAmount = $schedule[$gen] ?? 0.00;

                if ($bonusAmount > 0) {
                    $upline = User::lockForUpdate()->find($current->id);
                    if ($upline) {
                        $upline->increment('balance', $bonusAmount);
                        $upline->refresh();

                        $txn               = new Transaction();
                        $txn->user_id      = $upline->id;
                        $txn->amount       = $bonusAmount;
                        $txn->charge       = 0;
                        $txn->trx_type     = '+';
                        $txn->remark       = 'stockist_bonus';
                        $txn->details      = sprintf(
                            'Stockist %s Setup Bonus Gen-%d — %s (%s)',
                            $storeType,
                            $gen,
                            $stockist->business_name ?: $user->username,
                            $user->username
                        );
                        $txn->trx          = $trx;
                        $txn->post_balance = $upline->balance;
                        $txn->save();

                        // Debit from isolated Member Compensation Reserve Ledger (Commerce Pool)
                        try {
                            app(RevenueAllocationService::class)->disburseCompensation(
                                $bonusAmount,
                                $trx,
                                sprintf('Stockist %s setup bonus Gen-%d to %s', $storeType, $gen, $upline->username),
                                CompensationReserveLedger::SUB_POOL_CP,
                                'stockist_setup_bonus',
                                $upline->id
                            );
                        } catch (\Throwable $e) {
                            Log::warning('[RevenueAllocation] Failed to debit stockist setup bonus: ' . $e->getMessage());
                        }
                    }
                }

                $nextId  = $current->ref_by;
                $current = $nextId ? User::find($nextId) : null;
            }

            // Distribute store PV up the matrix tree for recognition awards
            try {
                $pvDetails = sprintf('Stockist %s PV from %s', $storeType, $user->username);
                updatePV($user, $pvDetails, $pvAmount);
            } catch (\Throwable $e) {
                Log::warning('[StockistEnrollment] Failed to distribute store PV: ' . $e->getMessage());
            }

            Log::info(sprintf(
                '[StockistEnrollment] %s bonuses disbursed for stockist #%d (%s)',
                $storeType,
                $stockist->id,
                $user->username
            ));
        });
    }
}
