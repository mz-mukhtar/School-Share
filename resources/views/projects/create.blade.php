@extends('layouts.app')

@section('title', 'New Project')

@section('content')
<div class="mb-4">
    <a href="{{ route('projects.index') }}" class="text-decoration-none text-muted small mb-2 d-inline-block">
        <i class="bi bi-arrow-left me-1"></i> Back to My Projects
    </a>
    <h1 class="h3 fw-bold">Create a New Project</h1>
    <p class="text-muted">A project contains all your files, checkpoints, and history for a specific assignment or topic.</p>
</div>

<div class="ss-card" style="max-width: 700px;">
    <form action="{{ route('projects.store') }}" method="POST">
        @csrf
        
        <div class="mb-4">
            <label for="name" class="form-label fw-bold">Project Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-transparent text-white @error('name') is-invalid @enderror" style="border-color: var(--ss-border);" id="name" name="name" value="{{ old('name') }}" placeholder="e.g., Biology Final Essay" required autofocus>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="form-text text-muted">Keep it short and descriptive.</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="description" class="form-label fw-bold">Description <span class="text-muted fw-normal">(Optional)</span></label>
            <textarea class="form-control bg-transparent text-white @error('description') is-invalid @enderror" style="border-color: var(--ss-border);" id="description" name="description" rows="3" placeholder="What is this project about?">{{ old('description') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="subject_tag" class="form-label fw-bold">Subject Tag <span class="text-muted fw-normal">(Optional)</span></label>
            <input type="text" list="subject_tags" class="form-control bg-transparent text-white @error('subject_tag') is-invalid @enderror" style="border-color: var(--ss-border);" id="subject_tag" name="subject_tag" value="{{ old('subject_tag') }}" placeholder="e.g., Science">
            <datalist id="subject_tags">
                <option value="Mathematics">
                <option value="Science">
                <option value="History">
                <option value="Language Arts">
                <option value="Computer Science">
                <option value="Art & Design">
            </datalist>
            @error('subject_tag')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="form-text text-muted">Select from the list or type your own custom tag.</div>
            @enderror
        </div>

        <div class="mb-4 pb-4 border-bottom" style="border-color: var(--ss-border) !important;">
            <label class="form-label fw-bold d-block mb-3">Visibility</label>
            
            <div class="form-check mb-3 p-3 rounded" style="border: 1px solid var(--ss-border); cursor: pointer;" onclick="document.getElementById('visibility_private').click()">
                <input class="form-check-input ms-1 me-3 mt-2" type="radio" name="visibility" id="visibility_private" value="private" {{ old('visibility', 'private') == 'private' ? 'checked' : '' }}>
                <label class="form-check-label w-100" for="visibility_private" style="cursor: pointer;">
                    <div class="fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-lock-fill text-muted"></i> Private
                    </div>
                    <div class="text-muted small mt-1">You choose who can see and commit to this project.</div>
                </label>
            </div>

            <div class="form-check p-3 rounded" style="border: 1px solid var(--ss-border); cursor: pointer;" onclick="document.getElementById('visibility_public').click()">
                <input class="form-check-input ms-1 me-3 mt-2" type="radio" name="visibility" id="visibility_public" value="public" {{ old('visibility') == 'public' ? 'checked' : '' }}>
                <label class="form-check-label w-100" for="visibility_public" style="cursor: pointer;">
                    <div class="fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-globe text-primary"></i> Public
                    </div>
                    <div class="text-muted small mt-1">Anyone on the internet can see this project. You choose who can commit.</div>
                </label>
            </div>
            @error('visibility')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('projects.index') }}" class="btn btn-ss-outline">Cancel</a>
            <button type="submit" class="btn btn-ss-primary">Create Project</button>
        </div>
    </form>
</div>
@endsection
