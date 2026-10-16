<?php
// app/Models/StockistService.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockistService extends Model
{
    protected $fillable = [
        'stockist_id', 'service_name', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }
}