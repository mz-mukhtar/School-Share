@extends('layouts.app')

@section('title', $file->original_name . ' — ' . $project->name)

@section('content')
{{-- Breadcrumb --}}
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none text-muted">{{ $project->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.checkpoints.show', [$project->slug, $version->checkpoint_id]) }}" class="text-decoration-none text-muted">{{ $version->checkpoint->title }}</a></li>
            <li class="breadcrumb-item active text-white" aria-current="page">{{ $file->original_name }}</li>
        </ol>
    </nav>
</div>

{{-- File header --}}
<div class="ss-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;background:var(--ss-dark-3);border:1px solid var(--ss-border);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i class="bi {{ $file->iconClass() }} fs-3"></i>
            </div>
            <div>
                <h1 class="h4 fw-bold mb-0">{{ $file->original_name }}</h1>
                <div class="text-muted small mt-1">
                    {{ strtoupper($file->extension()) }} &middot; {{ $file->sizeHuman() }}
                    @if($file->mime_type)
                        &middot; {{ $file->mime_type }}
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}"
               class="btn btn-ss-primary d-flex align-items-center gap-2">
                <i class="bi bi-download"></i> Download
            </a>
            @if(auth()->id() === $project->user_id)
                <form method="POST" action="{{ route('projects.files.destroy', [$project->slug, $file->id]) }}"
                      onsubmit="return confirm('Delete this file?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger d-flex align-items-center gap-2">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Preview area --}}
<div class="ss-card">
    @if($file->isImage())
        {{-- Image preview --}}
        <div class="text-center">
            <img src="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}"
                 alt="{{ $file->original_name }}"
                 class="img-fluid rounded"
                 style="max-height:80vh;">
        </div>

    @elseif($file->isPdf())
        {{-- PDF preview --}}
        <iframe src="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}"
                width="100%" height="750"
                style="border:none;border-radius:8px;background:#fff;">
        </iframe>

    @elseif($file->isText() && $content !== null)
        {{-- Text / Code preview --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">{{ $file->original_name }}</span>
            <span class="badge" style="background:var(--ss-dark-3);border:1px solid var(--ss-border);color:var(--ss-text-muted);">
                {{ strtoupper($file->extension()) }}
            </span>
        </div>
        <pre class="mb-0 p-3 rounded" style="background:var(--ss-dark-1);color:#e2e8f0;font-size:0.85rem;overflow:auto;max-height:75vh;white-space:pre-wrap;word-break:break-all;"><code>{{ $content }}</code></pre>

    @elseif($file->isText() && $content === null)
        {{-- Text file too large to preview --}}
        <div class="text-center py-5">
            <i class="bi bi-file-text" style="font-size:3rem;color:var(--ss-text-muted);"></i>
            <p class="text-muted mt-3 mb-4">This file is too large to preview ({{ $file->sizeHuman() }}).</p>
            <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}" class="btn btn-ss-primary">
                <i class="bi bi-download me-2"></i> Download to view
            </a>
        </div>

        {{-- Non-previewable fallback --}}
        <div class="text-center py-5">
            <i class="bi {{ $file->iconClass() }}" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-4">Preview is not available for this file type.</p>
            <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}" class="btn btn-ss-primary">
                <i class="bi bi-download me-2"></i> Download
            </a>
        </div>
    @endif
</div>

{{-- Version History --}}
@if($file->versions->count() > 1)
<div class="ss-card mt-4">
    <h3 class="h5 fw-bold mb-3">Version History</h3>
    <div class="list-group list-group-flush">
        @foreach($file->versions->sortByDesc('id') as $v)
            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 border-secondary">
                <div>
                    <div class="fw-bold text-white">Version {{ $v->version_number }}</div>
                    <div class="small text-muted">
                        {{ $v->created_at->format('M d, Y H:i') }} &middot; {{ $v->sizeHuman() }} &middot; Checkpoint: <a href="{{ route('projects.checkpoints.show', [$project->slug, $v->checkpoint_id]) }}" class="text-decoration-none">{{ $v->checkpoint->title }}</a>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @if($v->id !== $version->id)
                        <a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}?version_id={{ $v->id }}" class="btn btn-outline-secondary btn-sm">View</a>
                        <a href="{{ route('projects.files.diff', [$project->slug, $file->id]) }}?from={{ $v->id }}&to={{ $version->id }}" class="btn btn-ss-primary btn-sm">Diff with {{ $version->version_number }}</a>
                    @else
                        <span class="badge bg-secondary d-flex align-items-center">Currently Viewing</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
