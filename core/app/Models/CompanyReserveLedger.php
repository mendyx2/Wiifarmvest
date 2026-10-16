<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Company Reserve Ledger (30.00% Pool)
 *
 * Dedicated isolated ledger for the Company's 30% statutory return.
 */
class CompanyReserveLedger extends Model
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
