<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flashcard extends Model
{
    protected $fillable = ['title', 'description', 'type', 'file', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function viewers()
    {
        return $this->belongsToMany(User::class, 'flashcard_views')
                    ->withPivot('viewed_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFileUrlAttribute(): string
    {
        return getImage(getFilePath('flashcard') . '/' . $this->file);
    }
}
