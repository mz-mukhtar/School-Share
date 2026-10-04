@extends('layouts.app')
@section('title', 'My Projects')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 style="font-size:1.4rem;font-weight:700;margin:0;">My Projects</h1>
    <a href="{{ route('projects.create') }}" class="btn btn-ss-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Project
    </a>
</div>

<div class="ss-card text-center py-5" style="color:var(--ss-text-muted);">
    <i class="bi bi-folder-plus" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
    <p style="font-weight:600;font-size:1rem;">Projects coming in Phase 3!</p>
    <p style="font-size:.875rem;">Full project management will be implemented next.</p>
</div>
@endsection
