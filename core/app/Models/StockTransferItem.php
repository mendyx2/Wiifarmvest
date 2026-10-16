<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'unit_price'     => 'decimal:2',
        'line_total'     => 'decimal:2',
        'rebate_percent' => 'decimal:2',
        'rebate_amount'  => 'decimal:2',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
