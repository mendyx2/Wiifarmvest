<?php

namespace App\Models;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status; 
 
class Stockist_store extends Model
{
    use GlobalStatus;
    protected $table = 'stockist_stores';
     protected $fillable = [
        'user_id', 'product_id', 'quantity', 'is_active', 'status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'integer',
        'min_stock_level' => 'integer'
    ];
    
    public function sorder()
    {
        return $this->belongsTo(Sorder::class, 'sorder_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

     public function stockist()
    {
        return $this->belongsTo(Stockist::class, 'user_id');
    }


    public function incrementStock($quantity)
    {
        $this->increment('quantity', $quantity);
        $this->update(['is_active' => true]);
    }

    public function decrementStock($quantity)
    {
        $this->decrement('quantity', $quantity);
        if ($this->quantity <= 0) {
            $this->update(['is_active' => false]);
        }
    }
    /*
    public function addToWallet($amount)
    {
        $this->wallet += $amount;
        $this->save();
        return true;
    }
    */
    
   

}
