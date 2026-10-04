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
        'name',
    ];

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
        while ($current) {
            array_unshift($breadcrumbs, $current);
            $current = $current->parent;
        }
        return $breadcrumbs;
    }
}
