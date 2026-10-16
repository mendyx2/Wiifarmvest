<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasFactory;
    protected $table = 'supplies';
    

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    

    public function statusOrderBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::SUPPLY_APPROVED) {
                $html = '<span class="badge badge--info">' . trans("APPROVED") . '</span>';
            } elseif ($this->status == Status::SUPPLY_RECEIVED) {
                $html = '<span class="badge badge--success">' . trans("Delivered") . '</span>';
            } else {
                $html = '<span class="badge badge--danger">' . trans("Rejected") . '</span>';
            }
            return $html;
        });
    }
   
}
