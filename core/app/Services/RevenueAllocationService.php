<?php

namespace App\Services;

use App\Models\CompanyPoolLedger;
use App\Models\CompanyReserveLedger;
use App\Models\CompensationReserveLedger;
use App\Models\ExecutivePoolLedger;
use App\Models\OperationalPoolLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Revenue Allocation Service
 *
 * Blueprint & Multi-Ledger Architecture:
 * Strictly segregates incoming cashflows into isolated, dedicated financial ledgers:
 * 1. CompanyReserveLedger (30.00% Company Return)
 * 2. ExecutivePoolLedger (10.00% Executive Leadership)
 * 3. OperationalPoolLedger (5.00% Operations)
 * 4. CompensationReserveLedger (55.00% Member Compensation Reserve)
 */
class RevenueAllocationService
{
    /**
     * Allocate incoming revenue across the 4 dedicated ledgers atomically.
     *
     * @param  float   $grossAmount  incoming gross revenue in Naira
     * @param  string  $sourceType   e.g. 'deposit', 'registration', 'upgrade', 'order'
     * @param  string  $trx          transaction reference
     * @param  string  $description  human-readable narration
     * @return CompanyPoolLedger|null
     */
    public function allocate(
        float $grossAmount,
        string $sourceType,
        string $trx,
        string $description
    ): ?CompanyPoolLedger {
        if ($grossAmount <= 0) {
            return null;
        }

        $gross = round($grossAmount, 2);

        // Split proportions
        $companyReserve = round($gross * (CompanyPoolLedger::PERCENT_COMPANY_RESERVE / 100), 2);
        $executivePool  = round($gross * (CompanyPoolLedger::PERCENT_EXECUTIVE_POOL / 100), 2);
        $operational    = round($gross * (CompanyPoolLedger::PERCENT_OPERATIONAL / 100), 2);

        // Compensation reserve takes the balanced remainder to avoid rounding leakage
        $compensationReserve = round($gross - ($companyReserve + $executivePool + $operational), 2);

        return DB::transaction(function () use (
            $gross,
            $companyReserve,
            $executivePool,
            $operational,
            $compensationReserve,
            $sourceType,
            $trx,
            $description
        ) {
            // ── 1. Lock latest master summary row ───────────────────
            $latest = CompanyPoolLedger::lockForUpdate()->latest('id')->first();

            $prevCompany     = $latest ? (float) $latest->company_reserve_balance : 0.0;
            $prevExec        = $latest ? (float) $latest->executive_pool_balance : 0.0;
            $prevOperational = $latest ? (float) $latest->operational_pool_balance : 0.0;
            $prevComp        = $latest ? (float) $latest->compensation_reserve_balance : 0.0;

            $newCompany     = round($prevCompany + $companyReserve, 2);
            $newExec        = round($prevExec + $executivePool, 2);
            $newOperational = round($prevOperational + $operational, 2);
            $newComp        = round($prevComp + $compensationReserve, 2);

            // Master rollup ledger
            $masterLedger = CompanyPoolLedger::create([
                'trx'                          => $trx,
                'source_type'                  => $sourceType,
                'description'                  => $description,
                'gross_amount'                 => $gross,
                'company_reserve_amount'       => $companyReserve,
                'executive_pool_amount'        => $executivePool,
                'operational_pool_amount'      => $operational,
                'compensation_reserve_amount'  => $compensationReserve,
                'company_reserve_balance'      => $newCompany,
                'executive_pool_balance'       => $newExec,
                'operational_pool_balance'     => $newOperational,
                'compensation_reserve_balance' => $newComp,
            ]);

            // ── 2. Dedicated Isolated Ledgers ──────────────────────
            // Ledger A: Company Reserve (30%)
            CompanyReserveLedger::create([
                'trx'         => $trx,
                'source_type' => $sourceType,
                'credit'      => $companyReserve,
                'debit'       => 0.00,
                'balance'     => $newCompany,
                'description' => $description . ' [30% Company Reserve]',
            ]);

            // Ledger B: Executive Leadership (10%)
            ExecutivePoolLedger::create([
                'trx'         => $trx,
                'source_type' => $sourceType,
                'credit'      => $executivePool,
                'debit'       => 0.00,
                'balance'     => $newExec,
                'description' => $description . ' [10% Executive Pool]',
            ]);

            // Ledger C: Operational Expenses (5%)
            OperationalPoolLedger::create([
                'trx'         => $trx,
                'source_type' => $sourceType,
                'credit'      => $operational,
                'debit'       => 0.00,
                'balance'     => $newOperational,
                'description' => $description . ' [5% Operational Budget]',
            ]);

            // Ledger D: Member Compensation Reserve (55%)
            CompensationReserveLedger::create([
                'trx'         => $trx,
                'source_type' => $sourceType,
                'sub_pool'    => 'ALLOCATION',
                'bonus_type'  => null,
                'user_id'     => null,
                'credit'      => $compensationReserve,
                'debit'       => 0.00,
                'balance'     => $newComp,
                'description' => $description . ' [55% Member Compensation Reserve]',
            ]);

            Log::info(sprintf(
                '[RevenueAllocation:MultiLedger] ₦%s allocated into 4 isolated ledgers (TRX: %s)',
                number_format($gross, 2),
                $trx
            ));

            return $masterLedger;
        });
    }

