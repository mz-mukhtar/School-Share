<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckpointFolderSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkpoint_id',
        'source_folder_id',
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
}
