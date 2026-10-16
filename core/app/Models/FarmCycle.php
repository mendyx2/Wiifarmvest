<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmCycle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subscription_opens_at'  => 'date',
        'subscription_closes_at' => 'date',
        'maturity_date'          => 'date',
        'matured_at'             => 'datetime',
        'payout_processed'       => 'boolean',
    ];

    public function savings()
    {
        return $this->hasMany(SavingsProduct::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeMatured($query)
    {
        return $query->where('status', 'matured');
    }

    public function isMaturityDateReached(): bool
    {
        return $this->maturity_date && $this->maturity_date->lte(now()->startOfDay());
    }

    public function activeSavingsCount(): int
    {
        return $this->savings()->where('status', 'active')->count();
    }

    public function activeSavingsTotal(): float
    {
        return (float) $this->savings()->where('status', 'active')->sum('principal');
    }
}
