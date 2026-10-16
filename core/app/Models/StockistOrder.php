<?php
// app/Models/StockistOrder.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockistOrder extends Model
{
    protected $fillable = [
        'order_number', 'stockist_id', 'total_amount', 'shipping_cost',
        'tax_amount', 'grand_total', 'status', 'notes', 'approved_at',
        'shipped_at', 'delivered_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            $order->order_number = 'STO-' . strtoupper(uniqid());
        });
    }

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }

    public function items()
    {
        return $this->hasMany(StockistOrderItem::class);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'approved']);
    }
    public function canBeDelivered()
    {
        return in_array($this->status, ['shipped', 'approved']);
    }

    public function canBeApproved()
    {
        return $this->status === 'pending';
    }

    public function canBeShipped()
    {
        return in_array($this->status, ['approved', 'processing']);
    }

    public function markAsApproved()
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now()
        ]);
    }

    public function markAsShipped()
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now()
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-warning',
            'approved' => 'bg-info', 
            'processing' => 'bg-primary',
            'shipped' => 'bg-secondary',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger'
        ];

        return $badges[$this->status] ?? 'bg-secondary';
    }

    public function getStatusIconAttribute()
    {
        $icons = [
            'pending' => 'fa-clock',
            'approved' => 'fa-check',
            'processing' => 'fa-cog',
            'shipped' => 'fa-shipping-fast',
            'delivered' => 'fa-check-circle',
            'cancelled' => 'fa-times'
        ];

        return $icons[$this->status] ?? 'fa-question';
    }

    public function getStatusBadgeClass()
{
    switch ($this->status) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'info';
        case 'processing':
            return 'primary';
        case 'shipped':
            return 'primary';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
}