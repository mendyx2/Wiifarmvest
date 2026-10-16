<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateSetting extends Model
{
    protected $fillable = [
        'cash_on_pickup_enabled', 'paystack_enabled', 'cookie_days',
        'stockist_pickup_fee_type', 'stockist_pickup_fee_value', 'pending_order_expiry_days',
    ];

    protected $casts = [
        'cash_on_pickup_enabled' => 'boolean',
        'paystack_enabled'       => 'boolean',
        'cookie_days'             => 'integer',
        'stockist_pickup_fee_value' => 'float',
        'pending_order_expiry_days' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function stockistFeeFor(float $amount): float
    {
        if ($this->stockist_pickup_fee_value <= 0) {
            return 0.0;
        }

        return $this->stockist_pickup_fee_type === 'percentage'
            ? round($amount * ($this->stockist_pickup_fee_value / 100), 2)
            : (float) $this->stockist_pickup_fee_value;
    }
}
