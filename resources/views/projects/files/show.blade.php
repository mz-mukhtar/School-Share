@extends('layouts.app')

@section('title', $file->original_name . ' — ' . $project->name)

@section('content')
@php($mimeType = $version->mime_type ?? $file->mime_type)
{{-- Breadcrumb --}}
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none text-muted">{{ $project->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.checkpoints.show', [$project->slug, $version->checkpoint_id]) }}" class="text-decoration-none text-muted">{{ $version->checkpoint->title }}</a></li>
            <li class="breadcrumb-item active text-white" aria-current="page">{{ $file->original_name }}</li>
        </ol>
    </nav>
</div>

{{-- File header --}}
<div class="ss-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;background:var(--ss-dark-3);border:1px solid var(--ss-border);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i class="bi {{ $file->iconClass($mimeType) }} fs-3"></i>
            </div>
            <div>
                <h1 class="h4 fw-bold mb-0">{{ $file->original_name }}</h1>
                <div class="text-muted small mt-1">
                    {{ strtoupper($file->extension()) }} &middot; {{ $file->sizeHuman() }}
                    @if($mimeType)
                        &middot; {{ $mimeType }}
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}"
               class="btn btn-ss-primary d-flex align-items-center gap-2">
                <i class="bi bi-download"></i> Download
            </a>
            @if($project->canEdit(auth()->user()))
                <form method="POST" action="{{ route('projects.files.destroy', [$project->slug, $file->id]) }}"
                      onsubmit="return confirm('Delete this file?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger d-flex align-items-center gap-2">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Preview area --}}
