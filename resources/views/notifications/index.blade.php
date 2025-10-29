@extends('layouts.master')
@section('title', 'All Notifications')
@section('content')
<style>
    .notification-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        background: white;
        border-radius: 8px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .notification-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .notification-card.unread {
        border-left-color: #2c3e50;
        background: #f8f9fa;
    }
    .notification-card.read {
        border-left-color: #e9ecef;
        opacity: 0.8;
    }
    .notification-header {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        padding: 2rem;
        border-radius: 12px;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .notification-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        color: white;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    .notification-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .mark-read-btn {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .notification-card:hover .mark-read-btn {
        opacity: 1;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 5rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
</style>

<div class="container-xxxl">
    <div class="notification-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2 fw-bold"><i class="icofont-notification me-3"></i>All Notifications</h2>
                <p class="mb-0 opacity-75">Stay updated with your latest activities</p>
            </div>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-light">
                    <i class="icofont-check-circled me-2"></i>Mark All as Read
                </button>
            </form>
            @endif
        </div>
    </div>

    @if($notifications->count() > 0)
        <div class="row">
            <div class="col-12 px-3">
                @foreach($notifications as $notification)
                <div class="notification-card {{ $notification->read_at ? 'read' : 'unread' }}">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start">
                            <div class="notification-icon me-3">
                                <i class="icofont-{{ $notification->read_at ? 'envelope-open' : 'envelope' }}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $notification->data['mentioned_by'] ?? 'System' }}</h6>
                                        <small class="text-muted">
                                            <i class="icofont-clock-time me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        @if(!$notification->read_at)
                                        <span class="notification-badge bg-dark text-white me-2">New</span>
                                        @endif
                                        @if(isset($notification->data['project_name']))
                                        <span class="notification-badge bg-success text-white">{{ $notification->data['project_name'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="mb-2">{{ $notification->data['note'] ?? 'You have a new notification' }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    @if(isset($notification->data['url']))
                                    <a href="{{ route('notifications.mark-read', $notification->id) }}" class="btn btn-sm btn-dark">
                                        <i class="icofont-eye me-1"></i>View Details
                                    </a>
                                    @endif
                                    @if(!$notification->read_at)
                                    <form action="{{ route('notifications.mark-read', $notification->id) }}" method="POST" class="mark-read-btn">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="icofont-check"></i> Mark as Read
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                <div class="d-flex justify-content-center mt-4">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    @else
        <div class="empty-state">
            <i class="icofont-inbox"></i>
            <h4 class="fw-bold mb-2">No Notifications</h4>
            <p>You're all caught up! Check back later for updates.</p>
        </div>
    @endif
</div>
@endsection
