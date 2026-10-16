<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStageProgress extends Model
{
    use HasFactory;
    protected $table = 'user_stage_progress';

    protected $fillable = ['user_id', 'stage_id', 'is_completed', 'completed_at'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function stage()
    {
        return $this->belongsTo(MatrixStage::class, 'stage_id');
    }
   
}
