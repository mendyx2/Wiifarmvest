<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnilevelGeneration extends Model
{
    protected $fillable = ['number', 'title', 'percentage', 'description', 'status'];

    protected $casts = [
        'percentage' => 'decimal:4',
        'status'     => 'boolean',
        'number'     => 'integer',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_unilevel_generation', 'generation_id', 'project_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('number');
    }
}
