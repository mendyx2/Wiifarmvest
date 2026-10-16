<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Rmatrix extends Model
{
    use HasFactory;
   
    protected $fillable = [
        'stage_id', 
        'user_id', 
        'parent_id', 
    ];
    
    
}