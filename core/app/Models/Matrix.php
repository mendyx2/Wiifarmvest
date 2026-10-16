<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Matrix extends Model
{
 
    protected $fillable = [
        'stage_id',
        'user_id',
        'parent_id',
        'left',
        'right',
        'is_active',
        'pv_left',
        'pv_right',
        'pv_left_pairing',
        'pv_right_pairing',
        'position',
    ];

    protected $casts = [
        'pv_left'          => 'decimal:2',
        'pv_right'         => 'decimal:2',
        'pv_left_pairing'  => 'decimal:2',
        'pv_right_pairing' => 'decimal:2',
    ];
    
    public function stage()
    {
        return $this->belongsTo(MatrixStage::class, 'stage_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function parent()
    {
        return $this->belongsTo(Matrix::class, 'parent_id');
    }
    
    // Get left child user
    public function leftChildUser()
    {
        return $this->belongsTo(User::class, 'left');
    }
    
    // Get right child user
    public function rightChildUser()
    {
        return $this->belongsTo(User::class, 'right');
    }
     
    // Get left child matrix entry
    public function leftChildMatrix()
    {
        return $this->belongsTo(Matrix::class, 'left', 'user_id');
    }
    
    // Get right child matrix entry
    public function rightChildMatrix()
    {
        return $this->belongsTo(Matrix::class, 'right', 'user_id');
    }
    
    // Get all children
    public function parentMatric()
    {
        return Matrix::where('user_id', $this->parent_id)->first();
    }

    public function children()
    {
        return Matrix::where('parent_id', $this->id)->get();
    }
    
    // Get available positions
    public function getAvailablePositions()
    {
        $available = [];
        if (!$this->left) $available[] = 'left';
        if (!$this->right) $available[] = 'right';
        return $available;
    }
}