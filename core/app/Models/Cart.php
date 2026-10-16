<?php
// app/Models/Cart.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'total_amount'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
    public function getStateTheIdAttribute(){

        $stateID = $this->items->map(function ($items) {
            return $items->productstate->state_id;
        });
        return $stateID->first();
    }

    public function updateTotal()
    {
        $this->total_amount = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        $this->save();
    }
}