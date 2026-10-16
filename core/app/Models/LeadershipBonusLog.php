<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadershipBonusLog extends Model
{
    protected $fillable = [
        'leader_id',
        'source_user_id',
        'matching_bonus_log_id',
        'level',
        'leg',
        'matching_amount',
        'percentage',
        'amount',
        'trx',
    ];

    protected $casts = [
        'level'           => 'integer',
        'matching_amount' => 'decimal:2',
        'percentage'      => 'decimal:2',
        'amount'          => 'decimal:2',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function matchingBonusLog(): BelongsTo
    {
        return $this->belongsTo(MatchingBonusLog::class, 'matching_bonus_log_id');
    }
}
