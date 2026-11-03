@extends('layouts.master')
@section('title', 'All Notifications')
@section('content')
<style>
    .notif-header {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        padding: 1.5rem 1.75rem;
        border-radius: 12px;
        color: #fff;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .notif-header h4 { margin: 0; font-weight: 700; }
    .notif-header p { margin: 0.25rem 0 0; opacity: 0.7; font-size: 0.9rem; }
    .notif-count-pill {
        background: rgba(255,255,255,0.15);
        border-radius: 20px;
        padding: 0.15rem 0.65rem;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 0.5rem;
        vertical-align: middle;
    }

    .notif-list {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .notif-item {
        display: flex;
        gap: 1rem;
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid #eef0f2;
        border-left: 4px solid transparent;
        transition: background 0.2s ease, border-color 0.2s ease;
    }
    .notif-item:last-child { border-bottom: 0; }
    .notif-item:hover { background: #f8f9fa; }
    .notif-item.unread { border-left-color: #2c3e50; background: #f8f9fa; }
    .notif-item.read .notif-body { opacity: 0.75; }

    .notif-avatar {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        color: #fff;
        font-size: 1.2rem;
    }
    .notif-item.read .notif-avatar { background: #dee2e6; color: #6c757d; }

    .notif-body { flex: 1; min-width: 0; }
    .notif-top {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .notif-title { font-weight: 700; color: #2c3e50; }
    .notif-time { font-size: 0.8rem; color: #6c757d; white-space: nowrap; }
    .notif-text { margin: 0.25rem 0 0.6rem; color: #343a40; word-break: break-word; }
    .notif-tags { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.6rem; }
    .notif-tag {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.15rem 0.6rem;
        border-radius: 20px;
    }
    .notif-tag.new { background: #2c3e50; color: #fff; }
    .notif-tag.project { background: #e9ecef; color: #2c3e50; }
    .notif-actions { display: flex; gap: 0.5rem; align-items: center; }
    .notif-actions form { margin: 0; }
    .notif-actions .btn { font-size: 0.8rem; }
    .notif-actions .btn:disabled { opacity: 0.6; }

    .notif-search {
        position: relative;
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .notif-search .form-control { padding-left: 2.25rem; }
    .notif-search-icon {
        position: absolute;
        top: 50%;
        left: 0.85rem;
        transform: translateY(-50%);
        color: #6c757d;
        pointer-events: none;
    }
    .notif-search-summary { color: #6c757d; font-size: 0.9rem; margin: -0.25rem 0 1rem; }

    .empty-state { text-align: center; padding: 4rem 2rem; color: #6c757d; }
    .empty-state i { font-size: 4rem; color: #dee2e6; display: block; margin-bottom: 1rem; }
</style>

<div class="container-xxxl">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="icofont-check-circled me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="notif-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4>
                    <i class="icofont-notification me-2"></i>All Notifications
                    <span class="notif-count-pill" id="unreadPill" @if(!$unreadCount) style="display:none" @endif>
                        <span id="unreadCount">{{ $unreadCount }}</span> unread
                    </span>
                </h4>
                <p>Stay updated with your latest activities</p>
            </div>
            <form action="{{ route('notifications.mark-all-read') }}" method="POST" id="markAllForm" @if(!$unreadCount) style="display:none" @endif>
                @csrf
                <button type="submit" class="btn btn-light btn-sm">
                    <i class="icofont-check-circled me-1"></i>Mark All as Read
                </button>
            </form>
        </div>
    </div>

    <form method="GET" action="{{ route('notifications.index') }}" class="notif-search" role="search">
        <i class="icofont-search-1 notif-search-icon"></i>
        <input type="search" name="q" value="{{ $search }}" class="form-control"
            placeholder="Search notifications by keyword or phrase..." aria-label="Search notifications" autocomplete="off">
        <button type="submit" class="btn btn-dark">Search</button>
        @if($search)
        <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </form>

    @if($search)
    <p class="notif-search-summary">
        {{ $notifications->total() }} {{ Str::plural('notification', $notifications->total()) }} matching "{{ $search }}"
    </p>
    @endif

    @if($notifications->count() > 0)
        <div class="notif-list">
            @foreach($notifications as $notification)
            <div class="notif-item {{ $notification->read_at ? 'read' : 'unread' }}">
                <div class="notif-avatar">
                    <i class="icofont-{{ $notification->read_at ? 'envelope-open' : 'envelope' }}"></i>
                </div>
                <div class="notif-body">
                    <div class="notif-top">
                        <span class="notif-title">{{ $notification->data['mentioned_by'] ?? 'System' }}</span>
                        <span class="notif-time"><i class="icofont-clock-time me-1"></i>{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="notif-text">{{ $notification->data['note'] ?? $notification->data['subject'] ?? 'You have a new notification' }}</p>

                    @if(!$notification->read_at || isset($notification->data['project_name']))
                    <div class="notif-tags">
                        @if(!$notification->read_at)
                        <span class="notif-tag new">New</span>
                        @endif
                        @if(isset($notification->data['project_name']))
                        <span class="notif-tag project">{{ $notification->data['project_name'] }}</span>
                        @endif
                    </div>
                    @endif

                    <div class="notif-actions">
                        @if(isset($notification->data['url']))
                        <a href="{{ route('notifications.mark-read', $notification->id) }}" class="btn btn-sm btn-dark">
                            <i class="icofont-eye me-1"></i>View Details
                        </a>
                        @endif
                        @if(!$notification->read_at)
                        <form action="{{ route('notifications.mark-read-only', $notification->id) }}" method="POST" class="mark-read-form">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="icofont-check me-1"></i>Mark as Read
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $notifications->links() }}
        </div>
    @elseif($search)
        <div class="empty-state">
            <i class="icofont-search-1"></i>
            <h5 class="fw-bold mb-2">No Matches</h5>
            <p>No notifications contain "{{ $search }}". Try a different keyword or phrase.</p>
        </div>
    @else
        <div class="empty-state">
            <i class="icofont-inbox"></i>
            <h5 class="fw-bold mb-2">No Notifications</h5>
            <p>You're all caught up! Check back later for updates.</p>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('#markAllForm input[name="_token"]')?.value
            || document.querySelector('.mark-read-form input[name="_token"]')?.value;

        function post(url) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });
        }

        // Keep the header bell and its dropdown in step with this page.
        function syncHeader(n) {
            window.dispatchEvent(new CustomEvent('notifications-updated', { detail: { unread: n } }));
            if (window.Livewire) Livewire.dispatch('notifications-refresh');
        }

        function setUnread(n) {
            syncHeader(n);
            document.getElementById('unreadCount').textContent = n;
            document.getElementById('unreadPill').style.display = n > 0 ? '' : 'none';
            document.getElementById('markAllForm').style.display = n > 0 ? '' : 'none';
        }

        function markItemRead(item) {
            item.classList.replace('unread', 'read');
            item.querySelector('.notif-avatar i')?.classList.replace('icofont-envelope', 'icofont-envelope-open');
            item.querySelector('.notif-tag.new')?.remove();
            const tags = item.querySelector('.notif-tags');
            if (tags && !tags.children.length) tags.remove();
            item.querySelector('.mark-read-form')?.remove();
        }

        // Stay exactly where the user is: no reload, so no jump to the top.
        document.querySelectorAll('.mark-read-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = form.querySelector('button');
                btn.disabled = true;
                post(form.action)
                    .then(data => {
                        markItemRead(form.closest('.notif-item'));
                        setUnread(data.unread);
                    })
                    .catch(() => { form.submit(); }); // fall back to the normal request
            });
        });

        document.getElementById('markAllForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            post(this.action)
                .then(() => {
                    document.querySelectorAll('.notif-item.unread').forEach(markItemRead);
                    setUnread(0);
                })
                .catch(() => { this.submit(); });
        });
    })();
</script>
@endsection
