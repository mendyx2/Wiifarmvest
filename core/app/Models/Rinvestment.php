<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class Rinvestment extends Model
{

    use HasFactory;
    use GlobalStatus;
    protected $table = 'rinvestments';


    public function plan(){
        return $this->belongsTo(Plan::class, 'plan_id');
    }


    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shtransactions(){
        return $this->hasMany(Shtransaction::class);
    }

}
