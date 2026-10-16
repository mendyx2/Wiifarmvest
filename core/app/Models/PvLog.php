<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PvLog extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function positionBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->position == Status::LEFT) {
                $html = '<span class="badge badge--success">' . trans('Left') . '</span>';
            } else {
                $html = '<span class="badge badge--primary">' . trans('Right') . '</span>';
            }
            return $html;
        });
    } 

    public function scopeLeftPV($query)
    {
        return $query->where('position', 1)->where('trx_type','+');
    }
    
    public function scopeRightPV($query)
    {
        return $query->where('position', 2)->where('trx_type', '+');
    }
   
    public function scopeCutPV($query)
    {
        return $query->where('trx_type', '-');
    }
    public function scopePaidPV($query)
    {
        return $query->where('trx_type', '+');
    }
}
