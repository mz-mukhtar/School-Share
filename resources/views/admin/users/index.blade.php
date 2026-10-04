@extends('admin.layouts.admin')
@section('title', 'Manage Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 style="font-weight:700;margin:0;">Users</h2>
</div>

<div class="ss-card mb-4">
    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search name or email..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="plan" class="form-select bg-dark text-light border-secondary">
                <option value="">All Plans</option>
                <option value="free" {{ request('plan') === 'free' ? 'selected' : '' }}>Free</option>
                <option value="pro" {{ request('plan') === 'pro' ? 'selected' : '' }}>Pro (Monthly)</option>
                <option value="custom" {{ request('plan') === 'custom' ? 'selected' : '' }}>Pro (Yearly)</option>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-ss-outline me-2">Filter</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-link text-muted">Clear</a>
        </div>
    </form>
</div>

<div class="ss-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--ss-text);">
            <thead>
                <tr style="border-bottom:1px solid var(--ss-border);">
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">User</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Plan</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Storage Used</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Joined</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="px-4 py-3 align-middle">
                            <div style="font-weight:600;">{{ $user->name }}</div>
                            <div style="font-size:0.75rem;color:var(--ss-text-muted);">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3 align-middle">
                            @if($user->plan === 'pro' || $user->plan === 'custom')
                                <span class="badge bg-warning text-dark">{{ ucfirst($user->plan) }}</span>
                            @else
                                <span class="badge bg-secondary">Free</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle">
                            {{ \Illuminate\Support\Number::fileSize($user->storage_used_bytes) }}
                        </td>
                        <td class="px-4 py-3 align-middle" style="font-size:0.875rem;color:var(--ss-text-muted);">
                            {{ $user->created_at->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3 align-middle">
                            <button type="button" class="btn btn-sm btn-ss-outline" data-bs-toggle="modal" data-bs-target="#editPlanModal{{ $user->id }}">
                                Edit Plan
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $users->links('pagination::bootstrap-5') }}
</div>

@foreach($users as $user)
<!-- Modal for Edit Plan -->
<div class="modal fade" id="editPlanModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--ss-dark-2);border:1px solid var(--ss-border);">
      <div class="modal-header border-0">
        <h5 class="modal-title">Edit Plan: {{ $user->name }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.users.update-plan', $user) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-body">
              <label class="form-label" style="color:var(--ss-text-muted);font-size:0.875rem;">Select New Plan</label>
              <select name="plan" class="form-select bg-dark text-light border-secondary">
                  <option value="free" {{ $user->plan === 'free' ? 'selected' : '' }}>Free</option>
                  <option value="pro" {{ $user->plan === 'pro' ? 'selected' : '' }}>Pro (Monthly)</option>
                  <option value="custom" {{ $user->plan === 'custom' ? 'selected' : '' }}>Custom/Pro (Yearly)</option>
              </select>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-ss-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-ss-primary">Save Changes</button>
          </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@endsection