    /**
     * Debit member compensation disbursements from the Compensation Reserve isolated ledger.
     *
     * @param  float        $payoutAmount
     * @param  string       $trx
     * @param  string       $description
     * @param  string|null  $subPool     CRP | CP | SR
     * @param  string|null  $bonusType   e.g. 'direct_bonus', 'binary_matching', etc.
     * @param  int|null     $userId
     * @return CompanyPoolLedger|null
     */
    public function disburseCompensation(
        float $payoutAmount,
        string $trx,
        string $description,
        ?string $subPool = 'CRP',
        ?string $bonusType = null,
        ?int $userId = null
    ): ?CompanyPoolLedger {
        if ($payoutAmount <= 0) {
            return null;
        }

        $payout = round($payoutAmount, 2);

        return DB::transaction(function () use ($payout, $trx, $description, $subPool, $bonusType, $userId) {
            $latest = CompanyPoolLedger::lockForUpdate()->latest('id')->first();

            $prevCompany     = $latest ? (float) $latest->company_reserve_balance : 0.0;
            $prevExec        = $latest ? (float) $latest->executive_pool_balance : 0.0;
            $prevOperational = $latest ? (float) $latest->operational_pool_balance : 0.0;
            $prevComp        = $latest ? (float) $latest->compensation_reserve_balance : 0.0;

            $newComp = round($prevComp - $payout, 2);

            // Master rollup update
            $masterLedger = CompanyPoolLedger::create([
                'trx'                          => $trx,
                'source_type'                  => 'compensation_payout',
                'description'                  => $description,
                'gross_amount'                 => -$payout,
                'company_reserve_amount'       => 0.00,
                'executive_pool_amount'        => 0.00,
                'operational_pool_amount'      => 0.00,
                'compensation_reserve_amount'  => -$payout,
                'company_reserve_balance'      => $prevCompany,
                'executive_pool_balance'       => $prevExec,
                'operational_pool_balance'     => $prevOperational,
                'compensation_reserve_balance' => $newComp,
            ]);

            // Isolated Compensation Reserve Ledger entry
            CompensationReserveLedger::create([
                'trx'         => $trx,
                'source_type' => 'compensation_payout',
                'sub_pool'    => $subPool ?? 'CRP',
                'bonus_type'  => $bonusType,
                'user_id'     => $userId,
                'credit'      => 0.00,
                'debit'       => $payout,
                'balance'     => $newComp,
                'description' => $description,
            ]);

            Log::info(sprintf(
                '[RevenueAllocation:CompensationDebit] ₦%s debited from isolated Compensation Reserve (TRX: %s)',
                number_format($payout, 2),
                $trx
            ));

            return $masterLedger;
        });
    }

    /**
     * Check if compensation reserve has sufficient margin.
     */
    public function canDisburseCompensation(float $amount): bool
    {
        return CompensationReserveLedger::hasSufficientReserve($amount);
    }

    /**
     * Get running balances across all 4 pools.
     */
    public function getBalances(): array
    {
        return [
            'company_reserve'      => CompanyReserveLedger::currentBalance(),
            'executive_pool'       => ExecutivePoolLedger::currentBalance(),
            'operational'          => OperationalPoolLedger::currentBalance(),
            'compensation_reserve' => CompensationReserveLedger::currentBalance(),
            'total_accumulated'    => (float) (
                CompanyReserveLedger::currentBalance() +
                ExecutivePoolLedger::currentBalance() +
                OperationalPoolLedger::currentBalance() +
                CompensationReserveLedger::currentBalance()
            ),
        ];
    }
}
