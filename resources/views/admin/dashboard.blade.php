@extends('admin.layouts.admin')
@section('title', 'Admin Dashboard')

@section('content')
<h2 class="mb-4" style="font-weight:700;">Admin Overview</h2>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="ss-card text-center">
            <h6 style="color:var(--ss-text-muted);text-transform:uppercase;font-size:0.75rem;">Total Users</h6>
            <div style="font-size:2rem;font-weight:700;">{{ number_format($totalUsers) }}</div>
            <div style="font-size:0.8rem;color:var(--ss-text-muted);">{{ number_format($activeUsers) }} active (30d)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ss-card text-center">
            <h6 style="color:var(--ss-text-muted);text-transform:uppercase;font-size:0.75rem;">Storage Used</h6>
            <div style="font-size:2rem;font-weight:700;">{{ \Illuminate\Support\Number::fileSize($totalStorageBytes) }}</div>
            <div style="font-size:0.8rem;color:var(--ss-text-muted);">across all projects</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ss-card text-center">
            <h6 style="color:var(--ss-text-muted);text-transform:uppercase;font-size:0.75rem;">Pending Upgrades</h6>
            <div style="font-size:2rem;font-weight:700;color:{{ $pendingUpgrades > 0 ? 'var(--ss-primary)' : 'inherit' }};">{{ number_format($pendingUpgrades) }}</div>
            <div style="font-size:0.8rem;color:var(--ss-text-muted);">needs review</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ss-card text-center">
            <h6 style="color:var(--ss-text-muted);text-transform:uppercase;font-size:0.75rem;">Pro/Custom Users</h6>
            <div style="font-size:2rem;font-weight:700;">{{ number_format(($planCounts['pro'] ?? 0) + ($planCounts['custom'] ?? 0)) }}</div>
            <div style="font-size:0.8rem;color:var(--ss-text-muted);">Total approved: {{ number_format($totalApproved) }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="ss-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 style="margin:0;font-weight:600;">Recent Upgrade Requests</h6>
                <a href="{{ route('admin.upgrades.index') }}" class="btn btn-sm btn-ss-outline" style="font-size:0.75rem;">View All</a>
            </div>
            
            @forelse($recentUpgrades as $req)
                <div class="d-flex align-items-center gap-3 p-3 mb-2 rounded" style="background:var(--ss-dark-3);">
                    <div style="width:40px;height:40px;background:var(--ss-dark);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-arrow-up-circle" style="color:var(--ss-primary);"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-weight:600;font-size:0.9rem;">{{ $req->user->name }}</div>
                        <div style="font-size:0.75rem;color:var(--ss-text-muted);">Requested {{ $req->formattedPlan() }}</div>
                    </div>
                    <div>
                        @if($req->status === 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($req->status === 'approved')
                            <span class="badge bg-success">Approved</span>
                        @else
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-4">No recent requests.</p>
            @endforelse
        </div>
    </div>

    <div class="col-md-6">
        <div class="ss-card h-100">
            <h6 style="margin:0 0 1rem;font-weight:600;">Storage Maintenance</h6>
            <p style="font-size:0.875rem;color:var(--ss-text-muted);line-height:1.6;">
                If you suspect the storage quotas on user profiles have drifted from the actual physical file sizes, you can run the storage recalculation command to re-sync everything.
            </p>
            <div class="p-3 rounded" style="background:var(--ss-dark);border:1px solid var(--ss-border);font-family:monospace;font-size:0.8rem;color:var(--ss-text-muted);">
                $ php artisan schoolshare:recalculate-storage
            </div>
        </div>
    </div>
</div>
@endsection
