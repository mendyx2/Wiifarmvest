<?php
// app/Models/UserFlashcardPreference.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFlashcardPreference extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'user_id',
        'type',
        'content_url',
        'title',
        'description',
        'is_active',
        'is_dismissed',
        'dismissed_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotDismissed($query)
    {
        return $query->where('is_dismissed', false);
    }
}