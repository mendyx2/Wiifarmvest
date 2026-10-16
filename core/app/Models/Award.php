<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = [
        'name',
        'description',
        'required_total_pv',
        'required_left_pv',
        'required_right_pv',
        'prerequisite_award_id',
        'payment_amount',
        'image',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'required_total_pv'     => 'decimal:2',
        'required_left_pv'      => 'decimal:2',
        'required_right_pv'     => 'decimal:2',
        'payment_amount'        => 'decimal:2',
        'status'                => 'integer',
        'sort_order'            => 'integer',
        'prerequisite_award_id' => 'integer',
    ];

    public function prerequisite()
    {
        return $this->belongsTo(Award::class, 'prerequisite_award_id');
    }

    public function userAwards()
    {
        return $this->hasMany(UserAward::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return asset('assets/images/awards/' . $this->image);
    }
}
