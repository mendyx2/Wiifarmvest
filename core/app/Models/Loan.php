<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'user_id', 'loan_product_id', 'reference', 'status', 'purpose',
        'original_amount', 'interest_rate', 'total_interest', 'total_repayable',
        'outstanding', 'total_repaid', 'tenure_months', 'tenure_unit',
        'monthly_installment', 'installments_paid', 'next_due_date',
        'savings_balance_at_application',
        'approved_at', 'disbursed_at', 'cleared_at', 'defaulted_at',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'approved_at'   => 'datetime',
        'disbursed_at'  => 'datetime',
        'cleared_at'    => 'datetime',
        'defaulted_at'  => 'datetime',
        'metadata'      => 'array',
        'repayment_priority' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loanProduct()
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function schedule()
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_number');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class)->latest();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'at_risk']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDefaulted($query)
    {
        return $query->where('status', 'defaulted');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'at_risk']);
    }

    public function isCleared(): bool
    {
        return $this->status === 'cleared';
    }

    public function getRepaymentProgressAttribute(): float
    {
        if ($this->total_repayable <= 0) return 0;
        return round(($this->total_repaid / $this->total_repayable) * 100, 1);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'warning',
            'approved'  => 'info',
            'active'    => 'success',
            'at_risk'   => 'warning',
            'defaulted' => 'danger',
            'cleared'   => 'primary',
            'rejected'  => 'danger',
            default     => 'secondary',
        };
    }
}
