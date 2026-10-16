<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepurchaseAward extends Model
{
    protected $fillable = ['title', 'required_pv', 'amount', 'status'];

    protected $casts = [
        'required_pv' => 'decimal:2',
        'amount'      => 'decimal:2',
        'status'      => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('required_pv');
    }

    public function qualifiedUsers()
    {
        return RepurchasePv::where('total_pv', '>=', $this->required_pv)
            ->with('user')
            ->orderByDesc('total_pv')
            ->get();
    }
}
