@extends('layouts.app')

@section('title', 'Diff: ' . $file->original_name . ' — ' . $project->name)

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none text-muted">{{ $project->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}" class="text-decoration-none text-muted">{{ $file->original_name }}</a></li>
            <li class="breadcrumb-item active text-white" aria-current="page">Diff</li>
        </ol>
    </nav>
</div>

<div class="ss-card mb-4">
    <div class="d-flex align-items-center gap-3">
        <h1 class="h4 fw-bold mb-0">Changes to {{ $file->original_name }}</h1>
    </div>
    <div class="text-muted small mt-2">
        Comparing <span class="fw-bold">Version {{ $from->version_number }}</span> ({{ $from->checkpoint->title }})
        with <span class="fw-bold">Version {{ $to->version_number }}</span> ({{ $to->checkpoint->title }})
    </div>
</div>

<div class="ss-card p-0 overflow-hidden">
    <style>
        .diff-wrapper {
            background-color: var(--ss-dark-1);
            color: #e2e8f0;
            font-size: 0.9rem;
            font-family: monospace;
            overflow-x: auto;
        }
        .diff { width: 100%; border-collapse: collapse; }
        .diff th, .diff td { padding: 4px 8px; border-bottom: 1px solid var(--ss-border); }
        .diff th { background-color: var(--ss-dark-3); color: var(--ss-text-muted); text-align: right; width: 40px; user-select: none; }
        .diff td.diff-empty { background-color: var(--ss-dark-2); }
        .diff td.diff-unmodified { background-color: var(--ss-dark-1); }
        .diff td.diff-inserted { background-color: rgba(40, 167, 69, 0.2); }
        .diff td.diff-deleted { background-color: rgba(220, 53, 69, 0.2); }
        .diff .diff-ins { background-color: rgba(40, 167, 69, 0.4); font-weight: bold; }
        .diff .diff-del { background-color: rgba(220, 53, 69, 0.4); font-weight: bold; text-decoration: line-through; }
    </style>
    <div class="diff-wrapper">
        {!! $htmlDiff !!}
    </div>
</div>
@endsection
