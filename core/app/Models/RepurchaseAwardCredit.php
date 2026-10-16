<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepurchaseAwardCredit extends Model
{
    protected $fillable = ['user_id', 'repurchase_award_id', 'amount', 'paid_by', 'paid_at'];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function award()
    {
        return $this->belongsTo(RepurchaseAward::class, 'repurchase_award_id');
    }
}
