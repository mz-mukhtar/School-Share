@extends('layouts.app')

@section('title', ucfirst($type) . ' - ' . $user->name)

@section('content')

<div class="mb-4">
    <a href="{{ route('profile.public', $user->username) }}" class="text-decoration-none text-muted mb-2 d-inline-block">
        <i class="bi bi-arrow-left"></i> Back to Profile
    </a>
    <h1 class="h3 fw-bold mb-1">{{ $user->name }}</h1>
    <p class="text-muted mb-0">{{ '@' . $user->username }}</p>
</div>

<ul class="nav nav-tabs mb-4" style="border-bottom: 1px solid var(--ss-border);">
    <li class="nav-item">
        <a class="nav-link {{ $type === 'followers' ? 'active' : '' }}" href="{{ route('profile.followers', $user->username) }}" style="{{ $type === 'followers' ? 'background: transparent; color: var(--ss-primary); border-color: var(--ss-border) var(--ss-border) transparent;' : 'color: var(--ss-text-muted); border-color: transparent;' }}">
            Followers <span class="badge rounded-pill bg-secondary ms-1">{{ $user->followers()->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $type === 'following' ? 'active' : '' }}" href="{{ route('profile.following', $user->username) }}" style="{{ $type === 'following' ? 'background: transparent; color: var(--ss-primary); border-color: var(--ss-border) var(--ss-border) transparent;' : 'color: var(--ss-text-muted); border-color: transparent;' }}">
            Following <span class="badge rounded-pill bg-secondary ms-1">{{ $user->following()->count() }}</span>
        </a>
    </li>
</ul>

@if($connections->isEmpty())
    <div class="ss-card text-center py-5">
        <div class="mb-3">
            <i class="bi bi-people" style="font-size: 3rem; color: var(--ss-text-muted);"></i>
        </div>
        <h3 class="h5 fw-bold">No {{ $type }} yet</h3>
    </div>
@else
    <div class="row g-4">
        @foreach($connections as $connection)
            <div class="col-md-6 col-lg-4">
                <div class="ss-card h-100 d-flex flex-column hover-lift">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <a href="{{ route('profile.public', $connection->username) }}" class="text-decoration-none">
                            @if($connection->avatar_path)
                                <img src="{{ asset('storage/' . $connection->avatar_path) }}" alt="{{ $connection->name }}" class="rounded-circle shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                            @else
                                <div class="ss-avatar" style="width: 48px; height: 48px; font-size: 1.5rem;" title="{{ $connection->name }}">
                                    {{ strtoupper(substr($connection->name, 0, 1)) }}
                                </div>
                            @endif
                        </a>
                        <div class="d-flex flex-column">
                            <a href="{{ route('profile.public', $connection->username) }}" class="text-decoration-none fw-bold fs-5 text-white">
                                {{ $connection->name }}
                            </a>
                            <span class="text-muted small">{{ '@' . $connection->username }}</span>
                        </div>
                    </div>
                    
                    <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $connection->bio ?: 'No bio provided.' }}
                    </p>
                    
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ss-border) !important;">
                        <span class="small text-muted">
                            Joined {{ $connection->created_at->format('M Y') }}
                        </span>
                        @auth
                            @if(auth()->id() !== $connection->id)
                                @php
                                    $isFollowing = auth()->user()->following()->where('following_id', $connection->id)->exists();
                                @endphp
                                @if($isFollowing)
                                    <span class="badge bg-warning text-dark">Following</span>
                                @endif
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $connections->links() }}
    </div>
@endif

@push('styles')
<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        border-color: rgba(255,255,255,0.15);
    }
</style>
@endpush
@endsection
