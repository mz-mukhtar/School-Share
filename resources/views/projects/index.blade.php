@extends('layouts.app')

@section('title', 'My Projects')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 fw-bold">My Projects</h1>
    @if(auth()->user()->canCreateProject())
        <a href="{{ route('projects.create') }}" class="btn btn-ss-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i> New Project
        </a>
    @else
        <button class="btn btn-secondary d-flex align-items-center gap-2" disabled title="Project limit reached">
            <i class="bi bi-plus-lg"></i> New Project
        </button>
    @endif
</div>

@if($projects->isEmpty())
    <div class="ss-card text-center py-5">
        <div class="mb-3">
            <i class="bi bi-folder2-open" style="font-size: 3rem; color: var(--ss-text-muted);"></i>
        </div>
        <h3 class="h5 fw-bold">No projects yet</h3>
        <p class="text-muted mb-4">Create your first project to start uploading and versioning your files.</p>
        @if(auth()->user()->canCreateProject())
            <a href="{{ route('projects.create') }}" class="btn btn-ss-primary">
                Create Project
            </a>
        @endif
    </div>
@else
    <div class="row g-4">
        @foreach($projects as $project)
            <div class="col-md-6 col-lg-4">
                <div class="ss-card h-100 d-flex flex-column hover-lift">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none fw-bold fs-5 text-white d-flex align-items-center gap-2 text-truncate" style="max-width: 80%;">
                            <i class="bi {{ $project->visibility === 'public' ? 'bi-globe' : 'bi-lock-fill' }} fs-6 text-muted"></i>
                            <span class="text-truncate">{{ $project->name }}</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow" style="background:var(--ss-dark-3);border-color:var(--ss-border);">
                                <li><a class="dropdown-item text-white" href="{{ route('projects.show', $project->slug) }}"><i class="bi bi-folder2-open me-2 text-muted"></i>Open</a></li>
                                <li><a class="dropdown-item text-white" href="{{ route('projects.edit', $project->slug) }}"><i class="bi bi-gear me-2 text-muted"></i>Settings</a></li>
                                <li><hr class="dropdown-divider" style="border-color:var(--ss-border);"></li>
                                <li>
                                    <form method="POST" action="{{ route('projects.destroy', $project->slug) }}" onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $project->description ?: 'No description provided.' }}
                    </p>
                    
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top" style="border-color: var(--ss-border) !important;">
                        <div class="d-flex gap-3 text-muted small">
                            <span title="Last updated"><i class="bi bi-clock me-1"></i>{{ $project->updated_at->diffForHumans() }}</span>
                            @if($project->tags->isNotEmpty())
                                <span><i class="bi bi-tags me-1"></i>{{ $project->tags->pluck('tag')->implode(', ') }}</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-star me-1"></i>{{ $project->star_count }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
@endif

@if(isset($sharedProjects) && $sharedProjects->isNotEmpty())
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <h2 class="h4 mb-0 fw-bold">Shared with Me</h2>
    </div>
    <div class="row g-4 mb-5">
        @foreach($sharedProjects as $project)
            <div class="col-md-6 col-lg-4">
                <div class="ss-card h-100 d-flex flex-column hover-lift">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none fw-bold fs-5 text-white d-flex align-items-center gap-2 text-truncate" style="max-width: 80%;">
                            <i class="bi bi-people-fill text-ss-primary fs-6"></i>
                            <span class="text-truncate">{{ $project->name }}</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow" style="background:var(--ss-dark-3);border-color:var(--ss-border);">
                                <li><a class="dropdown-item text-white" href="{{ route('projects.show', $project->slug) }}"><i class="bi bi-folder2-open me-2 text-muted"></i>Open</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $project->description ?: 'No description provided.' }}
                    </p>
                    
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top" style="border-color: var(--ss-border) !important;">
                        <div class="d-flex gap-3 text-muted small">
                            <span><i class="bi bi-person-badge me-1"></i>Owner: {{ $project->owner->username }}</span>
                            <span title="Your Role"><i class="bi bi-shield-check me-1"></i>{{ ucfirst($project->pivot->role) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@push('styles')
<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        border-color: rgba(255,255,255,0.15);
    }
    .dropdown-item:hover {
        background-color: rgba(255,255,255,0.05);
    }
</style>
@endpush
@endsection
