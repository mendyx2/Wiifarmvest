<?php
// app/Models/StockistProduct.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockistProduct extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'stock_quantity', 'min_order_quantity',
        'max_order_quantity', 'is_active', 'image', 'specifications'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'specifications' => 'array'
    ];

    public function inventory()
    {
        return $this->hasMany(StockistInventory::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(StockistOrderItem::class, 'product_id');
    }

    public function getIsAvailableAttribute()
    {
        return $this->is_active && $this->stock_quantity > 0;
    }

    public function getFormattedPriceAttribute()
    {
        return '₦' . number_format($this->price, 2);
    }
}