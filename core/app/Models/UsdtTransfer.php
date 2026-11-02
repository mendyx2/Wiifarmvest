<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One USDT payment into a Wiifarm address, seen on the Tron blockchain.
 * The transaction ID is unique: a payment counts once, ever.
 *
 * status: credited (paid a deposit), review (waiting for an admin),
 * unmatched (no deposit fits it), rejected (an admin refused it).
 */
class UsdtTransfer extends Model
{
    public const CREDITED  = 'credited';
    public const REVIEW    = 'review';
    public const UNMATCHED = 'unmatched';
    public const REJECTED  = 'rejected';

    protected $guarded = ['id'];

    protected $casts = [
        'units'         => 'integer',
        'block_time_ms' => 'integer',
        'deposit_id'    => 'integer',
    ];

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }
}
