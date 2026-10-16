<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadershipLeader extends Model
{
    protected $fillable = [
        'user_id',
        'pv_left',
        'pv_right',
        'required_pv',
        'status',
        'qualified_at',
        'notified_at',
    ];

    protected $casts = [
        'pv_left'      => 'decimal:2',
        'pv_right'     => 'decimal:2',
        'required_pv'  => 'decimal:2',
        'status'       => 'boolean',
        'qualified_at' => 'datetime',
        'notified_at'  => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
