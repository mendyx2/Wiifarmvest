<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
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
}
