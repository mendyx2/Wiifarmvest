<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use GlobalStatus;

    /** The members' holdings of this plan. */
    public function rinvestments()
    {
        return $this->hasMany(Rinvestment::class, 'plan_id');
    }
}
