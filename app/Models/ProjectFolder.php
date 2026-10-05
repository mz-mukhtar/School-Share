<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFolder extends Model
{
    protected $fillable = [
        'project_id',
        'parent_id',
        'parent_scope',
        'name',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProjectFolder $folder): void {
            $folder->parent_scope = $folder->parent_id ?? 0;
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectFolder::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class, 'folder_id');
    }

    /**
     * Get the folder path as an array of ancestors.
     */
    public function breadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;
        $visited = [];
        while ($current && ! isset($visited[$current->id])) {
            $visited[$current->id] = true;
            array_unshift($breadcrumbs, $current);
            $current = $current->parent()->where('project_id', $this->project_id)->first();
        }

        return $breadcrumbs;
    }
}
