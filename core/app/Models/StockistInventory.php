<?php
// app/Models/StockistInventory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockistInventory extends Model
{
    protected $fillable = [
        'stockist_id', 'product_id', 'quantity', 'min_stock_level',
        'max_stock_level', 'unit_cost', 'total_value', 'last_restocked_at'
    ];
 
    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'last_restocked_at' => 'datetime'
    ];

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getIsLowStockAttribute()
    {
        return $this->quantity <= $this->min_stock_level;
    }

    public function getNeedsRestockingAttribute()
    {
        return $this->quantity < $this->min_stock_level;
    }

    public function getStockStatusAttribute()
    {
        if ($this->quantity == 0) {
            return 'out_of_stock';
        } elseif ($this->is_low_stock) {
            return 'low_stock';
        } elseif ($this->quantity >= $this->max_stock_level) {
            return 'over_stock';
        } else {
            return 'in_stock';
        }
    }
}