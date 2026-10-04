@extends('layouts.app')

@section('title', 'New Checkpoint — ' . $project->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none text-muted small d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to {{ $project->name }}
    </a>
    <h1 class="h3 fw-bold mb-1">Create a Checkpoint</h1>
    <p class="text-muted">A checkpoint is a snapshot of your uploaded files at this moment in time.</p>
</div>

{{-- Storage bar --}}
<div class="ss-card mb-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-muted fw-bold">Storage Used</span>
        <span class="small text-muted">{{ $user->storageUsedHuman() }} / {{ $user->maxStorageHuman() }}</span>
    </div>
    <div class="progress" style="height:6px;background:var(--ss-dark-3);">
        <div class="progress-bar {{ $user->storageUsagePercent() >= 90 ? 'bg-danger' : 'bg-primary' }}"
             style="width: {{ $user->storageUsagePercent() }}%;"></div>
    </div>
    <div class="text-muted small mt-1">{{ number_format($user->storageUsagePercent(), 1) }}% used &mdash; Max file size: 100 MB</div>
</div>

<div class="ss-card" style="max-width: 760px;">
    <form action="{{ route('projects.checkpoints.store', $project->slug) }}" method="POST" enctype="multipart/form-data" id="checkpointForm">
        @csrf
        
        <input type="hidden" name="folder_id" value="{{ request('folder') }}">

        {{-- Checkpoint title --}}
        <div class="mb-4">
            <label for="title" class="form-label fw-bold">Checkpoint Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-transparent text-white @error('title') is-invalid @enderror"
                   style="border-color:var(--ss-border);"
                   id="title" name="title" value="{{ old('title') }}"
                   placeholder="e.g., First Draft, Added chapter 3, Fixed calculations" required autofocus>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Checkpoint message --}}
        <div class="mb-4">
            <label for="message" class="form-label fw-bold">Message <span class="text-muted fw-normal">(Optional)</span></label>
            <textarea class="form-control bg-transparent text-white @error('message') is-invalid @enderror"
                      style="border-color:var(--ss-border);"
                      id="message" name="message" rows="3"
                      placeholder="Describe what changed or what you worked on…">{{ old('message') }}</textarea>
            @error('message')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- File dropzone --}}
        <div class="mb-4">
            <label class="form-label fw-bold">Files <span class="text-danger">*</span></label>
            @error('files')
                <div class="text-danger small mb-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
            @error('files.*')
                <div class="text-danger small mb-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror

            <div id="dropzone" class="text-center p-5 rounded"
                 style="border:2px dashed var(--ss-border);cursor:pointer;transition:border-color 0.2s,background 0.2s;"
                 onclick="document.getElementById('fileInput').click()">
                <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:var(--ss-text-muted);"></i>
                <p class="mb-1 mt-2 fw-bold">Drop files here or click to browse</p>
                <p class="text-muted small mb-0">Max 100 MB per file. You can upload multiple files at once.</p>
            </div>
            <input type="file" id="fileInput" name="files[]" multiple class="d-none">

            {{-- File preview list --}}
            <div id="fileList" class="mt-3 d-none">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small">Selected files:</span>
                    <span id="totalSizeLabel" class="text-muted small"></span>
                </div>
                <ul class="list-unstyled mb-0" id="fileListItems"></ul>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('projects.show', $project->slug) }}" class="btn btn-ss-outline">Cancel</a>
            <button type="submit" class="btn btn-ss-primary" id="submitBtn">
                <i class="bi bi-flag-fill me-2"></i> Save Checkpoint
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
const dropzone  = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const fileList  = document.getElementById('fileList');
const fileListItems = document.getElementById('fileListItems');
const totalSizeLabel = document.getElementById('totalSizeLabel');
const MAX_FILE_BYTES = 100 * 1024 * 1024; // 100 MB

function formatBytes(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576)    return (bytes / 1048576).toFixed(1)  + ' MB';
    if (bytes >= 1024)       return (bytes / 1024).toFixed(1)     + ' KB';
    return bytes + ' B';
}

function renderFiles(files) {
    fileListItems.innerHTML = '';
    let total = 0;
    let hasError = false;

    Array.from(files).forEach((f, i) => {
        const isTooBig = f.size > MAX_FILE_BYTES;
        if (isTooBig) hasError = true;
        total += f.size;

        const ext = f.name.split('.').pop().toLowerCase();
        const iconMap = {
            pdf: 'bi-file-pdf text-danger', doc: 'bi-file-word text-primary', docx: 'bi-file-word text-primary',
            xls: 'bi-file-excel text-success', xlsx: 'bi-file-excel text-success',
            jpg: 'bi-file-image text-success', jpeg: 'bi-file-image text-success',
            png: 'bi-file-image text-success', gif: 'bi-file-image text-success',
            txt: 'bi-file-text text-info', zip: 'bi-file-zip text-secondary',
        };
        const icon = iconMap[ext] || 'bi-file-earmark text-muted';

        const li = document.createElement('li');
        li.className = 'd-flex align-items-center gap-2 py-2';
        li.style = 'border-bottom: 1px solid var(--ss-border);';
        li.innerHTML = `
            <i class="bi ${icon} fs-5 flex-shrink-0"></i>
            <span class="flex-grow-1 text-truncate small">${f.name}</span>
            <span class="small ${isTooBig ? 'text-danger fw-bold' : 'text-muted'} flex-shrink-0">${formatBytes(f.size)}${isTooBig ? ' ⚠ Too large' : ''}</span>
        `;
        fileListItems.appendChild(li);
    });

    totalSizeLabel.textContent = `Total: ${formatBytes(total)}`;
    fileList.classList.remove('d-none');
    document.getElementById('submitBtn').disabled = hasError;
}

fileInput.addEventListener('change', () => { if (fileInput.files.length) renderFiles(fileInput.files); });

// Drag-and-drop
dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.style.borderColor = 'var(--ss-primary)'; dropzone.style.background = 'rgba(99,102,241,0.05)'; });
dropzone.addEventListener('dragleave', () => { dropzone.style.borderColor = 'var(--ss-border)'; dropzone.style.background = ''; });
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.style.borderColor = 'var(--ss-border)';
    dropzone.style.background = '';
    const dt = new DataTransfer();
    Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
    renderFiles(fileInput.files);
});
</script>
@endpush
@endsection
