@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php $user = auth()->user(); @endphp

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="mb-0" style="font-size:1.5rem;font-weight:700;">
            👋 Hello, {{ explode(' ', $user->name)[0] }}
        </h1>
        <p style="color:var(--ss-text-muted);font-size:.875rem;margin-top:.25rem;">
            Here's what's happening with your projects today.
        </p>
    </div>
    <a href="{{ route('projects.create') }}" class="btn btn-ss-primary d-flex align-items-center gap-1">
        <i class="bi bi-plus-lg"></i> New Project
    </a>
</div>

{{-- Stats row --}}
<div class="row g-3 mb-4">
    {{-- Projects stat --}}
    <div class="col-6 col-md-3">
        <div class="ss-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:rgba(37,99,235,.15);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-folder2-open" style="color:var(--ss-primary);font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;line-height:1;">
                        {{ $projectCount ?? 0 }}
                    </div>
                    <div style="font-size:.75rem;color:var(--ss-text-muted);">Projects</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Checkpoints stat --}}
    <div class="col-6 col-md-3">
        <div class="ss-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:rgba(6,182,212,.15);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-clock-history" style="color:var(--ss-accent, #06b6d4);font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;line-height:1;">
                        {{ $checkpointCount ?? 0 }}
                    </div>
                    <div style="font-size:.75rem;color:var(--ss-text-muted);">Checkpoints</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Storage stat --}}
    <div class="col-6 col-md-3">
        <div class="ss-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:rgba(16,185,129,.15);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-hdd" style="color:#10b981;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;line-height:1;">
                        {{ $user->storageUsedHuman() }}
                    </div>
                    <div style="font-size:.75rem;color:var(--ss-text-muted);">Used / {{ $user->maxStorageHuman() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Plan stat --}}
    <div class="col-6 col-md-3">
        <div class="ss-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:rgba(245,158,11,.15);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-award" style="color:#f59e0b;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.1rem;font-weight:700;line-height:1.2;text-transform:capitalize;">
                        {{ $user->plan }}
                    </div>
                    <div style="font-size:.75rem;color:var(--ss-text-muted);">
                        {{ $user->maxProjects() }} projects max
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Projects --}}
<div class="ss-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 style="font-size:1rem;font-weight:600;margin:0;">Recent Projects</h2>
        <a href="{{ route('projects.index') }}" class="ss-link" style="font-size:.825rem;">
            View all <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    @php $recentProjects = $recentProjects ?? collect(); @endphp

    @if ($recentProjects->isEmpty())
        <div class="text-center py-5" style="color:var(--ss-text-muted);">
            <i class="bi bi-folder-plus" style="font-size:2.5rem;opacity:.4;display:block;margin-bottom:1rem;"></i>
            <p class="mb-1" style="font-weight:600;">No projects yet</p>
            <p style="font-size:.875rem;">Create your first project to start tracking your school work.</p>
            <a href="{{ route('projects.create') }}" class="btn btn-ss-primary btn-sm mt-1">
                <i class="bi bi-plus-lg me-1"></i> Create Project
            </a>
        </div>
    @else
        <div class="list-group list-group-flush" style="gap:.5rem;">
            @foreach ($recentProjects as $project)
            <a href="{{ route('projects.show', $project) }}"
               class="d-flex align-items-center gap-3 p-2 rounded text-decoration-none"
               style="transition:background .15s;" onmouseover="this.style.background='var(--ss-dark-3)'" onmouseout="this.style.background='transparent'">
                <div style="width:38px;height:38px;border-radius:8px;background:var(--ss-dark-3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-folder2" style="color:var(--ss-primary);"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div style="font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--ss-text);">
                        {{ $project->name }}
                    </div>
                    <div style="font-size:.75rem;color:var(--ss-text-muted);">
                        {{ $project->checkpoints_count ?? 0 }} checkpoint(s) &middot; Updated {{ $project->updated_at->diffForHumans() }}
                    </div>
                </div>
                <i class="bi bi-chevron-right" style="color:var(--ss-text-muted);flex-shrink:0;"></i>
            </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
