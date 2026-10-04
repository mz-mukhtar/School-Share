<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'checkpoint_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /**
     * The project this file belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The checkpoint this file belongs to.
     */
    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    /**
     * Human-readable file size.
     */
    public function sizeHuman(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    /**
     * File extension (lowercase).
     */
    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    /**
     * Whether this file is previewable in the browser.
     */
    public function isPreviewable(): bool
    {
        $previewable = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            // PDF
            'application/pdf',
            // Text types
            'text/plain', 'text/html', 'text/css', 'text/javascript',
            'application/json', 'text/markdown', 'text/csv',
            'application/x-sh', 'text/x-python',
        ];

        return in_array($this->mime_type, $previewable);
    }

    /**
     * Whether this file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Whether this file is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Whether this file is plain text / code.
     */
    public function isText(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'text/')
            || in_array($this->mime_type, ['application/json', 'application/x-sh', 'text/x-python']);
    }

    /**
     * Icon class for file type.
     */
    public function iconClass(): string
    {
        if ($this->isImage())   return 'bi-file-image text-success';
        if ($this->isPdf())     return 'bi-file-pdf text-danger';
        if ($this->isText())    return 'bi-file-text text-info';
        $ext = $this->extension();
        return match($ext) {
            'doc', 'docx' => 'bi-file-word text-primary',
            'xls', 'xlsx' => 'bi-file-excel text-success',
            'ppt', 'pptx' => 'bi-file-ppt text-warning',
            'zip', 'rar', '7z' => 'bi-file-zip text-secondary',
            default => 'bi-file-earmark text-muted',
        };
    }
}
