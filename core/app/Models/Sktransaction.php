<?php
// App/Models/Sktransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sktransaction extends Model
{
    use HasFactory;

    protected $table = 'sktransactions';

    protected $fillable = [
        'stockist_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'notes',
        'reference',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array'
    ];

    // Relationships
    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        $sign = $this->type === 'credit' ? '+' : '-';
        return $sign . '₦' . number_format($this->amount, 2);
    }

    public function getFormattedBalanceBeforeAttribute()
    {
        return '₦' . number_format($this->balance_before, 2);
    }

    public function getFormattedBalanceAfterAttribute()
    {
        return '₦' . number_format($this->balance_after, 2);
    }

    public function getTransactionTypeBadgeAttribute()
    {
        if ($this->type === 'credit') {
            return '<span class="badge badge-success">Credit</span>';
        }
        return '<span class="badge badge-danger">Debit</span>';
    }

    // Generate unique reference
    public static function generateReference()
    {
        return 'SKTXN' . date('YmdHis') . rand(1000, 9999);
    }
}