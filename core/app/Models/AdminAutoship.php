<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAutoship extends Model
{
    protected $table = 'admin_autoship';

    protected $fillable = [
        'user_id',
        'amount',
        'month',
        'swept_at',
        'trx',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'swept_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
