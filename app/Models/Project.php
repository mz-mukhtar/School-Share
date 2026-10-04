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
        'tags',
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

    /**
     * The tags associated with this project.
     */
    public function tags()
    {
        return $this->hasMany(\App\Models\ProjectTag::class);
    }

    /**
     * Folders within this project.
     */
    public function folders()
    {
        return $this->hasMany(\App\Models\ProjectFolder::class);
    }

    /**
     * If this project is a fork, get the original project.
     */
    public function originalProject()
    {
        return $this->hasOneThrough(
            \App\Models\Project::class,
            \App\Models\ProjectFork::class,
            'forked_project_id', // Foreign key on project_forks table
            'id', // Foreign key on projects table
            'id', // Local key on projects table
            'original_project_id' // Local key on project_forks table
        );
    }

    /**
     * Projects that are forks of this project.
     */
    public function forks()
    {
        return $this->hasManyThrough(
            \App\Models\Project::class,
            \App\Models\ProjectFork::class,
            'original_project_id', // Foreign key on project_forks table
            'id', // Foreign key on projects table
            'id', // Local key on projects table
            'forked_project_id' // Local key on project_forks table
        );
    }

    /**
     * Activities related to this project.
     */
    public function activities()
    {
        return $this->morphMany(\App\Models\Activity::class, 'subject');
    }
}

