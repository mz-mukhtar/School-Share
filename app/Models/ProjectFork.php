<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFork extends Model
{
    public $timestamps = false; // only created_at

    protected $fillable = [
        'original_project_id',
        'forked_project_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'original_project_id');
    }

    public function fork(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'forked_project_id');
    }
}
