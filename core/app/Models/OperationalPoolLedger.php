<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Operational Expenses Pool Ledger (5.00% Pool)
 *
 * Dedicated isolated ledger for platform maintenance, marketing, expansion, and contingency.
 */
class OperationalPoolLedger extends Model
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
