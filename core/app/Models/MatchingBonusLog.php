<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchingBonusLog extends Model
{
    protected $fillable = [
        'user_id',
        'matrix_id',
        'project_id',
        'matches',
        'pv_per_match',
        'bonus_per_match',
        'total_bonus',
        'autoship_fulfilled',
        'autoship_amount',
        'pv_left_before',
        'pv_right_before',
        'pv_left_after',
        'pv_right_after',
        'trx',
        'processed_at',
        'leadership_processed_at',
    ];

    protected $casts = [
        'pv_per_match'            => 'decimal:2',
        'bonus_per_match'         => 'decimal:2',
        'total_bonus'             => 'decimal:2',
        'autoship_fulfilled'      => 'boolean',
        'autoship_amount'         => 'decimal:2',
        'pv_left_before'          => 'decimal:2',
        'pv_right_before'         => 'decimal:2',
        'pv_left_after'           => 'decimal:2',
        'pv_right_after'          => 'decimal:2',
        'processed_at'            => 'datetime',
        'leadership_processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Matrix::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Total matches earned by a user on a specific calendar date (YYYY-MM-DD). */
    public static function dailyMatchCount(int $userId, string $date): int
    {
        return static::where('user_id', $userId)
            ->whereDate('processed_at', $date)
            ->sum('matches');
    }
}
