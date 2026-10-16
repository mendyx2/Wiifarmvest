<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Shtransaction extends Model
{
    use HasFactory;

    protected $table = 'shtransactions';

    protected $fillable = [
        'user_id',
        'rinvestment_id',
        'dividend_batch_id',
        'amount',
        'amount_per_unit',
        'units_held',
        'description',
        'type',
        'reference',
        'status',
        'notes',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_per_unit' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ============ RELATIONSHIPS ============

    /**
     * Get the user associated with this transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the investment associated with this transaction
     */
    public function rinvestment()
    {
        return $this->belongsTo(Rinvestment::class);
    }

    /**
     * Get the dividend batch this transaction belongs to
     */
    public function dividendBatch()
    {
        return $this->belongsTo(DividendBatch::class, 'dividend_batch_id');
    }

    // ============ SCOPES ============

    /**
     * Scope to filter by status
     */
    public function scopeByStatus(Builder $query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter completed transactions
     */
    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter pending transactions
     */
    public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter failed transactions
     */
    public function scopeFailed(Builder $query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by dividend batch
     */
    public function scopeForBatch(Builder $query, $batchId)
    {
        return $query->where('dividend_batch_id', $batchId);
    }

    /**
     * Scope to filter by type (credit/debit)
     */
    public function scopeByType(Builder $query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter credit transactions
     */
    public function scopeCredits(Builder $query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope to filter debit transactions
     */
    public function scopeDebits(Builder $query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope to order by newest first
     */
    public function scopeNewest(Builder $query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by oldest first
     */
    public function scopeOldest(Builder $query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope for date range filtering
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ============ HELPER METHODS ============

    /**
     * Generate unique transaction reference
     */
    public static function generateReference(): string
    {
        return 'DVDND' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Mark transaction as completed
     */
    public function markCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markFailed($reason = null): bool
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        return $this->update([
            'status' => 'failed',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark transaction as pending
     */
    public function markPending(): bool
    {
        return $this->update(['status' => 'pending']);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is a credit
     */
    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    /**
     * Check if transaction is a debit
     */
    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    /**
     * Get total amount for a user
     */
    public static function getTotalForUser($userId, $status = 'completed'): float
    {
        return static::forUser($userId)
            ->byStatus($status)
            ->credits()
            ->sum('amount') ?? 0;
    }

    /**
     * Get total amount for a batch
     */
    public static function getTotalForBatch($batchId, $status = 'completed'): float
    {
        return static::forBatch($batchId)
            ->byStatus($status)
            ->sum('amount') ?? 0;
    }

    /**
     * Get transaction count for user
     */
    public static function getCountForUser($userId, $status = null): int
    {
        $query = static::forUser($userId);

        if ($status) {
            $query->byStatus($status);
        }

        return $query->count();
    }

    /**
     * Get average amount per transaction
     */
    public function getAverageAmount($status = 'completed'): float
    {
        $total = static::byStatus($status)->credits()->sum('amount') ?? 0;
        $count = static::byStatus($status)->credits()->count();

        return $count > 0 ? $total / $count : 0;
    }

    // ============ ACCESSORS & MUTATORS ============

    /**
     * Get formatted amount attribute
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->type === 'credit' ? '+' : '-';
        return $sign . '₦' . number_format($this->amount, 2);
    }

    /**
     * Get formatted amount per unit
     */
    public function getFormattedAmountPerUnitAttribute(): string
    {
        return '₦' . number_format($this->amount_per_unit, 2);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge badge--warning">Pending</span>',
            'completed' => '<span class="badge badge--success">Completed</span>',
            'failed' => '<span class="badge badge--danger">Failed</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    /**
     * Get type badge HTML
     */
    public function getTypeBadgeAttribute(): string
    {
        $badges = [
            'credit' => '<span class="badge badge-success">Credit</span>',
            'debit' => '<span class="badge badge-danger">Debit</span>',
        ];

        return $badges[$this->type] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    /**
     * Get transaction summary
     */
    public function getSummaryAttribute(): string
    {
        return "{$this->reference} - {$this->description}";
    }

    /**
     * Get user full name
     */
    public function getUserNameAttribute(): string
    {
        return $this->user?->fullname ?? 'Unknown';
    }

    /**
     * Get plan name if available
     */
    public function getPlanNameAttribute(): string
    {
        return $this->rinvestment?->plan->name ?? 'N/A';
    }

    // ============ CALCULATION METHODS ============

    /**
     * Calculate total dividend amount from units and rate
     */
    public static function calculateDividend($units, $amountPerUnit): float
    {
        return $units * $amountPerUnit;
    }

    /**
     * Get transaction profit/loss (for debit transactions)
     */
    public function getProfit(): float
    {
        if ($this->type === 'credit') {
            return $this->amount;
        }

        return 0;
    }

    /**
     * Get refund amount (for failed transactions)
     */
    public function getRefundAmount(): float
    {
        return $this->status === 'failed' ? $this->amount : 0;
    }

    // ============ SEARCH & FILTER METHODS ============

    /**
     * Search by reference or description
     */
    public function scopeSearch(Builder $query, string $term)
    {
        return $query->where('reference', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * Get transactions for reporting
     */
    public function scopeForReport(Builder $query, $startDate = null, $endDate = null, $userId = null, $status = null)
    {
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($userId) {
            $query->forUser($userId);
        }

        if ($status) {
            $query->byStatus($status);
        }

        return $query->with('user', 'rinvestment.plan')->newest();
    }

    // ============ EXPORT/IMPORT METHODS ============

    /**
     * Get array for export
     */
    public function toExportArray(): array
    {
        return [
            'Reference' => $this->reference,
            'User' => $this->user_name,
            'Plan' => $this->plan_name,
            'Units' => $this->units_held,
            'Rate/Unit' => $this->formatted_amount_per_unit,
            'Amount' => $this->formatted_amount,
            'Status' => $this->status,
            'Date' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get array for API response
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'amount_per_unit' => $this->amount_per_unit,
            'units_held' => $this->units_held,
            'type' => $this->type,
            'status' => $this->status,
            'description' => $this->description,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
