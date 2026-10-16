<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsProduct extends Model
{
    // Lifecycle timestamps and status are intentionally excluded —
    // they must only be set via direct assignment in controlled code paths.
    protected $fillable = [
        'user_id', 'type', 'reference', 'name',
        'principal', 'balance', 'interest_rate', 'interest_earned',
        'target_amount', 'frequency', 'duration_cycles', 'contribution_per_cycle',
        'start_date', 'maturity_date', 'next_due_date',
        'farm_cycle_id',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'maturity_date' => 'date',
        'next_due_date' => 'date',
        'matured_at'    => 'datetime',
        'closed_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function farmCycle()
    {
        return $this->belongsTo(FarmCycle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeMatured($query)
    {
        return $query->where('status', 'matured');
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'target' => 'blue',
            'fixed'  => 'gold',
            'farm'   => 'green',
            default  => 'green',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'target' => 'bullseye',
            'fixed'  => 'box',
            'farm'   => 'seedling',
            default  => 'piggy-bank',
        };
    }
}
