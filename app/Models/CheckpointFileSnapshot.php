<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckpointFileSnapshot extends Model
{
    protected $fillable = [
        'checkpoint_id',
        'project_file_id',
        'file_version_id',
        'original_name',
        'mime_type',
        'folder_path',
    ];

    protected function casts(): array
    {
        return [
            'folder_path' => 'array',
        ];
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class)->withTrashed();
    }

    public function fileVersion(): BelongsTo
    {
        return $this->belongsTo(FileVersion::class);
    }
}
