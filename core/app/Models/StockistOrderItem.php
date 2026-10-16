<?php
// app/Models/StockistOrderItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockistOrderItem extends Model
{
    protected $fillable = [
        'stockist_order_id', 'product_id', 'quantity', 'unit_price', 'total_price'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(StockistOrder::class, 'stockist_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}