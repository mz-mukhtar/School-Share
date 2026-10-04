@extends('layouts.app')

@section('title', $checkpoint->title . ' — ' . $project->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('projects.show', $project->slug) }}" class="text-decoration-none text-muted small d-inline-block mb-1">
        <i class="bi bi-arrow-left me-1"></i> {{ $project->name }}
    </a>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mt-1">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-flag-fill text-primary"></i>
                <h1 class="h3 fw-bold mb-0">{{ $checkpoint->title }}</h1>
            </div>
            <div class="text-muted small">
                <i class="bi bi-person me-1"></i>{{ $checkpoint->author->name }}
                &middot;
                <i class="bi bi-calendar me-1"></i>{{ $checkpoint->created_at->format('M d, Y H:i') }}
                ({{ $checkpoint->created_at->diffForHumans() }})
                &middot;
                <i class="bi bi-hdd me-1"></i>{{ $checkpoint->totalSizeHuman() }}
            </div>
            @if($checkpoint->message)
                <p class="text-muted mt-2 mb-0 p-3 rounded" style="background:var(--ss-dark-3);border-left:3px solid var(--ss-primary);max-width:600px;">
                    {{ $checkpoint->message }}
                </p>
            @endif
        </div>

        @if(auth()->id() === $project->user_id)
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('projects.checkpoints.restore', [$project->slug, $checkpoint->id]) }}"
                      onsubmit="return confirm('Restore project to this checkpoint? All files will revert to their state at this time.');">
                    @csrf
                    <button type="submit" class="btn btn-ss-primary btn-sm">
                        <i class="bi bi-clock-history me-1"></i> Restore Checkpoint
                    </button>
                </form>
                <form method="POST" action="{{ route('projects.checkpoints.destroy', [$project->slug, $checkpoint->id]) }}"
                      onsubmit="return confirm('Delete this checkpoint and all its files?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Delete Checkpoint
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

{{-- Files table --}}
<div class="ss-card">
    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-folder2-open text-primary"></i>
        Files ({{ $checkpoint->fileVersions->count() }})
    </h5>

    @if($checkpoint->fileVersions->isEmpty())
        <div class="text-center text-muted py-4">No files in this checkpoint.</div>
    @else
        <div class="table-responsive">
            <table class="table mb-0" style="color:var(--ss-text-primary);">
                <thead>
                    <tr style="border-color:var(--ss-border);">
                        <th class="text-muted fw-normal ps-0" style="width:40px;"></th>
                        <th class="text-muted fw-normal">Name</th>
                        <th class="text-muted fw-normal text-end">Type</th>
                        <th class="text-muted fw-normal text-end">Size</th>
                        <th class="text-muted fw-normal text-end pe-0">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checkpoint->fileVersions as $version)
                        @php $file = $version->projectFile; @endphp
                        @if($file)
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
                            <td class="align-middle text-end text-muted small">{{ strtoupper($file->extension()) }}</td>
                            <td class="align-middle text-end text-muted small">{{ $version->sizeHuman() }}</td>
                            <td class="align-middle text-end pe-0">
                                <div class="d-flex gap-2 justify-content-end">
                                    @if($file->isPreviewable())
                                        <a href="{{ route('projects.files.show', [$project->slug, $file->id]) }}"
                                           class="btn btn-ss-outline btn-sm" title="Preview">
                                            <i class="bi bi-eye me-1"></i>Preview
                                        </a>
                                    @endif
                                    <a href="{{ route('projects.files.download', [$project->slug, $file->id]) }}"
                                       class="btn btn-ss-outline btn-sm" title="Download">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                    @if(auth()->id() === $project->user_id)
                                        <form method="POST" action="{{ route('projects.files.destroy', [$project->slug, $file->id]) }}"
                                              onsubmit="return confirm('Delete {{ addslashes($file->original_name) }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Comments Section --}}
<div class="ss-card mt-4">
    <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-chat-left-text text-primary"></i>
        Comments ({{ $checkpoint->comments->count() }})
    </h5>

    <div class="mb-4">
        @foreach($checkpoint->comments()->with('user')->latest()->get() as $comment)
            <div class="d-flex gap-3 mb-4">
                <div class="flex-shrink-0">
                    @if($comment->user->avatar)
                        <img src="{{ Storage::url($comment->user->avatar) }}" alt="Avatar" class="rounded-circle" width="40" height="40" style="object-fit: cover; border:1px solid var(--ss-border);">
                    @else
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:40px; height:40px; font-size: 1rem;">
                            {{ substr($comment->user->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <a href="{{ route('profile.public', $comment->user->username) }}" class="fw-bold text-decoration-none text-white">{{ $comment->user->name }}</a>
                            <span class="text-muted small ms-2">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        @if(auth()->id() === $comment->user_id || auth()->id() === $project->user_id)
                            <form method="POST" action="{{ route('comments.destroy', $comment->id) }}" onsubmit="return confirm('Delete this comment?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger p-0" title="Delete Comment">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="mt-1 text-white-50" style="white-space: pre-line;">
                        {{ $comment->body }}
                    </div>
                </div>
            </div>
        @endforeach

        @if($checkpoint->comments->isEmpty())
            <p class="text-muted mb-0">No comments yet. Be the first to comment!</p>
        @endif
    </div>

    @auth
        <form method="POST" action="{{ route('projects.checkpoints.comments.store', [$project->slug, $checkpoint->id]) }}">
            @csrf
            <div class="mb-3">
                <textarea name="body" class="form-control bg-transparent text-white" rows="3" placeholder="Leave a comment..." required></textarea>
                @error('body')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Post Comment</button>
            </div>
        </form>
    @else
        <div class="alert alert-secondary mt-3 mb-0 text-center">
            <a href="{{ route('login') }}" class="text-primary text-decoration-none fw-bold">Log in</a> to leave a comment.
        </div>
    @endauth
</div>
@endsection
