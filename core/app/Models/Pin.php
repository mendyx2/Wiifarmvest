<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    
    public function user()
    {
    	return $this->belongsTo(User::class, 'user_id');
    }
 
    public function createUser()
    {
    	return $this->belongsTo(User::class, 'generate_user_id');
    }

    /**
     * A new, unused pin code: four groups of eight digits from a secure
     * random source. Shared by the member's "Generate pin" and the admin's
     * "Create Pins" (which used rand(), a predictable generator).
     */
    public static function newCode(): string
    {
        do {
            $code = implode('-', array_map(fn () => (string) random_int(10000000, 99999999), range(1, 4)));
        } while (self::where('pin', $code)->exists());

        return $code;
    }
}
