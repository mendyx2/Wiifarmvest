<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    
    public function user()
    {
    	return $this->belongsTo(User::class, 'user_id');
    }
 
    public function createUser()
    {
    	return $this->belongsTo(User::class, 'generate_user_id');
    }

}
