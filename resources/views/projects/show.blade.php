@extends('layouts.app')

@section('title', $project->name)

@section('content')

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('projects.index') }}" class="text-decoration-none text-muted">{{ $project->owner->name }}</a>
                    </li>
                    <li class="breadcrumb-item active fw-bold text-white" aria-current="page">{{ $project->name }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                <span class="badge rounded-pill" style="background:var(--ss-dark-3);border:1px solid var(--ss-border);color:var(--ss-text-muted);">
                    <i class="bi {{ $project->visibility === 'public' ? 'bi-globe' : 'bi-lock-fill' }} me-1"></i>
                    {{ ucfirst($project->visibility) }}
                </span>
                @if($project->subject_tag)
                    <span class="badge rounded-pill" style="background:rgba(6,182,212,0.1);border:1px solid rgba(6,182,212,0.2);color:#67e8f9;">
                        {{ $project->subject_tag }}
                    </span>
                @endif
            </div>
            @if($project->description)
                <p class="text-muted mt-2 mb-0" style="max-width:700px;">{{ $project->description }}</p>
            @endif
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->id() === $project->user_id)
                <a href="{{ route('projects.checkpoints.create', $project->slug) }}" class="btn btn-ss-primary d-flex align-items-center gap-2">
                    <i class="bi bi-cloud-upload"></i> New Checkpoint
                </a>
                <a href="{{ route('projects.edit', $project->slug) }}" class="btn btn-ss-outline d-flex align-items-center gap-1">
                    <i class="bi bi-gear"></i> Settings
                </a>
            @endif
            <button class="btn btn-ss-outline d-flex align-items-center gap-1" disabled>
                <i class="bi bi-star"></i> <span>{{ $project->star_count }}</span>
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(34,197,94,0.1);border-color:rgba(34,197,94,0.3);color:#86efac;">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.3);color:#fca5a5;">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Main content ─────────────────────────────────────────────────────── --}}
@if($latestCheckpoint)
    {{-- Files from the latest checkpoint --}}
    <div class="ss-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-folder2-open text-primary"></i>
                    Latest files
                </h5>
                <small class="text-muted">
                    From checkpoint: <a href="{{ route('projects.checkpoints.show', [$project->slug, $latestCheckpoint->id]) }}" class="text-decoration-none" style="color:var(--ss-primary);">{{ $latestCheckpoint->title }}</a>
                    &mdash; {{ $latestCheckpoint->created_at->diffForHumans() }}
                </small>
            </div>
            <a href="{{ route('projects.checkpoints.index', $project->slug) }}" class="btn btn-ss-outline btn-sm">
                <i class="bi bi-clock-history me-1"></i> All Checkpoints
            </a>
        </div>

        @if($latestCheckpoint->files->isEmpty())
            <div class="text-center py-3 text-muted small">No files in this checkpoint.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="color:var(--ss-text-primary);">
                    <thead>
                        <tr style="border-color:var(--ss-border);">
                            <th class="text-muted fw-normal ps-0" style="width:40px;"></th>
                            <th class="text-muted fw-normal">Name</th>
                            <th class="text-muted fw-normal text-end">Size</th>
                            <th class="text-muted fw-normal text-end pe-0">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestCheckpoint->files as $file)
                            <tr style="border-color:var(--ss-border);">
                                <td class="ps-0 align-middle text-center">
                                    <i class="bi {{ $file->iconClass() }} fs-5"></i>
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}"
                                       class="text-decoration-none text-white fw-medium">
                                        {{ $file->original_name }}
                                    </a>
                                </td>
                                <td class="align-middle text-end text-muted small">{{ $file->sizeHuman() }}</td>
                                <td class="align-middle text-end pe-0">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @if($file->isPreviewable())
                                            <a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}"
                                               class="btn btn-link btn-sm p-0 text-muted" title="Preview">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}"
                                           class="btn btn-link btn-sm p-0 text-muted" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @if(auth()->id() === $project->user_id)
                                            <form method="POST" action="{{ route('projects.files.destroy', [$project->slug, $file->id]) }}"
                                                  onsubmit="return confirm('Delete this file?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Checkpoint history preview --}}
    <div class="ss-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Checkpoint History
            </h5>
            <a href="{{ route('projects.checkpoints.index', $project->slug) }}" class="btn btn-ss-outline btn-sm">View all</a>
        </div>

        <div class="timeline">
            @foreach($checkpoints as $cp)
                <div class="d-flex gap-3 pb-3 {{ !$loop->last ? 'mb-2 border-bottom' : '' }}" style="border-color: var(--ss-border) !important;">
                    <div class="flex-shrink-0 mt-1">
                        <div style="width:32px;height:32px;background:var(--ss-dark-3);border:1px solid var(--ss-border);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-flag-fill text-primary" style="font-size:0.7rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <a href="{{ route('projects.checkpoints.show', [$project->slug, $cp->id]) }}"
                                   class="fw-bold text-white text-decoration-none">
                                    {{ $cp->title }}
                                </a>
                                @if($loop->first)
                                    <span class="badge ms-1" style="background:rgba(6,182,212,0.15);color:#67e8f9;font-size:0.65rem;">Latest</span>
                                @endif
                                <div class="text-muted small mt-1">
                                    {{ $cp->files_count }} {{ Str::plural('file', $cp->files_count) }}
                                    &middot; {{ $cp->totalSizeHuman() }}
                                    &middot; {{ $cp->created_at->diffForHumans() }}
                                </div>
                            </div>
                            @if(auth()->id() === $project->user_id)
                                <form method="POST" action="{{ route('projects.checkpoints.destroy', [$project->slug, $cp->id]) }}"
                                      onsubmit="return confirm('Delete this checkpoint and all its files?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-muted ms-2" title="Delete checkpoint">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                        @if($cp->message)
                            <p class="text-muted small mt-1 mb-0 text-truncate" style="max-width:500px;">{{ $cp->message }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@else
    {{-- Empty state --}}
    <div class="ss-card text-center py-5">
        <div class="mb-3">
            <i class="bi bi-cloud-upload" style="font-size:3.5rem;color:var(--ss-primary);opacity:0.6;"></i>
        </div>
        <h3 class="h4 fw-bold">No files yet</h3>
        <p class="text-muted mb-4 mx-auto" style="max-width:450px;">
            Create your first checkpoint by uploading files. Each checkpoint is a snapshot of your work at a specific point in time.
        </p>
        @if(auth()->id() === $project->user_id)
            <a href="{{ route('projects.checkpoints.create', $project->slug) }}" class="btn btn-ss-primary">
                <i class="bi bi-cloud-upload me-2"></i> Upload Files
            </a>
        @endif
    </div>
@endif

@endsection
