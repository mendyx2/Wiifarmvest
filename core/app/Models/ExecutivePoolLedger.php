<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Executive Leadership Pool Ledger (10.00% Pool)
 *
 * Dedicated isolated ledger for Executive & Frontline Leadership distributions.
 */
class ExecutivePoolLedger extends Model
{
    protected $fillable = [
        'trx',
        'source_type',
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

    public static function currentBalance(): float
    {
        return (float) (static::latest('id')->value('balance') ?? 0.00);
    }
}
