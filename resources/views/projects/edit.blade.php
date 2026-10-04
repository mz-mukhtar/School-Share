@extends('layouts.app')

@section('title', 'Project Settings')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none text-muted small mb-2 d-inline-block">
            <i class="bi bi-arrow-left me-1"></i> Back to {{ $project->name }}
        </a>
        <h1 class="h3 fw-bold mb-0">Project Settings</h1>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="ss-card mb-4">
            <h4 class="h5 fw-bold mb-3 border-bottom pb-2" style="border-color: var(--ss-border) !important;">General Information</h4>
            <form action="{{ route('projects.update', $project->slug) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Project Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-transparent text-white @error('name') is-invalid @enderror" style="border-color: var(--ss-border);" id="name" name="name" value="{{ old('name', $project->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold">Description <span class="text-muted fw-normal">(Optional)</span></label>
                    <textarea class="form-control bg-transparent text-white @error('description') is-invalid @enderror" style="border-color: var(--ss-border);" id="description" name="description" rows="3">{{ old('description', $project->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="subject_tag" class="form-label fw-bold">Subject Tag <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="text" list="subject_tags" class="form-control bg-transparent text-white @error('subject_tag') is-invalid @enderror" style="border-color: var(--ss-border);" id="subject_tag" name="subject_tag" value="{{ old('subject_tag', $project->subject_tag) }}" placeholder="e.g., Science">
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

                <h4 class="h5 fw-bold mb-3 border-bottom pb-2 pt-3" style="border-color: var(--ss-border) !important;">Visibility</h4>
                
                <div class="form-check mb-3 p-3 rounded" style="border: 1px solid var(--ss-border); cursor: pointer;" onclick="document.getElementById('visibility_private').click()">
                    <input class="form-check-input ms-1 me-3 mt-2" type="radio" name="visibility" id="visibility_private" value="private" {{ old('visibility', $project->visibility) == 'private' ? 'checked' : '' }}>
                    <label class="form-check-label w-100" for="visibility_private" style="cursor: pointer;">
                        <div class="fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-lock-fill text-muted"></i> Private
                        </div>
                        <div class="text-muted small mt-1">You choose who can see and commit to this project.</div>
                    </label>
                </div>

                <div class="form-check p-3 rounded mb-4" style="border: 1px solid var(--ss-border); cursor: pointer;" onclick="document.getElementById('visibility_public').click()">
                    <input class="form-check-input ms-1 me-3 mt-2" type="radio" name="visibility" id="visibility_public" value="public" {{ old('visibility', $project->visibility) == 'public' ? 'checked' : '' }}>
                    <label class="form-check-label w-100" for="visibility_public" style="cursor: pointer;">
                        <div class="fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-globe text-primary"></i> Public
                        </div>
                        <div class="text-muted small mt-1">Anyone on the internet can see this project. You choose who can commit.</div>
                    </label>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-ss-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="ss-card border-danger mb-4" style="background: rgba(239, 68, 68, 0.05);">
            <h4 class="h5 fw-bold text-danger mb-3">Danger Zone</h4>
            <p class="text-muted small mb-3">Once you delete a project, there is no going back. Please be certain.</p>
            <form method="POST" action="{{ route('projects.destroy', $project->slug) }}" onsubmit="return confirm('Are you sure you want to delete this project and all of its files? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100">Delete this project</button>
            </form>
        </div>
    </div>
</div>
@endsection
