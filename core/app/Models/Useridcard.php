<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Useridcard extends Model
{
    protected $table = 'useridcards';
      protected $fillable = [
        'user_id'
    ];
}
