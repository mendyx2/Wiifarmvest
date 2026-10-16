<?php
// app/Models/ProductStatePrice.php
namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;

use Illuminate\Database\Eloquent\Model;

class ProductStatePrice extends Model
{
    protected $fillable = ['product_id', 'state_id', 'price', 'mega_stockist_bonus', 'micro_stockist_bonus'];

    /**
     * Rebate % for a stockist of this tier redeeming this product in this state.
     */
    public function bonusPercentFor(Stockist $stockist): float
    {
        return (float) ($stockist->isMega() ? $this->mega_stockist_bonus : $this->micro_stockist_bonus);
    }
     
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
    
    public function getFormattedPriceAttribute()
    {
        return '₦' . number_format($this->price, 2);
    }
}
