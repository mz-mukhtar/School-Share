<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'avatar_path',
        'bio',
        'github_url',
        'linkedin_url',
        'plan',
        'storage_used_bytes',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'last_active_at'     => 'datetime',
            'password'           => 'hashed',
            'storage_used_bytes' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The projects (repositories) that belong to this user.
     */
    public function projects()
    {
        return $this->hasMany(\App\Models\Project::class);
    }

    /**
     * Projects starred by this user.
     */
    public function starredProjects()
    {
        return $this->belongsToMany(\App\Models\Project::class, 'starred_projects')
                    ->withTimestamps();
    }

    /**
     * Users that this user is following.
     */
    public function following()
    {
        return $this->hasMany(\App\Models\Follow::class, 'follower_id');
    }

    /**
     * Users that follow this user.
     */
    public function followers()
    {
        return $this->hasMany(\App\Models\Follow::class, 'following_id');
    }

    /**
     * Activities performed by this user.
     */
    public function activities()
    {
        return $this->hasMany(\App\Models\Activity::class);
    }

    /**
     * Comments made by this user.
     */
    public function comments()
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    // -------------------------------------------------------------------------
    // Storage Helpers
    // -------------------------------------------------------------------------

    /**
     * Maximum storage in bytes for this user's plan.
     */
    public function maxStorageBytes(): int
    {
        $gb = config("schoolshare.{$this->plan}_plan.storage_gb", 3);
        return (int) ($gb * 1024 * 1024 * 1024);
    }

    /**
     * Remaining storage available in bytes.
     */
    public function remainingStorageBytes(): int
    {
        return max(0, $this->maxStorageBytes() - $this->storage_used_bytes);
    }

    /**
     * Storage usage as a percentage (0–100).
     */
    public function storageUsagePercent(): float
    {
        $max = $this->maxStorageBytes();
        if ($max === 0) return 100.0;
        return round(($this->storage_used_bytes / $max) * 100, 1);
    }

    /**
     * Human-readable storage used (e.g. "512 MB").
     */
    public function storageUsedHuman(): string
    {
        return $this->formatBytes($this->storage_used_bytes);
    }

    /**
     * Human-readable max storage (e.g. "3 GB").
     */
    public function maxStorageHuman(): string
    {
        return $this->formatBytes($this->maxStorageBytes());
    }

    /**
     * Returns true if the user has no space left.
     */
    public function isStorageFull(): bool
    {
        return $this->storage_used_bytes >= $this->maxStorageBytes();
    }

    // -------------------------------------------------------------------------
    // Plan Helpers
    // -------------------------------------------------------------------------

    /**
     * Maximum number of projects for this user's plan.
     */
    public function maxProjects(): int
    {
        return config("schoolshare.{$this->plan}_plan.max_projects", 15);
    }

    /**
     * Whether the user can create another project.
     */
    public function canCreateProject(): bool
    {
        return $this->projects()->count() < $this->maxProjects();
    }

    /**
     * Whether the user is on the student (free) plan.
     */
    public function isStudent(): bool
    {
        return $this->plan === 'student';
    }

    /**
     * Whether the user is on the pro plan.
     */
    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
