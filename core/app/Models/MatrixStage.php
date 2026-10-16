<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatrixStage extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'level', 'width', 'price'];
    
    public function matrices()
    {
        return $this->hasMany(Matrix::class);
    }
    
    // Get the matrix dimensions
    public function getDimensions()
    {
        return [
            'width' => $this->width,
            'depth' => 2 // Fixed depth for all stages in your case
        ];
    }
    
}
