@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="container-fluid max-w-4xl mx-auto">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="m-0" style="font-weight:700;">Notifications</h2>
        
        @if(auth()->user()->unreadNotifications()->count() > 0)
            <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ss-outline btn-sm">
                    <i class="bi bi-check2-all"></i> Mark all as read
                </button>
            </form>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="ss-card text-center py-5">
            <i class="bi bi-bell-slash fs-1" style="color:var(--ss-text-muted);"></i>
            <p class="mt-3 mb-0" style="color:var(--ss-text-muted);">You have no notifications yet.</p>
        </div>
    @else
        <div class="ss-card p-0 overflow-hidden">
            <div class="list-group list-group-flush">
                @foreach($notifications as $notification)
                    @php
                        $isRead = $notification->read_at !== null;
                        $bg = $isRead ? 'transparent' : 'rgba(37,99,235,0.05)';
                        $iconColor = $isRead ? 'var(--ss-text-muted)' : 'var(--ss-primary)';
                        
                        // Icon mapping
                        $icon = 'bi-info-circle';
                        if(str_contains($notification->type, 'CollaboratorAdded')) $icon = 'bi-person-plus';
                        if(str_contains($notification->type, 'FileUploaded')) $icon = 'bi-file-earmark-arrow-up';
                    @endphp
                    
                    <a href="{{ route('notifications.markAsRead', $notification->id) }}" class="list-group-item list-group-item-action d-flex align-items-start gap-3 p-3 border-bottom" style="background: {{ $bg }}; border-color: var(--ss-border) !important;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: var(--ss-dark-3); color: {{ $iconColor }};">
                            <i class="bi {{ $icon }} fs-5"></i>
                        </div>
                        
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 {{ $isRead ? '' : 'fw-bold' }}" style="color:var(--ss-text);">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </h6>
                                <small style="color:var(--ss-text-muted); font-size: 0.75rem;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <p class="mb-0" style="color:var(--ss-text-muted); font-size: 0.875rem;">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                        </div>
                        
                        @if(!$isRead)
                            <div class="align-self-center">
                                <span class="badge rounded-pill bg-primary" style="width:10px;height:10px;padding:0;">&nbsp;</span>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
        
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
