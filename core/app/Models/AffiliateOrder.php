<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AffiliateOrder extends Model
{
    const STATUS_PENDING         = 'pending';
    const STATUS_PAID            = 'paid';
    const STATUS_AWAITING_PICKUP = 'awaiting_pickup';
    const STATUS_FULFILLED       = 'fulfilled';
    const STATUS_CANCELLED       = 'cancelled';
    const STATUS_EXPIRED         = 'expired';

    const PAYMENT_PAYSTACK       = 'paystack';
    const PAYMENT_CASH_ON_PICKUP = 'cash_on_pickup';

    protected $fillable = [
        'order_code', 'affiliate_user_id', 'affiliate_click_id',
        'buyer_name', 'buyer_email', 'buyer_phone', 'state_id',
        'payment_method', 'status', 'subtotal', 'total_amount',
        'paystack_reference', 'bonus_credited',
        'redeemed_by_stockist_id', 'redeemed_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'bonus_credited' => 'boolean',
        'redeemed_at'    => 'datetime',
        'subtotal'       => 'float',
        'total_amount'   => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (AffiliateOrder $order) {
            if (empty($order->order_code)) {
                do {
                    $code = 'AF-' . strtoupper(Str::random(8));
                } while (static::where('order_code', $code)->exists());

                $order->order_code = $code;
            }
        });
    }
                   
    public function items() 
    { 
        return $this->hasMany(AffiliateOrderItem::class);
    }

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    public function click()
    {
        return $this->belongsTo(AffiliateClick::class, 'affiliate_click_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function stockist()
    {
        return $this->belongsTo(Stockist::class, 'redeemed_by_stockist_id');
    }

    public function isReadyForPickup(): bool
    {
        return in_array($this->status, [self::STATUS_AWAITING_PICKUP, self::STATUS_PAID], true);
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            self::STATUS_PENDING         => 'badge--warning',
            self::STATUS_PAID            => 'badge--info',
            self::STATUS_AWAITING_PICKUP => 'badge--primary',
            self::STATUS_FULFILLED       => 'badge--success',
            self::STATUS_CANCELLED       => 'badge--danger',
            self::STATUS_EXPIRED         => 'badge--dark',
        ];

        $class = $map[$this->status] ?? 'badge--secondary';
        $label = ucwords(str_replace('_', ' ', $this->status));

        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }
}
