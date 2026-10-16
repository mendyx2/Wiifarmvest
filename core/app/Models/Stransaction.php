<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stransaction extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
