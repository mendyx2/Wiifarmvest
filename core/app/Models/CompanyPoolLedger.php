<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Company Pool Ledger Model
 *
 * Blueprint: Financial Governance & Pool Separation
 *
 * Segregates platform revenue into 4 distinct financial pools:
 * - 30.00% Company Reserve Pool
 * - 10.00% Executive Pool
 * -  5.00% Operational Expenses Pool
 * - 55.00% Member Compensation Reserve Pool
 */
class CompanyPoolLedger extends Model
{
    protected $fillable = [
        'trx',
        'source_type',
        'description',
        'gross_amount',
        'company_reserve_amount',
        'executive_pool_amount',
        'operational_pool_amount',
        'compensation_reserve_amount',
        'company_reserve_balance',
        'executive_pool_balance',
        'operational_pool_balance',
        'compensation_reserve_balance',
    ];

    protected $casts = [
        'gross_amount'                 => 'decimal:2',
        'company_reserve_amount'       => 'decimal:2',
        'executive_pool_amount'        => 'decimal:2',
        'operational_pool_amount'      => 'decimal:2',
        'compensation_reserve_amount'  => 'decimal:2',
        'company_reserve_balance'      => 'decimal:2',
        'executive_pool_balance'       => 'decimal:2',
        'operational_pool_balance'     => 'decimal:2',
        'compensation_reserve_balance' => 'decimal:2',
    ];

    /** Pool Percentage Constants */
    public const PERCENT_COMPANY_RESERVE = 30.00;
    public const PERCENT_EXECUTIVE_POOL  = 10.00;
    public const PERCENT_OPERATIONAL     = 5.00;
    public const PERCENT_COMPENSATION    = 55.00;

    /**
     * Get the latest running balances across the 4 pools.
     *
     * @return array
     */
    public static function getBalances(): array
    {
        $latest = static::latest('id')->first();

        if (!$latest) {
            return [
                'company_reserve'      => 0.00,
                'executive_pool'       => 0.00,
                'operational'          => 0.00,
                'compensation_reserve' => 0.00,
                'total_accumulated'    => 0.00,
            ];
        }

        return [
            'company_reserve'      => (float) $latest->company_reserve_balance,
            'executive_pool'       => (float) $latest->executive_pool_balance,
            'operational'          => (float) $latest->operational_pool_balance,
            'compensation_reserve' => (float) $latest->compensation_reserve_balance,
            'total_accumulated'    => (float) (
                $latest->company_reserve_balance +
                $latest->executive_pool_balance +
                $latest->operational_pool_balance +
                $latest->compensation_reserve_balance
            ),
        ];
    }

    /**
     * Check if the compensation reserve holds sufficient margin for a disbursement.
     *
     * @param  float $requiredAmount
     * @return bool
     */
    public static function hasSufficientCompensationReserve(float $requiredAmount): bool
    {
        if ($requiredAmount <= 0) {
            return true;
        }

        $balances = static::getBalances();
        return $balances['compensation_reserve'] >= $requiredAmount;
    }
}
