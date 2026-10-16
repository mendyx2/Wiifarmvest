<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    // Only creation-time fields are mass-assignable.
    // status and paid_at are lifecycle fields — always set via direct assignment.
    protected $fillable = [
        'loan_id',
        'user_id',
        'installment_number',
        'due_date',
        'principal',
        'interest',
        'total',
        'balance_due',
        'amount_paid',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && !in_array($this->status, ['paid']);
    }
}
