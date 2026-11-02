<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What one USDT deposit was asked to pay: an exact amount in whole units
 * (1 USDT = 1,000,000), to which address, and until when. See
 * App\Services\Usdt\UsdtPayments.
 */
class UsdtInvoice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'units'       => 'integer',
        'deposit_id'  => 'integer',
        'transfer_id' => 'integer',
        'expires_at'  => 'datetime',
    ];

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    public function transfer()
    {
        return $this->belongsTo(UsdtTransfer::class);
    }
}
