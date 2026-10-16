<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepurchasePv extends Model
{
    protected $fillable = ['user_id', 'total_pv'];

    protected $casts = ['total_pv' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function qualifiedAwards()
    {
        return RepurchaseAward::where('status', true)
            ->where('required_pv', '<=', $this->total_pv)
            ->orderBy('required_pv')
            ->get();
    }
}
