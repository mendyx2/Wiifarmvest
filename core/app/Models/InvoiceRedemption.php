<?php
// app/Models/InvoiceRedemption.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceRedemption extends Model
{
    protected $fillable = [
        'invoice_id', 'stockist_id', 'user_id', 'redeemed_items', 
        'total_amount', 'notes', 'redeemed_at'
    ];

    protected $casts = [
        'redeemed_items' => 'array',
        'redeemed_at' => 'datetime'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}