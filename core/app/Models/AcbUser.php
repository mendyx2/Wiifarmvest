<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcbUser extends Model
{
    protected $table = 'acb_users';

    protected $fillable = ['user_id', 'added_by', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
