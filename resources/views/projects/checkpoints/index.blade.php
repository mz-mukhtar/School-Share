@extends('layouts.app')

@section('title', 'Checkpoints — ' . $project->name)

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none text-muted small d-inline-block mb-1">
            <i class="bi bi-arrow-left me-1"></i> Back to {{ $project->name }}
        </a>
        <h1 class="h3 fw-bold mb-0">Checkpoints</h1>
    </div>
    @if(auth()->id() === $project->user_id)
        <a href="{{ route('projects.checkpoints.create', $project->slug) }}" class="btn btn-ss-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i> New Checkpoint
        </a>
    @endif
</div>

@if($checkpoints->isEmpty())
    <div class="ss-card text-center py-5">
        <i class="bi bi-clock-history" style="font-size:3rem;color:var(--ss-text-muted);"></i>
        <h3 class="h5 fw-bold mt-3">No checkpoints yet</h3>
        <p class="text-muted mb-4">Each time you upload files, a checkpoint is saved here.</p>
        @if(auth()->id() === $project->user_id)
            <a href="{{ route('projects.checkpoints.create', $project->slug) }}" class="btn btn-ss-primary">
                <i class="bi bi-cloud-upload me-2"></i> Upload Files
            </a>
        @endif
    </div>
@else
    <div class="ss-card">
        @foreach($checkpoints as $cp)
            <div class="d-flex gap-3 {{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}" style="border-color:var(--ss-border) !important;">
                {{-- Timeline dot --}}
                <div class="flex-shrink-0 mt-1 d-flex flex-column align-items-center">
                    <div style="width:36px;height:36px;background:var(--ss-dark-3);border:1px solid {{ $loop->first ? 'var(--ss-primary)' : 'var(--ss-border)' }};border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-flag-fill {{ $loop->first ? 'text-primary' : 'text-muted' }}" style="font-size:0.75rem;"></i>
                    </div>
                </div>

                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                        <div>
                            <a href="{{ route('projects.checkpoints.show', [$project->slug, $cp->id]) }}"
                               class="fw-bold text-white text-decoration-none fs-6">
                                {{ $cp->title }}
                            </a>
                            @if($loop->first)
                                <span class="badge ms-1" style="background:rgba(6,182,212,0.15);color:#67e8f9;font-size:0.65rem;">Latest</span>
                            @endif
                            <div class="text-muted small mt-1">
                                <i class="bi bi-person me-1"></i>{{ $cp->author->name }}
                                &middot;
                                <i class="bi bi-clock me-1"></i>{{ $cp->created_at->diffForHumans() }}
                                &middot;
                                <i class="bi bi-file-earmark me-1"></i>{{ $cp->files_count }} {{ Str::plural('file', $cp->files_count) }}
                                &middot;
                                {{ $cp->totalSizeHuman() }}
                            </div>
                            @if($cp->message)
                                <p class="text-muted small mt-1 mb-0">{{ $cp->message }}</p>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('projects.checkpoints.show', [$project->slug, $cp->id]) }}"
                               class="btn btn-ss-outline btn-sm">
                                <i class="bi bi-folder2-open me-1"></i> Browse
                            </a>
                            @if(auth()->id() === $project->user_id)
                                <form method="POST" action="{{ route('projects.checkpoints.destroy', [$project->slug, $cp->id]) }}"
                                      onsubmit="return confirm('Delete checkpoint \'{{ addslashes($cp->title) }}\' and all its files? This cannot be undone.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $checkpoints->links() }}
    </div>
@endif
@endsection
