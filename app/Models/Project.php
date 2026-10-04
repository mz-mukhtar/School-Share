<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'subject_tag',
        'visibility',
        'default_branch',
        'star_count',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            // Generate slug if not provided
            if (empty($project->slug)) {
                $baseSlug = Str::slug($project->name);
                $slug = $baseSlug;
                $count = 1;
                // Ensure slug is unique per user
                while (static::where('user_id', $project->user_id)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $count;
                    $count++;
                }
                $project->slug = $slug;
            }
        });
    }

    /**
     * Get the route key for the model.
     * We use the slug so URLs are /projects/{slug}
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The owner of the project.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include public projects.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * All checkpoints for this project.
     */
    public function checkpoints()
    {
        return $this->hasMany(\App\Models\Checkpoint::class)->latest();
    }

    /**
     * All files across all checkpoints for this project.
     */
    public function files()
    {
        return $this->hasMany(\App\Models\ProjectFile::class);
    }

    /**
     * The latest checkpoint.
     */
    public function latestCheckpoint()
    {
        return $this->hasOne(\App\Models\Checkpoint::class)->latestOfMany();
    }
}

