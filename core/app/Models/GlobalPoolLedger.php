<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global Share Pool Ledger
 *
 * Blueprint: Strategic Reserve — Global Share: 1.25%
 *
 * Accumulates 1.25% of PV volume from all product sales and registrations.
 * Periodically distributed to qualified top-rank leaders.
 */
class GlobalPoolLedger extends Model
{
    protected $fillable = [
        'period',
        'accumulated_amount',
        'distributed_amount',
        'qualified_count',
        'status',
        'distributed_at',
    ];

    protected $casts = [
        'accumulated_amount' => 'decimal:2',
        'distributed_amount' => 'decimal:2',
        'qualified_count'    => 'integer',
        'distributed_at'     => 'datetime',
    ];

    /** Pool percentage from strategic reserve */
    public const POOL_PERCENT = 1.25;

    /** Status constants */
    public const STATUS_ACCUMULATING = 'accumulating';
    public const STATUS_DISTRIBUTED  = 'distributed';
    public const STATUS_CLOSED       = 'closed';

    /**
     * Get or create the current accumulating period.
     *
     * @param  string $period  e.g. "Q4-2026" or "2026-10"
     */
    public static function currentPeriod(string $period = null): self
    {
        $period = $period ?? self::defaultPeriod();

        return static::firstOrCreate(
            ['period' => $period, 'status' => self::STATUS_ACCUMULATING],
            [
                'accumulated_amount' => 0.00,
                'distributed_amount' => 0.00,
                'qualified_count'    => 0,
            ]
        );
    }

    /**
     * Default period identifier based on current quarter.
     */
    public static function defaultPeriod(): string
    {
        $quarter = ceil(now()->month / 3);
        return 'Q' . $quarter . '-' . now()->year;
    }

    /**
     * Accrue an amount into the current pool.
     *
     * @param  float  $pvVolume  the PV volume of the transaction (before ₦ conversion)
     * @return float  the amount accrued
     */
    public static function accrue(float $pvVolume): float
    {
        if ($pvVolume <= 0) {
            return 0.0;
        }

        $amount = round($pvVolume * (self::POOL_PERCENT / 100) * rDollar(), 2);

        if ($amount <= 0) {
            return 0.0;
        }

        $ledger = self::currentPeriod();
        $ledger->increment('accumulated_amount', $amount);

        return $amount;
    }

    /**
     * Mark this period as distributed.
     */
    public function markDistributed(int $qualifiedCount, float $distributedAmount): void
    {
        $this->update([
            'status'             => self::STATUS_DISTRIBUTED,
            'qualified_count'    => $qualifiedCount,
            'distributed_amount' => $distributedAmount,
            'distributed_at'     => now(),
        ]);
    }

    /**
     * Scope: only accumulating periods.
     */
    public function scopeAccumulating($query)
    {
        return $query->where('status', self::STATUS_ACCUMULATING);
    }

    /**
     * Scope: only distributed periods.
     */
    public function scopeDistributed($query)
    {
        return $query->where('status', self::STATUS_DISTRIBUTED);
    }
}
