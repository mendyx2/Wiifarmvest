<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DividendBatch extends Model
{
    use HasFactory;

    protected $table = 'dividend_batches';

    protected $fillable = [
        'created_by_id',
        'amount_per_unit',
        'scope',
        'start_date',
        'end_date',
        'plan_id',
        'status',
        'total_users',
        'total_units',
        'total_amount',
        'processed_count',
        'failed_count',
        'cancelled_at',
        'cancelled_by_id',
        'reversed_at',
        'reversed_by_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'cancelled_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function stransactions()
    {
        return $this->hasMany(Shtransaction::class, 'dividend_batch_id');
    }
    public function shtransactions()
    {
        return $this->hasMany(Shtransaction::class, 'dividend_batch_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Helper Methods
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canReverse(): bool
    {
        return $this->status === 'completed';
    } 

    public function cancel(): bool
    {
        if (!$this->canCancel()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by_id' => Auth::guard('admin')->user()->id ?? null,
        ]);

        return true;
    }

    public function getTotalAffectedUsersAttribute(): int
    {
        return $this->total_users;
    }

    public static function generateReference(): string
    {
        return 'DVDND' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Accessors
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->total_amount, 2);
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge badge--warning">Pending</span>',
            'processing' => '<span class="badge badge--info">Processing</span>',
            'completed' => '<span class="badge badge--success">Completed</span>',
            'cancelled' => '<span class="badge badge--secondary">Cancelled</span>',
            'failed' => '<span class="badge badge--danger">Failed</span>',
            'reversed' => '<span class="badge badge--dark">Reversed</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge badge--secondary">Unknown</span>';
    }

    public function getScopeDisplayAttribute(): string
    {
        if ($this->scope === 'date-range') {
            return "{$this->start_date->format('M d, Y')} - {$this->end_date->format('M d, Y')}";
        }

        return 'All Time';
    }
}
