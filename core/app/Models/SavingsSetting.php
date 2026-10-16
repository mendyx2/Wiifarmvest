<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsSetting extends Model
{
    protected $fillable = [
        'type', 'duration_months', 'label', 'description',
        'interest_rate', 'min_amount', 'max_amount', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function rateFor(string $type, ?int $duration = null): float
    {
        $row = static::where('type', $type)
                     ->where('duration_months', $duration)
                     ->where('is_active', true)
                     ->first();

        return $row ? (float) $row->interest_rate : 8.0;
    }

    public static function minAmountFor(string $type, ?int $duration = null): float
    {
        $row = static::where('type', $type)
                     ->where('duration_months', $duration)
                     ->where('is_active', true)
                     ->first();

        return $row ? (float) $row->min_amount : 100.0;
    }

    public static function fixedRateMap(): array
    {
        return static::where('type', 'fixed')
                     ->where('is_active', true)
                     ->orderBy('duration_months')
                     ->pluck('interest_rate', 'duration_months')
                     ->toArray();
    }
}
