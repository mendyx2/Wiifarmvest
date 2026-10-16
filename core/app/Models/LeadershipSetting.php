<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadershipSetting extends Model
{
    protected $fillable = [
        'required_pv',
        'percentage',
        'max_levels',
        'is_active',
        'enabled_at',
    ];

    protected $casts = [
        'required_pv' => 'decimal:2',
        'percentage'  => 'decimal:2',
        'max_levels'  => 'integer',
        'is_active'   => 'boolean',
        'enabled_at'  => 'datetime',
    ];

    /**
     * Get the current active leadership setting or safe Blueprint default.
     */
    public static function current(): self
    {
        $setting = static::where('is_active', true)->latest()->first();

        if (!$setting) {
            $setting = static::first();
        }

        if (!$setting) {
            // Blueprint default: 4.00% leadership pool, 1000 PV threshold, 5 upline levels
            $setting = new static([
                'required_pv' => 1000.00,
                'percentage'  => 4.00,
                'max_levels'  => 5,
                'is_active'   => true,
                'enabled_at'  => now(),
            ]);
        }

        return $setting;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function percentageLabel(): string
    {
        return rtrim(rtrim((string) $this->percentage, '0'), '.') . '%';
    }

    public function levelsLabel(): string
    {
        return $this->max_levels > 0 ? "{$this->max_levels} levels" : 'unlimited levels';
    }
}
