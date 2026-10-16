<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stockist_store_record extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

}
