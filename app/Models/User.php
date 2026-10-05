<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'plan' => 'free',
        'storage_used_bytes' => 0,
    ];

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

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->username)) {
                $user->username = static::nextAvailableUsername($user->name);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The projects (repositories) that belong to this user.
     */
    public function projects(): HasMany
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
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
                    ->withPivot('created_at');
    }

    /**
     * Users that follow this user.
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
                    ->withPivot('created_at');
    }

    /**
     * Activities performed by this user.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\App\Models\Activity::class);
    }

    /**
     * Comments made by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    /**
     * Projects that this user has been added to as a collaborator.
     */
    public function sharedProjects()
    {
        return $this->belongsToMany(\App\Models\Project::class, 'project_collaborators', 'user_id', 'project_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function upgradeRequests(): HasMany
    {
        return $this->hasMany(UpgradeRequest::class);
    }

    // -------------------------------------------------------------------------
    // Storage Helpers
    // -------------------------------------------------------------------------

    /**
     * Maximum storage in bytes for this user's plan.
     */
    public function maxStorageBytes(): int
    {
        $gb = $this->planConfiguration()['storage_gb'];
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
        return $this->planConfiguration()['max_projects'];
    }

    public function maxCollaborators(): int
    {
        return $this->planConfiguration()['max_collaborators'];
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
        return $this->plan === 'free';
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

    /** @return array{storage_gb: int, max_projects: int, max_collaborators: int} */
    private function planConfiguration(): array
    {
        return config('schoolshare.plans.'.$this->plan, config('schoolshare.plans.free'));
    }

    private static function nextAvailableUsername(string $name): string
    {
        $base = Str::slug(Str::ascii($name), '_');
        $base = $base !== '' ? Str::limit($base, 56, '') : 'user';
        $candidate = $base;
        $suffix = 1;

        while (static::where('username', $candidate)->exists()) {
            $candidate = Str::limit($base, 56 - strlen((string) $suffix), '').'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
