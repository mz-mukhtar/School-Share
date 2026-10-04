@extends('admin.layouts.admin')
@section('title', 'Upgrade Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 style="font-weight:700;margin:0;">Upgrade Requests</h2>
</div>

<div class="ss-card mb-4">
    <ul class="nav nav-pills gap-2">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('admin.upgrades.index', ['status' => 'pending']) }}" style="{{ $status === 'pending' ? 'background:var(--ss-primary);color:#fff;' : 'color:var(--ss-text-muted);' }}">Pending</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}" href="{{ route('admin.upgrades.index', ['status' => 'approved']) }}" style="{{ $status === 'approved' ? 'background:var(--ss-primary);color:#fff;' : 'color:var(--ss-text-muted);' }}">Approved</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'rejected' ? 'active' : '' }}" href="{{ route('admin.upgrades.index', ['status' => 'rejected']) }}" style="{{ $status === 'rejected' ? 'background:var(--ss-primary);color:#fff;' : 'color:var(--ss-text-muted);' }}">Rejected</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.upgrades.index', ['status' => 'all']) }}" style="{{ $status === 'all' ? 'background:var(--ss-primary);color:#fff;' : 'color:var(--ss-text-muted);' }}">All</a>
        </li>
    </ul>
</div>

<div class="ss-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--ss-text);">
            <thead>
                <tr style="border-bottom:1px solid var(--ss-border);">
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">User</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Requested Plan</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Requested At</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Status</th>
                    <th class="py-3 px-4 text-muted" style="font-weight:600;font-size:0.75rem;text-transform:uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-3 align-middle">
                            <div style="font-weight:600;">{{ $req->user->name }}</div>
                            <div style="font-size:0.75rem;color:var(--ss-text-muted);">{{ $req->user->email }}</div>
                        </td>
                        <td class="px-4 py-3 align-middle">
                            <span class="badge bg-secondary">{{ $req->formattedPlan() }}</span>
                        </td>
                        <td class="px-4 py-3 align-middle" style="font-size:0.875rem;color:var(--ss-text-muted);">
                            {{ $req->created_at->format('M j, Y H:i') }}
                        </td>
                        <td class="px-4 py-3 align-middle">
                            @if($req->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($req->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle">
                            @if($req->status === 'pending')
                                <div class="d-flex gap-2">
                                    <form action="{{ route('admin.upgrades.approve', $req) }}" method="POST" onsubmit="return confirm('Are you sure you want to approve this upgrade? The user will immediately be granted the requested plan.');">
                                        @csrf
                                        <button class="btn btn-sm btn-ss-primary">Approve</button>
                                    </form>
                                    
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}">Reject</button>
                                </div>
                            @else
                                <span class="text-muted" style="font-size:0.8rem;">
                                    Processed by Admin #{{ $req->processed_by }}<br>
                                    {{ $req->processed_at->format('M j, Y') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No upgrade requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $requests->appends(['status' => $status])->links('pagination::bootstrap-5') }}
</div>

@foreach($requests as $req)
    @if($req->status === 'pending')
        <!-- Modal for Reject -->
        <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--ss-dark-2);border:1px solid var(--ss-border);">
              <div class="modal-header border-0">
                <h5 class="modal-title">Reject Upgrade Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <form action="{{ route('admin.upgrades.reject', $req) }}" method="POST">
                  @csrf
                  <div class="modal-body">
                      <p style="font-size:0.875rem;color:var(--ss-text-muted);">Please provide a reason for rejecting the request for <strong>{{ $req->user->name }}</strong>. This will be stored for admin records.</p>
                      
                      <textarea name="admin_notes" class="form-control bg-dark text-light border-secondary" rows="3" required placeholder="E.g., Payment screenshot not received, payment amount incorrect, etc."></textarea>
                  </div>
                  <div class="modal-footer border-0">
                    <button type="button" class="btn btn-ss-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                  </div>
              </form>
            </div>
          </div>
        </div>
    @endif
@endforeach

@endsection
