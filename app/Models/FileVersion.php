<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileVersion extends Model
{
    use HasFactory;

    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'project_file_id',
        'checkpoint_id',
        'storage_path',
        'size_bytes',
        'mime_type',
        'version_number',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class);
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function snapshotReferences(): HasMany
    {
        return $this->hasMany(CheckpointFileSnapshot::class);
    }

    public function sizeHuman(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function isText(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'text/')
            || in_array($this->mime_type, ['application/json', 'application/x-sh', 'text/x-python'], true);
    }
}
