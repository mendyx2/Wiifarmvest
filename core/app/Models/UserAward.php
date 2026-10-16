<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAward extends Model
{
    protected $fillable = [
        'user_id',
        'award_id',
        'status',
        'earned_at',
        'paid_at',
        'paid_by',
        'paid_amount',
        'note',
    ];

    protected $casts = [
        'earned_at'  => 'datetime',
        'paid_at'    => 'datetime',
        'paid_amount' => 'decimal:2',
        'status'     => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function award()
    {
        return $this->belongsTo(Award::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }
}
