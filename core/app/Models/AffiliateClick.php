<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateClick extends Model
{
    protected $fillable = [
        'affiliate_user_id', 'session_token', 'ip_address', 'user_agent', 'referer', 'landing_url',
    ];

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    public function orders()
    {
        return $this->hasMany(AffiliateOrder::class, 'affiliate_click_id');
    }

    /** The shared ad this visit came through, if any. */
    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}
