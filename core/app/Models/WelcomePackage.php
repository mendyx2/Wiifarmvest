<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WelcomePackage
 *
 * A redeemable voucher created in place of an instant cash-back credit
 * when a user registers or upgrades their project. The amount stays
 * locked until a stockist redeems the code in person, at which point
 * the stockist's wallet is reimbursed the package amount.
 *
 * @see \App\Services\WelcomePackageService
 */
class WelcomePackage extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_REDEEMED = 'redeemed';

    public const SOURCE_REGISTRATION = 'registration';
    public const SOURCE_UPGRADE      = 'upgrade';

    protected $fillable = [
        'user_id',
        'amount',
        'code',
        'source',
        'status',
        'trx',
        'redeemed_by_stockist_id',
        'redeemed_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(Stockist::class, 'redeemed_by_stockist_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRedeemed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REDEEMED);
    }

    public function isRedeemed(): bool
    {
        return $this->status === self::STATUS_REDEEMED;
    }
}
