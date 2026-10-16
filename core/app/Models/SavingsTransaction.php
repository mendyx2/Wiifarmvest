<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_type',
        'savings_product_id',
        'type',
        'amount',
        'source',
        'description',
        'reference',
        'balance_before',
        'balance_after',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount'   => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savingsProduct()
    {
        return $this->belongsTo(SavingsProduct::class);
    }
}
