<?php
// app/Models/Invoice.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'order_id', 'invoice_code', 'items', 'total_amount', 
        'redeemed_at', 'is_fully_redeemed', 'fully_redeemed_at'
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'fully_redeemed_at' => 'datetime',
        'is_fully_redeemed' => 'boolean'
    ];
        
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function redemptions()
    {
        return $this->hasMany(InvoiceRedemption::class);
    }
    public function getRemainingItemsAttribute()
    {
        try {
            // Get all redemptions for this invoice
            $redeemedItems = [];
            foreach ($this->redemptions as $redemption) {
                $items = $redemption->redeemed_items;
                if (is_string($items)) {
                    $items = json_decode($items, true);
                }
                
                foreach ($items as $item) {
                    $productId = $item['product_id'];
                    $quantity = $item['quantity'];
                    
                    if (!isset($redeemedItems[$productId])) {
                        $redeemedItems[$productId] = 0;
                    }
                    $redeemedItems[$productId] += $quantity;
                }
            }

            // Get original items from invoice
            $originalItems = [];
            $items = $this->items;
            if (is_string($items)) {
                $items = json_decode($items, true);
            }
            
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                
                if (!isset($originalItems[$productId])) {
                    $originalItems[$productId] = 0;
                }
                $originalItems[$productId] += $quantity;
            }

            // Calculate remaining items
            $remainingItems = [];
            foreach ($originalItems as $productId => $originalQty) {
                $redeemedQty = $redeemedItems[$productId] ?? 0;
                $remainingQty = $originalQty - $redeemedQty;
                
                if ($remainingQty > 0) {
                    $remainingItems[$productId] = $remainingQty;
                }
            }

            return $remainingItems;

        } catch (\Exception $e) {
            \Log::error("Error calculating remaining items for invoice {$this->id}: " . $e->getMessage());
            return [];
        }
    }
    public function getIsFullyRedeemedAttribute()
    {
        return count($this->remaining_items) === 0;
    }

    public function getOriginalItemsAttribute()
    {
        try {
            $items = $this->items;
            if (is_string($items)) {
                return json_decode($items, true);
            }
            return $items ?? [];
        } catch (\Exception $e) {
            \Log::error("Error getting original items for invoice {$this->id}: " . $e->getMessage());
            return [];
        }
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            $invoice->invoice_code = 'INV-' . strtoupper(uniqid());
        });
    }
}