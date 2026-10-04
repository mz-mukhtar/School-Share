@extends('layouts.app')

@section('title', 'Activity Feed')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 fw-bold mb-0">Activity Feed</h1>
        <a href="{{ route('explore') }}" class="btn btn-ss-outline btn-sm">Explore Projects</a>
    </div>
    <p class="text-muted mt-2">Recent activities from people you follow.</p>
</div>

@if($activities->isEmpty())
    <div class="ss-card text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold">No activity yet</h5>
        <p class="text-muted mb-0">Follow more people to see their activities here.</p>
    </div>
@else
    <div class="timeline ps-3 pe-3">
        @foreach($activities as $activity)
            <div class="d-flex gap-3 pb-3 {{ !$loop->last ? 'mb-3 border-bottom' : '' }}" style="border-color: var(--ss-border) !important;">
                <div class="flex-shrink-0 mt-1">
                    @if($activity->user->avatar)
                        <img src="{{ Storage::url($activity->user->avatar) }}" alt="Avatar" class="rounded-circle" width="32" height="32" style="object-fit: cover; border:1px solid var(--ss-border);">
                    @else
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:32px; height:32px; font-size: 0.8rem;">
                            {{ substr($activity->user->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="flex-grow-1 min-width-0">
                    <div class="text-white">
                        <a href="{{ route('profile.public', $activity->user->username) }}" class="fw-bold text-decoration-none text-white">{{ $activity->user->name }}</a>
                        
                        @if($activity->type === 'checkpoint_created')
                            <span class="text-muted">created a checkpoint</span>
                            @if($activity->subject)
                                <a href="{{ route('projects.checkpoints.show', [$activity->meta['project_slug'] ?? 'unknown', $activity->subject_id]) }}" class="fw-bold text-decoration-none text-info">{{ $activity->subject->title }}</a>
                                <span class="text-muted">in</span>
                                <a href="{{ route('projects.show', $activity->meta['project_slug'] ?? 'unknown') }}" class="fw-bold text-decoration-none text-primary">{{ $activity->meta['project_name'] ?? 'project' }}</a>
                            @else
                                <span class="text-muted">in a deleted project</span>
                            @endif

                        @elseif($activity->type === 'project_created')
                            <span class="text-muted">created a new project</span>
                            @if($activity->subject)
                                <a href="{{ route('projects.show', $activity->subject->slug) }}" class="fw-bold text-decoration-none text-primary">{{ $activity->subject->name }}</a>
                            @else
                                <span class="text-muted">(deleted)</span>
                            @endif

                        @elseif($activity->type === 'project_starred')
                            <span class="text-muted">starred a project</span>
                            @if($activity->subject)
                                <a href="{{ route('projects.show', $activity->subject->slug) }}" class="fw-bold text-decoration-none text-warning"><i class="bi bi-star-fill me-1"></i>{{ $activity->subject->name }}</a>
                            @else
                                <span class="text-muted">(deleted)</span>
                            @endif

                        @elseif($activity->type === 'follow')
                            <span class="text-muted">started following</span>
                            @if($activity->subject)
                                <a href="{{ route('profile.public', $activity->subject->username) }}" class="fw-bold text-decoration-none text-white">{{ $activity->subject->name }}</a>
                            @else
                                <span class="text-muted">someone</span>
                            @endif
                        @else
                            <span class="text-muted">{{ $activity->type }}</span>
                        @endif
                    </div>
                    <div class="text-muted small mt-1">
                        {{ $activity->created_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>
@endif

@endsection
