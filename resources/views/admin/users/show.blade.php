@extends('admin.layouts.admin')

@section('title', $user->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-ss-outline mb-2">Back to users</a>
        <h2 class="mb-0">{{ $user->name }}</h2>
        <p class="mb-0" style="color:var(--ss-text-muted);">{{ $user->email }} · {{ '@'.$user->username }}</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="ss-card h-100">
            <h3 class="h6">Account</h3>
            <dl class="mb-0 small">
                <dt>Plan</dt><dd>{{ ucfirst($user->plan) }}</dd>
                <dt>Projects</dt><dd>{{ $user->projects->count() }}</dd>
                <dt>Storage used</dt><dd>{{ \Illuminate\Support\Number::fileSize($user->storage_used_bytes) }}</dd>
                <dt>Joined</dt><dd>{{ $user->created_at->format('M j, Y') }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-md-8">
        <div class="ss-card h-100">
            <h3 class="h6">Upgrade requests</h3>
            @forelse($user->upgradeRequests as $request)
                <div class="border-bottom py-2" style="border-color:var(--ss-border) !important;">
                    {{ $request->formattedPlan() }} · {{ ucfirst($request->status) }}
                    <span class="small" style="color:var(--ss-text-muted);">{{ $request->created_at->format('M j, Y H:i') }}</span>
                </div>
            @empty
                <p class="mb-0" style="color:var(--ss-text-muted);">No upgrade requests.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
