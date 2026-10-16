<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorChangeLog extends Model
{
    protected $fillable = ['user_id', 'previous_sponsor_id', 'new_sponsor_id', 'admin_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function previousSponsor()
    {
        return $this->belongsTo(User::class, 'previous_sponsor_id');
    }

    public function newSponsor()
    {
        return $this->belongsTo(User::class, 'new_sponsor_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
