<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

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
                $project->slug = static::nextAvailableSlug($project->name);
            }
        });
    }

    /** @param array{name: string, description?: ?string, visibility: string} $attributes */
    public static function createForOwner(User $owner, array $attributes): self
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return static::create([
                    ...$attributes,
                    'user_id' => $owner->id,
                    'slug' => static::nextAvailableSlug($attributes['name']),
                ]);
            } catch (QueryException $exception) {
                if (! static::isSlugConflict($exception) || $attempt === 4) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Project creation retry loop completed unexpectedly.');
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

    public function scopeAccessibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where($query->qualifyColumn('visibility'), 'public');

            if ($user) {
                $query->orWhere($query->qualifyColumn('user_id'), $user->id)
                    ->orWhereHas('collaborators', fn (Builder $collaborators): Builder => $collaborators->whereKey($user->id));
            }
        });
    }

    public function hasForeignFolderReferences(): bool
    {
        $folderIds = $this->folders()->select('id');

        // Foreign child folders can be deleted by a database cascade even when PHP skips them.
        return ProjectFolder::whereIn('parent_id', $folderIds)->where('project_id', '!=', $this->id)->exists()
            || ProjectFile::whereIn('folder_id', $folderIds)->where('project_id', '!=', $this->id)->exists();
    }

    /**
     * All checkpoints for this project.
     */
    public function checkpoints()
    {
        return $this->hasMany(Checkpoint::class)->latest();
    }

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(Comment::class, Checkpoint::class);
    }

    /**
     * All files across all checkpoints for this project.
     */
    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * The latest checkpoint.
     */
    public function latestCheckpoint()
    {
        return $this->hasOne(Checkpoint::class)->latestOfMany();
    }

    /**
     * The tags associated with this project.
     */
    public function tags()
    {
        return $this->hasMany(ProjectTag::class);
    }

    /**
     * Folders within this project.
     */
    public function folders()
    {
        return $this->hasMany(ProjectFolder::class);
    }

    /**
     * If this project is a fork, get the original project.
     */
    public function originalProject()
    {
        return $this->hasOneThrough(
            Project::class,
            ProjectFork::class,
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
            Project::class,
            ProjectFork::class,
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
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Collaborators on this project.
     */
    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'project_collaborators', 'project_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Check if a user has access to view this project.
     */
    public function hasAccess(?User $user): bool
    {
        if ($this->visibility === 'public') {
            return true;
        }
        if (! $user) {
            return false;
        }
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->collaborators()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user can edit/upload to this project.
     */
    public function canEdit(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->collaborators()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'editor')
            ->exists();
    }

    private static function nextAvailableSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? Str::limit($base, 240, '') : 'project';
        $candidate = $base;
        $suffix = 1;

        while (static::where('slug', $candidate)->exists()) {
            $candidate = Str::limit($base, 240 - strlen((string) $suffix), '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private static function isSlugConflict(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true)
            || in_array($exception->errorInfo[1] ?? null, [19, 1062], true);
    }
}
