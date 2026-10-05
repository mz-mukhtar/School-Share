<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkpoint extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'message',
        'total_size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'total_size_bytes' => 'integer',
        ];
    }

    /**
     * The project this checkpoint belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The user who created this checkpoint.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The file versions included in this checkpoint.
     */
    public function fileVersions(): HasMany
    {
        return $this->hasMany(FileVersion::class);
    }

    public function fileSnapshots(): HasMany
    {
        return $this->hasMany(CheckpointFileSnapshot::class);
    }

    public function folderSnapshots(): HasMany
    {
        return $this->hasMany(CheckpointFolderSnapshot::class);
    }

    /**
     * Comments made on this checkpoint.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Activities related to this checkpoint.
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Human-readable total size.
     */
    public function totalSizeHuman(): string
    {
        $bytes = $this->total_size_bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
