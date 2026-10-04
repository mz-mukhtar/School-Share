<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'folder_id',
        'latest_version_id',
        'version_count',
        'original_name',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'version_count' => 'integer',
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
     * The folder this file belongs to.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(ProjectFolder::class);
    }

    /**
     * All versions of this file.
     */
    public function versions()
    {
        return $this->hasMany(FileVersion::class);
    }

    /**
     * The latest version of this file.
     */
    public function latestVersion(): BelongsTo
    {
        return $this->belongsTo(FileVersion::class, 'latest_version_id');
    }

    /**
     * Human-readable file size of the latest version.
     */
    public function sizeHuman(): string
    {
        $version = $this->latestVersion;
        if (!$version) return '0 B';
        return $version->sizeHuman();
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
            // Video / Audio
            'video/mp4', 'video/webm', 'video/ogg',
            'audio/mpeg', 'audio/wav', 'audio/ogg',
            // Office
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
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

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'audio/');
    }

    public function isOffice(): bool
    {
        $officeMimes = [
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
        return in_array($this->mime_type, $officeMimes);
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
