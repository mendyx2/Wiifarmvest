<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Stateleader extends Model
{
    use GlobalStatus;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function addWallet($amount)
    {
        $this->wallet += $amount;
        $this->save();
        return true;
       
    }

    // public function stockist()
    // {
    //     return $this->hasOne(Stockist::class);
    // }
}
