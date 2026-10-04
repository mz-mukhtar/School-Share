@extends('layouts.app')

@section('title', $project->name)

@section('content')

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('projects.index') }}" class="text-decoration-none text-muted">{{ $project->owner->name }}</a>
                    </li>
                    <li class="breadcrumb-item active fw-bold text-white" aria-current="page">{{ $project->name }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                <span class="badge rounded-pill" style="background:var(--ss-dark-3);border:1px solid var(--ss-border);color:var(--ss-text-muted);">
                    <i class="bi {{ $project->visibility === 'public' ? 'bi-globe' : 'bi-lock-fill' }} me-1"></i>
                    {{ ucfirst($project->visibility) }}
                </span>
                @foreach($project->tags as $tag)
                    <span class="badge rounded-pill" style="background:rgba(6,182,212,0.1);border:1px solid rgba(6,182,212,0.2);color:#67e8f9;">
                        {{ $tag->tag }}
                    </span>
                @endforeach
            </div>
            @if($project->description)
                <p class="text-muted mt-2 mb-0" style="max-width:700px;">{{ $project->description }}</p>
            @endif
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->id() === $project->user_id)
                <a href="{{ route('projects.checkpoints.create', $project->slug) }}" class="btn btn-ss-primary d-flex align-items-center gap-2">
                    <i class="bi bi-cloud-upload"></i> New Checkpoint
                </a>
                <a href="{{ route('projects.edit', $project->slug) }}" class="btn btn-ss-outline d-flex align-items-center gap-1">
                    <i class="bi bi-gear"></i> Settings
                </a>
            @endif
            @auth
                @if(auth()->id() !== $project->user_id)
                    <form action="{{ route('projects.fork', $project->slug) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-ss-outline d-flex align-items-center gap-1" title="Fork this project to your account" onclick="return confirm('Fork this project?')">
                            <i class="bi bi-diagram-2"></i> Fork
                        </button>
                    </form>
                @endif
                @php
                    $isStarred = auth()->user()->starredProjects()->where('project_id', $project->id)->exists();
                @endphp
                <button id="star-btn" class="btn btn-ss-outline d-flex align-items-center gap-1 {{ $isStarred ? 'text-warning' : '' }}" onclick="toggleStar()">
                    <i id="star-icon" class="bi {{ $isStarred ? 'bi-star-fill' : 'bi-star' }}"></i> 
                    <span id="star-count">{{ $project->star_count }}</span>
                </button>
            @endauth
            @guest
                <a href="{{ route('login') }}" class="btn btn-ss-outline d-flex align-items-center gap-1">
                    <i class="bi bi-star"></i> <span>{{ $project->star_count }}</span>
                </a>
            @endguest
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(34,197,94,0.1);border-color:rgba(34,197,94,0.3);color:#86efac;">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.3);color:#fca5a5;">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Main content ─────────────────────────────────────────────────────── --}}
    {{-- File Browser --}}
    <div class="ss-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-folder2-open text-primary"></i>
                    Files
                </h5>
                <nav aria-label="breadcrumb" class="mt-2">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none">Root</a></li>
                        @foreach($breadcrumbs as $bc)
                            <li class="breadcrumb-item"><a href="{{ route('projects.show', ['project' => $project->slug, 'folder' => $bc->id]) }}" class="text-decoration-none">{{ $bc->name }}</a></li>
                        @endforeach
                        @if($currentFolder)
                            <li class="breadcrumb-item active text-white" aria-current="page">{{ $currentFolder->name }}</li>
                        @endif
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                @if(auth()->id() === $project->user_id)
                    <button class="btn btn-ss-outline btn-sm" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                        <i class="bi bi-folder-plus"></i> New Folder
                    </button>
                    <a href="{{ route('projects.checkpoints.create', ['project' => $project->slug, 'folder' => $currentFolderId]) }}" class="btn btn-ss-primary btn-sm">
                        <i class="bi bi-upload"></i> Upload
                    </a>
                @endif
                <a href="{{ route('download-zip', $project->slug) }}" class="btn btn-ss-outline btn-sm">
                    <i class="bi bi-file-earmark-zip"></i> Download ZIP
                </a>
            </div>
        </div>

        @if($folders->isEmpty() && $files->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-folder2-open fs-1 opacity-50"></i>
                <p class="mt-2">This folder is empty.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="color:var(--ss-text-primary);">
                    <thead>
                        <tr style="border-color:var(--ss-border);">
                            <th class="text-muted fw-normal ps-0" style="width:40px;"></th>
                            <th class="text-muted fw-normal">Name</th>
                            <th class="text-muted fw-normal text-end">Size</th>
                            <th class="text-muted fw-normal text-end pe-0">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($currentFolder)
                            <tr style="border-color:var(--ss-border);">
                                <td class="ps-0 align-middle text-center"><i class="bi bi-arrow-90deg-up"></i></td>
                                <td class="align-middle" colspan="3">
                                    <a href="{{ route('projects.show', ['project' => $project->slug, 'folder' => $currentFolder->parent_id]) }}" class="text-decoration-none text-white fw-medium">..</a>
                                </td>
                            </tr>
                        @endif

                        @foreach($folders as $folder)
                            <tr style="border-color:var(--ss-border);">
                                <td class="ps-0 align-middle text-center">
                                    <i class="bi bi-folder-fill text-primary fs-5"></i>
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('projects.show', ['project' => $project->slug, 'folder' => $folder->id]) }}"
                                       class="text-decoration-none text-white fw-medium">
                                        {{ $folder->name }}
                                    </a>
                                </td>
                                <td class="align-middle text-end text-muted small">-</td>
                                <td class="align-middle text-end pe-0">
                                    @if(auth()->id() === $project->user_id)
                                        <form method="POST" action="{{ route('folders.destroy', $folder->id) }}" class="d-inline" onsubmit="return confirm('Delete this folder and ALL its contents?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm p-0 text-danger" title="Delete Folder">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        @foreach($files as $file)
                            <tr style="border-color:var(--ss-border);">
                                <td class="ps-0 align-middle text-center">
                                    <i class="bi {{ $file->iconClass() }} fs-5"></i>
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}"
                                       class="text-decoration-none text-white fw-medium">
                                        {{ $file->original_name }}
                                    </a>
                                </td>
                                <td class="align-middle text-end text-muted small">{{ $file->sizeHuman() }}</td>
                                <td class="align-middle text-end pe-0">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @if($file->isPreviewable())
                                            <a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}"
                                               class="btn btn-link btn-sm p-0 text-muted" title="Preview">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}"
                                           class="btn btn-link btn-sm p-0 text-muted" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @if(auth()->id() === $project->user_id)
                                            <button type="button" 
                                                    class="btn btn-link btn-sm p-0 text-muted edit-file-btn" 
                                                    title="Rename / Move"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editFileModal"
                                                    data-file-id="{{ $file->id }}"
                                                    data-file-name="{{ $file->original_name }}"
                                                    data-folder-id="{{ $file->folder_id ?? '' }}"
                                                    data-update-url="{{ route('projects.files.update', [$project->slug, $file->id]) }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="{{ route('projects.files.destroy', [$project->slug, $file->id]) }}"
                                                  onsubmit="return confirm('Delete this file?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if(isset($readmeHtml))
        {{-- README --}}
        <div class="ss-card mb-4 p-0 overflow-hidden" style="border-color:var(--ss-border);">
            <div class="p-3 border-bottom d-flex align-items-center gap-2" style="background:var(--ss-dark-2); border-color:var(--ss-border) !important;">
                <i class="bi bi-book text-muted"></i>
                <h6 class="mb-0 fw-bold text-muted">README.md</h6>
            </div>
            <div class="p-4" style="background:var(--ss-dark-1); color:#e2e8f0; font-size: 0.95rem;">
                {!! $readmeHtml !!}
            </div>
        </div>
    @endif

    {{-- Checkpoint history preview --}}
    <div class="ss-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Checkpoint History
            </h5>
            <a href="{{ route('projects.checkpoints.index', $project->slug) }}" class="btn btn-ss-outline btn-sm">View all</a>
        </div>

        <div class="timeline">
            @foreach($checkpoints as $cp)
                <div class="d-flex gap-3 pb-3 {{ !$loop->last ? 'mb-2 border-bottom' : '' }}" style="border-color: var(--ss-border) !important;">
                    <div class="flex-shrink-0 mt-1">
                        <div style="width:32px;height:32px;background:var(--ss-dark-3);border:1px solid var(--ss-border);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-flag-fill text-primary" style="font-size:0.7rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <a href="{{ route('projects.checkpoints.show', [$project->slug, $cp->id]) }}"
                                   class="fw-bold text-white text-decoration-none">
                                    {{ $cp->title }}
                                </a>
                                @if($loop->first)
                                    <span class="badge ms-1" style="background:rgba(6,182,212,0.15);color:#67e8f9;font-size:0.65rem;">Latest</span>
                                @endif
                                <div class="text-muted small mt-1">
                                    {{ $cp->files_count }} {{ Str::plural('file', $cp->files_count) }}
                                    &middot; {{ $cp->totalSizeHuman() }}
                                    &middot; {{ $cp->created_at->diffForHumans() }}
                                </div>
                            </div>
                            @if(auth()->id() === $project->user_id)
                                <form method="POST" action="{{ route('projects.checkpoints.destroy', [$project->slug, $cp->id]) }}"
                                      onsubmit="return confirm('Delete this checkpoint and all its files?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-muted ms-2" title="Delete checkpoint">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                        @if($cp->message)
                            <p class="text-muted small mt-1 mb-0 text-truncate" style="max-width:500px;">{{ $cp->message }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@if(auth()->id() === $project->user_id)
    {{-- New Folder Modal --}}
    <div class="modal fade" id="newFolderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background:var(--ss-dark-2); border-color:var(--ss-border);">
                <form action="{{ route('folders.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    @if($currentFolderId)
                        <input type="hidden" name="parent_id" value="{{ $currentFolderId }}">
                    @endif
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title">New Folder</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Folder Name</label>
                            <input type="text" name="name" class="form-control ss-input" required pattern="[\w\-\.]+" title="Only letters, numbers, dashes, underscores, and dots">
                            <div class="form-text text-muted">No spaces or special characters allowed.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-ss-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ss-primary">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Edit File Modal -->
@if(auth()->id() === $project->user_id)
<div class="modal fade" id="editFileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--ss-dark-2); border-color:var(--ss-border);">
            <form id="editFileForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title">Rename / Move File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">File Name</label>
                        <input type="text" id="editFileName" name="original_name" class="form-control ss-input" required pattern="[\w\-\.]+" title="Only letters, numbers, dashes, underscores, and dots">
                        <div class="form-text text-muted">No spaces or special characters allowed.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location (Folder)</label>
                        <select name="folder_id" id="editFileFolder" class="form-select ss-input">
                            <option value="">/ (Root)</option>
                            @foreach($allFolders ?? [] as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-ss-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-ss-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editFileBtns = document.querySelectorAll('.edit-file-btn');
        const editFileForm = document.getElementById('editFileForm');
        const editFileName = document.getElementById('editFileName');
        const editFileFolder = document.getElementById('editFileFolder');

        editFileBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                editFileForm.action = this.dataset.updateUrl;
                editFileName.value = this.dataset.fileName;
                editFileFolder.value = this.dataset.folderId || '';
            });
        });
    });

    function toggleStar() {
        const btn = document.getElementById('star-btn');
        const icon = document.getElementById('star-icon');
        const countSpan = document.getElementById('star-count');
        
        btn.disabled = true;

        fetch('{{ route('projects.star', $project->slug) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            countSpan.textContent = data.star_count;
            if (data.status === 'starred') {
                btn.classList.add('text-warning');
                icon.classList.remove('bi-star');
                icon.classList.add('bi-star-fill');
            } else {
                btn.classList.remove('text-warning');
                icon.classList.remove('bi-star-fill');
                icon.classList.add('bi-star');
            }
        })
        .catch(error => console.error('Error:', error))
        .finally(() => {
            btn.disabled = false;
        });
    }
</script>
@endpush
