<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDetailHistory extends Model
{
    protected $fillable = ['user_id', 'bname', 'aname', 'ano'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
