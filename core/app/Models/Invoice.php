<?php
// app/Models/Invoice.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['order_id', 'invoice_code', 'items', 'total_amount', 'redeemed_at'];

         
    public function redemptions()
    {
        return $this->hasMany(InvoiceRedemption::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
     public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function getRemainingItemsAttribute()
    {
        $redeemedItems = $this->redemptions->flatMap(function ($redemption) {
            return collect($redemption->redeemed_items);
        })->groupBy('product_id')->map(function ($items) {
            return $items->sum('quantity');
        });

        $originalItems = collect(json_decode($this->items, true))->groupBy('product_id')->map(function ($items) {
            return $items->sum('quantity');
        });

        $remaining = [];
        foreach ($originalItems as $productId => $originalQty) {
            $redeemedQty = $redeemedItems[$productId] ?? 0;
            if ($redeemedQty < $originalQty) {
                $remaining[$productId] = $originalQty - $redeemedQty;
            }
        }

        return $remaining;
    }
    public function getIsFullyRedeemedAttribute()
    {
        return count($this->remaining_items) == 0;
    }



    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /*
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            $invoice->invoice_code = 'INV-' . strtoupper(uniqid());
        });
    }
    */
}