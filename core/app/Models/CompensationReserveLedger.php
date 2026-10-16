<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Member Compensation Reserve Ledger (55.00% Pool)
 *
 * Dedicated isolated ledger for the Member Compensation Budget:
 * - Consumer Rewards Pool (CRP): 22.00%
 * - Commerce Pool (CP): 24.75%
 * - Strategic Reserve (SR): 8.25%
 *
 * Tracks every allocation (credit) and member bonus disbursement (debit).
 */
class CompensationReserveLedger extends Model
{
    protected $fillable = [
        'trx',
        'source_type',
        'sub_pool',
        'bonus_type',
        'user_id',
        'credit',
        'debit',
        'balance',
        'description',
    ];

    protected $casts = [
        'credit'  => 'decimal:2',
        'debit'   => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public const SUB_POOL_CRP = 'CRP'; // Consumer Rewards Pool (22.00%)
    public const SUB_POOL_CP  = 'CP';  // Commerce Pool (24.75%)
    public const SUB_POOL_SR  = 'SR';  // Strategic Reserve (8.25%)

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function currentBalance(): float
    {
        return (float) (static::latest('id')->value('balance') ?? 0.00);
    }

    public static function hasSufficientReserve(float $amount): bool
    {
        return static::currentBalance() >= $amount;
    }
}
