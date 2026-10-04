@extends('layouts.app')

@section('title', 'Explore Public Projects')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Explore</h1>
        <p class="text-muted mb-0">Discover public projects shared by other students.</p>
    </div>
    
    <form class="d-flex" style="max-width: 300px; width: 100%;" method="GET" action="{{ route('explore') }}">
        <div class="input-group">
            <span class="input-group-text bg-transparent" style="border-color: var(--ss-border); color: var(--ss-text-muted);">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" name="q" class="form-control bg-transparent text-white" 
                   style="border-color: var(--ss-border);" 
                   placeholder="Search public projects..." 
                   value="{{ request('q') }}">
        </div>
    </form>
</div>

@if($projects->isEmpty())
    <div class="ss-card text-center py-5">
        <div class="mb-3">
            <i class="bi bi-search" style="font-size: 3rem; color: var(--ss-text-muted);"></i>
        </div>
        <h3 class="h5 fw-bold">No projects found</h3>
        @if(request('q'))
            <p class="text-muted mb-0">We couldn't find any public projects matching "{{ request('q') }}".</p>
            <a href="{{ route('explore') }}" class="btn btn-ss-outline mt-3">Clear Search</a>
        @else
            <p class="text-muted mb-0">There are no public projects available yet.</p>
        @endif
    </div>
@else
    <div class="row g-4">
        @foreach($projects as $project)
            <div class="col-md-6 col-lg-4">
                <div class="ss-card h-100 d-flex flex-column hover-lift">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="ss-avatar" style="width: 24px; height: 24px; font-size: 0.65rem;" title="{{ $project->owner->name }}">
                            {{ strtoupper(substr($project->owner->name, 0, 1)) }}
                        </div>
                        <span class="text-muted small text-truncate">{{ $project->owner->name }}</span>
                    </div>
                    
                    <a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none fw-bold fs-5 text-white mb-2 d-inline-block text-truncate">
                        {{ $project->name }}
                    </a>
                    
                    <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $project->description ?: 'No description provided.' }}
                    </p>
                    
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top" style="border-color: var(--ss-border) !important;">
                        <div class="d-flex gap-3 text-muted small">
                            <span title="Last updated"><i class="bi bi-clock me-1"></i>{{ $project->updated_at->diffForHumans() }}</span>
                            @if($project->subject_tag)
                                <span><i class="bi bi-tag me-1"></i>{{ $project->subject_tag }}</span>
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
</style>
@endpush
@endsection
