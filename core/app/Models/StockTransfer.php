<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A Mini stockist buying stock from a Mega stockist in the same state.
 *
 * The buyer's payment leaves their stockist wallet when the request is made
 * and is held here until the seller confirms (stock moves, seller is paid) or
 * the request is declined / cancelled (buyer is refunded). Only
 * App\Services\StockTransferService changes a transfer's status or money.
 */
class StockTransfer extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED  = 'declined';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING   => 'Awaiting Mega stockist',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_DECLINED  => 'Declined',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount'         => 'decimal:2',
        'rebate_amount'        => 'decimal:2',
        'buyer_balance_after'  => 'decimal:2',
        'seller_balance_after' => 'decimal:2',
        'completed_at'         => 'datetime',
        'declined_at'          => 'datetime',
        'cancelled_at'         => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(Stockist::class, 'buyer_stockist_id');
    }

    public function seller()
    {
        return $this->belongsTo(Stockist::class, 'seller_stockist_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeInvolving($query, Stockist $stockist)
    {
        return $query->where(fn ($q) => $q->where('buyer_stockist_id', $stockist->id)->orWhere('seller_stockist_id', $stockist->id));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /** Status pill class on the stockist pages (sl-status-*). */
    public function statusClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'sl-status-pending',
            self::STATUS_COMPLETED => 'sl-status-delivered',
            self::STATUS_DECLINED  => 'sl-status-cancelled',
            default                => 'sl-status-closed',
        };
    }

    /** Status badge class in the admin panel. */
    public function adminBadge(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'badge--warning',
            self::STATUS_COMPLETED => 'badge--success',
            self::STATUS_DECLINED  => 'badge--danger',
            default                => 'badge--dark',
        };
    }

    /** When the transfer stopped being pending, whichever way it ended. */
    public function closedAt()
    {
        return $this->completed_at ?? $this->declined_at ?? $this->cancelled_at;
    }

    /** "Rice 50kg × 10, Palm oil × 2" — for notifications. */
    public function itemsSummary(): string
    {
        return $this->items->map(fn ($item) => $item->product_name . ' × ' . $item->quantity)->implode(', ');
    }

    public static function newCode(): string
    {
        do {
            $code = 'STX' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
