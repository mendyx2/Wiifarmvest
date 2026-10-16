<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'icon', 'color',
        'amount', 'direct_commission', 'indirect_commission',
        'pv', 'pairing_per_day', 'cash_back', 'monthly_maintenance',
        'upgrade_bonus', 'unilevel_bonus',
        'max_pairing_slots', 'upgrade_allowed', 'is_default',
        'sort_order', 'status',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'direct_commission'   => 'decimal:2',
        'indirect_commission' => 'decimal:2',
        'pv'                  => 'decimal:2',
        'pairing_per_day'     => 'decimal:2',
        'cash_back'           => 'decimal:2',
        'monthly_maintenance' => 'decimal:2',
        'upgrade_bonus'       => 'decimal:2',
        'unilevel_bonus'      => 'decimal:2',
        'upgrade_allowed'     => 'boolean',
        'is_default'          => 'boolean',
        'status'              => 'boolean',
    ]; 

    // ── Relationships ──────────────────────────────────────────────

    public function users()
    {
        return $this->hasMany(User::class, 'project_id');
    }

    public function unilevelGenerations()
    {
        return $this->belongsToMany(
            UnilevelGeneration::class,
            'project_unilevel_generation',
            'project_id',
            'generation_id'
        )->orderBy('number');
    }


    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('sort_order')->orderBy('amount');
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function subscriberCount(): int
    {
        return $this->users()->count();
    }

    public function hasSubscribers(): bool
    {
        return $this->users()->exists();
    }

    public function directAmount(): float
    {
        return round((float) $this->direct_commission, 2);
    }

    public function indirectAmount(): float
    {
        return round((float) $this->indirect_commission, 2);
    }

    public function cashBackAmount(): float
    {
        return round((float) $this->cash_back, 2);
    }

    // ── Boot ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->slug)) {
                $project->slug = static::uniqueSlug($project->title);
            }
        });

        static::updating(function (Project $project) {
            if ($project->isDirty('title') && empty($project->getOriginal('slug'))) {
                $project->slug = static::uniqueSlug($project->title);
            }
        });
    }

    private static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
