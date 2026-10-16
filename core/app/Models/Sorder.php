<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
 
class Sorder extends Model
{
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function scopeInActive($query)
    {
        return $query->where('status', Status::INACTIVE);
    }

    public function statusSorderBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::ORDER_PENDING) {
                $html = '<span class="badge badge--info">' . trans("Pending") . '</span>';
            } elseif ($this->status == Status::ORDER_CONFIRM) {
                $html = '<span class="badge badge--success">' . trans("Confirm") . '</span>';
            } elseif ($this->status == Status::ORDER_DELIVERED) {
                $html = '<span class="badge badge--primary">' . trans("Delivered") . '</span>';
            } else {
                $html = '<span class="badge badge-cancelled">' . trans("Cancelled") . '</span>';
            }
            return $html;
        });
    }
}
