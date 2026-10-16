<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Order extends Model
{
    protected $fillable = ['user_id', 'invoice_code', 'total_amount', 'status', 'state_id'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
 
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function scopeInActive($query)
    {
        return $query->where('status', Status::INACTIVE);
    }
    public function scopeCancel($query)
    {
        return $query->where('status', Status::CANCEL);
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }
    

    public function statusOrderBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::ORDER_PENDING) {
                $html = '<span class="badge badge--warning">' . trans("Pending") . '</span>';
            } elseif ($this->status == Status::ORDER_SHIPPED) {
                $html = '<span class="badge badge--success">' . trans("Shipped") . '</span>';
            } else {
                $html = '<span class="badge badge--danger">' . trans("Cancelled") . '</span>';
            }
            return $html;
        });
    }

    public function statusShowBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::ORDER_PENDING) {
                $html = 'PENDING';
            } elseif ($this->status == Status::ORDER_PAID) {
                $html = 'PAID';
            }elseif ($this->status == Status::ORDER_REDEEMED) {
                $html = 'REDEEMED';
            } else {
                $html = 'CANCELED';
            }
            return $html;
        }); 
    } 
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            $order->invoice_code = 'INV-' . strtoupper(uniqid());
        });
    }

}
