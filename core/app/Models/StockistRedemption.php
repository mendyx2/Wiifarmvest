<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Unified accounting ledger for every unit/redemption a stockist processes,
 * regardless of channel. Written alongside (not instead of) the existing
 * per-channel records (InvoiceRedemption, WelcomePackage, AffiliateOrder,
 * Stransaction) so reporting has one table to sum across all four channels.
 */
class StockistRedemption extends Model
{
    const TYPE_CASH              = 'cash';
    const TYPE_INVOICE           = 'invoice_code';
    const TYPE_WELCOME_PACK      = 'welcome_pack';
    const TYPE_AFFILIATE_INVOICE = 'affiliate_invoice';
 
    public static array $types = [
        self::TYPE_CASH              => 'Cash',
        self::TYPE_INVOICE           => 'Invoice Code',
        self::TYPE_WELCOME_PACK      => 'Welcome Pack Code',
        self::TYPE_AFFILIATE_INVOICE => 'Affiliate Invoice Code',
    ];

    protected $fillable = [
        'stockist_id', 'type', 'trx', 'reference_code',
        'invoice_id', 'welcome_package_id', 'affiliate_order_id',
        'customer_user_id', 'buyer_name',
        'items', 'quantity', 'amount', 'notes', 'redeemed_at',
    ];

    protected $casts = [
        'items'       => 'array',
        'amount'      => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function welcomePackage()
    {
        return $this->belongsTo(WelcomePackage::class);
    }

    public function affiliateOrder()
    {
        return $this->belongsTo(AffiliateOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::$types[$this->type] ?? $this->type;
    }
}
