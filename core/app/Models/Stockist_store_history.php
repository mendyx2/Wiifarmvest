<?php

namespace App\Models;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use App\Constants\Status; 
 
class Stockist_store_history extends Model
{
    use GlobalStatus;
     protected $table = 'stockist_store_histories';
    
    public function user()
    {
    	return $this->belongsTo(User::class, 'user_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
   

}