<div class="ss-card">
    @if($file->isImage($mimeType))
        {{-- Image preview --}}
        <div class="text-center" style="cursor: zoom-in;" data-bs-toggle="modal" data-bs-target="#imageModal">
            <img src="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}"
                 alt="{{ $file->original_name }}"
                 class="img-fluid rounded"
                 style="max-height:80vh;">
        </div>

        {{-- Image Modal --}}
        <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen modal-dialog-centered">
                <div class="modal-content bg-transparent border-0">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center d-flex align-items-center justify-content-center p-0" data-bs-dismiss="modal" style="cursor: zoom-out;">
                        <img src="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}"
                             class="img-fluid" style="max-height:95vh;">
                    </div>
                </div>
            </div>
        </div>

    @elseif($file->isPdf($mimeType))
        {{-- PDF preview --}}
        <div id="pdf-viewer" class="bg-light p-3 rounded text-center overflow-auto" style="height: 750px;">
            <div id="pdf-loading" class="text-dark py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2">Loading PDF...</div>
            </div>
            <canvas id="pdf-canvas" class="shadow-sm mx-auto d-none" style="max-width: 100%;"></canvas>
            <div id="pdf-controls" class="mt-3 d-none gap-2 justify-content-center">
                <button class="btn btn-outline-dark btn-sm" id="pdf-prev"><i class="bi bi-chevron-left"></i></button>
                <span class="text-dark align-self-center mx-2">Page <span id="pdf-page-num">1</span> of <span id="pdf-page-count">1</span></span>
                <button class="btn btn-outline-dark btn-sm" id="pdf-next"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>

        @push('scripts')
        <script type="module">
            const url = {{ Illuminate\Support\Js::from(route('projects.files.download', [$project->slug, $file->id]) . '?version_id=' . $version->id) }};
            let pdfDoc = null,
                pageNum = 1,
                pageRendering = false,
                pageNumPending = null,
                canvas = document.getElementById('pdf-canvas'),
                ctx = canvas.getContext('2d');

            function showPdfError() {
                pageRendering = false;
                pageNumPending = null;
                canvas.classList.add('d-none');
                document.getElementById('pdf-controls').classList.add('d-none');
                document.getElementById('pdf-controls').classList.remove('d-flex');
                const loading = document.getElementById('pdf-loading');
                loading.classList.remove('d-none');
                loading.classList.add('text-danger');
                loading.textContent = 'Failed to load PDF. Please download the file to view it.';
            }

            async function renderPage(num) {
                pageRendering = true;
                try {
                    const page = await pdfDoc.getPage(num);
                    const viewport = page.getViewport({scale: 1.5});
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    const renderContext = {
                        canvasContext: ctx,
                        viewport: viewport
                    };
                    await page.render(renderContext).promise;

                    document.getElementById('pdf-page-num').textContent = num;
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        const pendingPage = pageNumPending;
                        pageNumPending = null;
                        renderPage(pendingPage);
                    }
                } catch {
                    showPdfError();
                }
            }

            function queueRenderPage(num) {
                if (pageRendering) {
                    pageNumPending = num;
                } else {
                    renderPage(num);
                }
            }

            function onPrevPage() {
                if (pageNum <= 1) return;
                pageNum--;
                queueRenderPage(pageNum);
            }

            function onNextPage() {
                if (pageNum >= pdfDoc.numPages) return;
                pageNum++;
                queueRenderPage(pageNum);
            }

            document.getElementById('pdf-prev').addEventListener('click', onPrevPage);
            document.getElementById('pdf-next').addEventListener('click', onNextPage);

            try {
                const pdfjsLib = await import('https://cdn.jsdelivr.net/npm/pdfjs-dist@6.4.299/build/pdf.min.mjs');
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.4.299/build/pdf.worker.min.mjs';
                pdfDoc = await pdfjsLib.getDocument({
                    url,
                    isEvalSupported: false
                }).promise;
                document.getElementById('pdf-loading').classList.add('d-none');
                canvas.classList.remove('d-none');
                document.getElementById('pdf-controls').classList.remove('d-none');
                document.getElementById('pdf-controls').classList.add('d-flex');
                document.getElementById('pdf-page-count').textContent = pdfDoc.numPages;
                
                renderPage(pageNum);
            } catch {
                showPdfError();
            }
        </script>
        @endpush

    @elseif($file->isVideo($mimeType))
        <div class="text-center bg-black rounded p-3">
            <video controls class="w-100" style="max-height: 70vh;">
                <source src="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}" type="{{ $mimeType }}">
                Your browser does not support the video tag.
            </video>
        </div>
        
    @elseif($file->isAudio($mimeType))
        <div class="text-center bg-dark rounded p-5">
            <i class="bi bi-music-note-beamed text-light d-block mb-4" style="font-size: 4rem;"></i>
            <audio controls class="w-100">
                <source src="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}" type="{{ $mimeType }}">
                Your browser does not support the audio element.
            </audio>
        </div>

    @elseif($file->isOffice($mimeType))
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i> This file is being rendered via Google Docs Viewer. Note that the project must be public for Google to access it.
        </div>
        <iframe src="https://docs.google.com/viewer?url={{ urlencode(route('projects.files.download', [$project->slug, $file->id])) }}%3Fversion_id%3D{{ $version->id }}&embedded=true"
                width="100%" height="750"
                style="border:none;border-radius:8px;background:#fff;">
        </iframe>

    @elseif($file->isText($mimeType) && $content !== null)
        {{-- Text / Code preview & editor --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">{{ $file->original_name }}</span>
            <div class="d-flex gap-2">
                @if($project->canEdit(auth()->user()))
                    <button type="button" class="btn btn-sm btn-outline-light" id="toggleEditBtn">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-ss-primary d-none" id="saveFileBtn">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                @endif
                <span class="badge" style="background:var(--ss-dark-3);border:1px solid var(--ss-border);color:var(--ss-text-muted);">
                    {{ strtoupper($file->extension()) }}
                </span>
            </div>
        </div>

        <div id="fileViewer" class="p-3 rounded" style="background:var(--ss-dark-1);color:#e2e8f0;font-size:0.85rem;overflow:auto;max-height:75vh;white-space:pre-wrap;word-break:break-all;border:1px solid var(--ss-border);"><code>{{ $content }}</code></div>
        
        <div id="fileEditorContainer" class="d-none" style="border:1px solid var(--ss-border); border-radius:8px; overflow:hidden;"></div>

        @push('scripts')
        <script type="module">
            import {EditorView, basicSetup} from "https://esm.sh/codemirror@6.0.1";
            import {EditorState} from "https://esm.sh/@codemirror/state@6.0.1";

            const toggleEditBtn = document.getElementById('toggleEditBtn');
            const saveFileBtn = document.getElementById('saveFileBtn');
            const fileViewer = document.getElementById('fileViewer');
            const fileEditorContainer = document.getElementById('fileEditorContainer');
            
            let editorView = null;
            let originalContent = {!! json_encode($content) !!};

            if(toggleEditBtn) {
                toggleEditBtn.addEventListener('click', () => {
                    fileViewer.classList.add('d-none');
                    fileEditorContainer.classList.remove('d-none');
                    toggleEditBtn.classList.add('d-none');
                    saveFileBtn.classList.remove('d-none');

                    if (!editorView) {
                        editorView = new EditorView({
                            state: EditorState.create({
                                doc: originalContent,
                                extensions: [basicSetup]
                            }),
                            parent: fileEditorContainer
                        });
                    }
                });
            }

            if(saveFileBtn) {
                saveFileBtn.addEventListener('click', () => {
                    if (!editorView) return;
                    const newContent = editorView.state.doc.toString();
                    
                    saveFileBtn.disabled = true;
                    saveFileBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

                    fetch(`{{ route('projects.files.update', [$project->slug, $file->id]) }}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            content: newContent
                        })
                    }).then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            window.location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                            saveFileBtn.disabled = false;
                            saveFileBtn.innerHTML = '<i class="bi bi-save me-1"></i> Save Changes';
                        }
                    }).catch(err => {
                        alert('An error occurred.');
                        saveFileBtn.disabled = false;
                        saveFileBtn.innerHTML = '<i class="bi bi-save me-1"></i> Save Changes';
                    });
                });
            }
        </script>
        <style>
            .cm-editor { height: 600px; background: var(--ss-dark-1); color: var(--ss-text); }
            .cm-gutters { background: var(--ss-dark-2); color: var(--ss-text-muted); border-right: 1px solid var(--ss-border); }
        </style>
        @endpush

    @elseif($file->isText($mimeType) && $content === null)
        {{-- Text file too large to preview --}}
        <div class="text-center py-5">
            <i class="bi bi-file-text" style="font-size:3rem;color:var(--ss-text-muted);"></i>
            <p class="text-muted mt-3 mb-4">This file is too large to preview ({{ $file->sizeHuman() }}).</p>
            <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}" class="btn btn-ss-primary">
                <i class="bi bi-download me-2"></i> Download to view
            </a>
        </div>

        {{-- Non-previewable fallback --}}
        <div class="text-center py-5">
            <i class="bi {{ $file->iconClass($mimeType) }}" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-4">Preview is not available for this file type.</p>
            <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}?version_id={{ $version->id }}" class="btn btn-ss-primary">
                <i class="bi bi-download me-2"></i> Download
            </a>
        </div>
    @endif
</div>

{{-- Version History --}}
@if($file->versions->count() > 1)
<div class="ss-card mt-4">
    <h3 class="h5 fw-bold mb-3">Version History</h3>
    <div class="list-group list-group-flush">
        @foreach($file->versions->sortByDesc('id') as $v)
            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 border-secondary">
                <div>
                    <div class="fw-bold text-white">Version {{ $v->version_number }}</div>
                    <div class="small text-muted">
                        {{ $v->created_at->format('M d, Y H:i') }} &middot; {{ $v->sizeHuman() }} &middot; Checkpoint: <a href="{{ route('projects.checkpoints.show', [$project->slug, $v->checkpoint_id]) }}" class="text-decoration-none">{{ $v->checkpoint->title }}</a>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @if($v->id !== $version->id)
                        <a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}?version_id={{ $v->id }}" class="btn btn-outline-secondary btn-sm">View</a>
                        <a href="{{ route('projects.files.diff', [$project->slug, $file->id]) }}?from={{ $v->id }}&to={{ $version->id }}" class="btn btn-ss-primary btn-sm">Diff with {{ $version->version_number }}</a>
                    @else
                        <span class="badge bg-secondary d-flex align-items-center">Currently Viewing</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
