@extends('layouts.app')

@section('title', $user->name . ' (@' . $user->username . ')')

@section('content')

<div class="row">
    {{-- Left Sidebar: Profile Info --}}
    <div class="col-md-3 mb-4">
        <div class="text-center text-md-start">
            {{-- Avatar --}}
            <div class="mb-3">
                @if($user->avatar_path)
                    <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="rounded-circle img-fluid shadow-sm" style="width: 200px; height: 200px; object-fit: cover; border: 4px solid var(--ss-dark-3);">
                @else
                    <div class="rounded-circle mx-auto mx-md-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 200px; height: 200px; background: var(--ss-dark-3); border: 4px solid var(--ss-border);">
                        <span class="fw-bold" style="font-size: 5rem; color: var(--ss-text-muted);">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Name and Username --}}
            <h1 class="h3 fw-bold mb-0">{{ $user->name }}</h1>
            <p class="text-muted fs-5 mb-3">
                {{ $user->username ? '@' . $user->username : '' }}
                @if($user->isPro())
                    <span class="badge ms-1" style="background:rgba(234,179,8,0.15);color:#fde047;">PRO</span>
                @endif
            </p>

            {{-- Bio --}}
            @if($user->bio)
                <p class="mb-3 text-white-50">{{ $user->bio }}</p>
            @endif

            {{-- Followers / Following --}}
            <div class="d-flex gap-3 mb-4">
                <a href="{{ route('profile.followers', $user->username) }}" class="text-decoration-none text-white-50">
                    <strong class="text-white">{{ $user->followers()->count() }}</strong> followers
                </a>
                <a href="{{ route('profile.following', $user->username) }}" class="text-decoration-none text-white-50">
                    <strong class="text-white">{{ $user->following()->count() }}</strong> following
                </a>
            </div>

            {{-- Follow / Edit Button --}}
            <div class="mb-4">
                @auth
                    @if(auth()->id() === $user->id)
                        <a href="{{ route('profile.edit') }}" class="btn btn-ss-outline w-100">Edit Profile</a>
                    @else
                        @php
                            $isFollowing = auth()->user()->following()->where('following_id', $user->id)->exists();
                        @endphp
                        <button class="btn {{ $isFollowing ? 'btn-ss-outline text-warning' : 'btn-ss-outline' }} w-100" id="follow-btn" onclick="toggleFollow()">
                            {{ $isFollowing ? 'Unfollow' : 'Follow' }}
                        </button>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-ss-outline w-100">Follow</a>
                @endauth
            </div>

            <div class="d-flex flex-column gap-2 text-muted small">
                <div><i class="bi bi-calendar3 me-2"></i> Joined {{ $user->created_at->format('M Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Right Content: Activity & Projects --}}
    <div class="col-md-9">
        
        {{-- Heatmap Placeholder --}}
        <div class="mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-calendar-week text-primary"></i>
                Contributions
            </h5>
            <div class="ss-card py-4">
                <x-activity-calendar :contributions="$contributions" />
            </div>
        </div>

        {{-- Public Projects --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-journal-code text-primary"></i>
                Public Projects
            </h5>
        </div>

        @if($projects->isEmpty())
            <div class="ss-card text-center py-5">
                <i class="bi bi-journal-x mb-3" style="font-size:3rem; color:var(--ss-primary); opacity:0.6;"></i>
                <h5 class="fw-bold">No public projects</h5>
                <p class="text-muted mb-0">{{ $user->name }} hasn't published any public projects yet.</p>
            </div>
        @else
            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                @foreach($projects as $project)
                    <div class="col">
                        <div class="ss-card h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <a href="{{ route('projects.show', $project->slug) }}" class="fw-bold text-info text-decoration-none fs-5 text-truncate" style="max-width:80%;">
                                    {{ $project->name }}
                                </a>
                                <span class="badge rounded-pill" style="background:var(--ss-dark-3);border:1px solid var(--ss-border);color:var(--ss-text-muted);">
                                    <i class="bi bi-globe me-1"></i> Public
                                </span>
                            </div>
                            
                            <p class="text-muted small flex-grow-1 mb-3">
                                {{ Str::limit($project->description, 100) ?: 'No description provided.' }}
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-3" style="border-top:1px solid var(--ss-border);">
                                <div class="d-flex gap-3 small text-muted">
                                    <span title="Stars"><i class="bi bi-star me-1"></i>{{ $project->star_count }}</span>
                                    <span title="Checkpoints"><i class="bi bi-flag me-1"></i>{{ $project->checkpoints_count }}</span>
                                </div>
                                <small class="text-muted">Updated {{ $project->updated_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="d-flex justify-content-center">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
@auth
<script>
    function toggleFollow() {
        const btn = document.getElementById('follow-btn');
        const userId = {{ $user->id }};
        
        fetch(`/users/${userId}/follow`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'followed') {
                btn.textContent = 'Unfollow';
                btn.classList.add('text-warning');
            } else {
                btn.textContent = 'Follow';
                btn.classList.remove('text-warning');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Failed to follow/unfollow user');
        });
    }
</script>
@endauth
@endpush
