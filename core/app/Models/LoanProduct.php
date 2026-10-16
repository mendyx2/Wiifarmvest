<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    protected $fillable = [
        'name', 'description', 'interest_rate', 'min_loan_amount', 'max_loan_amount',
        'savings_multiple', 'min_savings_threshold', 'min_membership_days',
        'tenure_options', 'auto_approve', 'is_active',
    ];

    protected $casts = [
        'tenure_options'    => 'array',
        'auto_approve'      => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function maxLoanFor(float $savingsWallet): float
    {
        $byMultiple = $savingsWallet * $this->savings_multiple;
        return $this->max_loan_amount
            ? min($byMultiple, $this->max_loan_amount)
            : $byMultiple;
    }
}
