<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class AffiliateOrderItem extends Model
{
    protected $fillable = [
        'affiliate_order_id', 'product_id', 'product_name',
        'quantity', 'unit_price', 'line_total', 'bonus_amount',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'unit_price'   => 'float',
        'line_total'   => 'float',
        'bonus_amount' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(AffiliateOrder::class, 'affiliate_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    
}
