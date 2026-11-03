<div class="card border-0 w380">
    <div class="card-header border-0 p-3">
        <h5 class="mb-0 font-weight-light d-flex justify-content-between align-items-center">
            <span>Notifications <span class="notification-count">{{ $unreadCount }}</span></span>
            @if ($unreadCount)
                <button type="button" class="btn btn-mark-all-read" wire:click="markAllAsRead"
                    wire:loading.attr="disabled" wire:target="markAllAsRead">
                    <i class="icofont-check-alt" wire:loading.remove wire:target="markAllAsRead"></i>
                    <span wire:loading.remove wire:target="markAllAsRead">Mark all as read</span>
                    <span wire:loading wire:target="markAllAsRead">Marking...</span>
                </button>
            @endif
        </h5>
    </div>
    <div class="notification-search px-3 pt-3">
        <div class="sf-search" role="search">
            <i class="icofont-search-1 sf-search-icon" aria-hidden="true"></i>
            <input type="search" class="sf-search-input" placeholder="Search notifications..."
                aria-label="Search notifications" autocomplete="off"
                wire:model.live.debounce.300ms="search" wire:keydown.escape="$set('search', '')">
            @if ($search !== '')
                <button type="button" class="sf-search-clear" title="Clear search" aria-label="Clear search"
                    wire:click="$set('search', '')">
                    <i class="icofont-close-line"></i>
                </button>
            @endif
        </div>
    </div>
    <div class="tab-content card-body">
        <div class="tab-pane fade show active">
            <ul class="list-unstyled list mb-0">
                @forelse ($notifications as $notification)
                <li class="py-2 mb-1 border-bottom notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                    <div class="d-flex align-items-start">
                        <a wire:click.prevent="unread('{{ $notification->id }}','{{ $notification->data['url'] ?? '#' }}')" href="#" class="d-flex flex-fill" wire:loading.attr="disabled">
                            <img class="avatar rounded-circle" src="assets/images/xs/avatar1.jpg" alt="">
                            <div class="flex-fill ms-2">
                                <p class="d-flex justify-content-between mb-0 "><span class="font-weight-bold">{{ \App\Support\NotificationSearch::highlight($notification->data['mentioned_by'] ?? 'System', $searchTerm) }}</span> <small>{{ $notification->created_at->diffForHumans() }}</small></p>
                                <span class="">{{ \App\Support\NotificationSearch::highlight($notification->data['note'] ?? $notification->data['subject'] ?? 'New notification', $searchTerm) }} @if(isset($notification->data['project_name']))<span class="badge bg-success">{{ \App\Support\NotificationSearch::highlight($notification->data['project_name'], $searchTerm) }}</span>@endif</span>
                            </div>
                        </a>
                        @if (!$notification->read_at)
                        <button type="button" class="btn btn-mark-one-read ms-2" title="Mark as read"
                            wire:click="markAsRead('{{ $notification->id }}')"
                            wire:loading.attr="disabled" wire:target="markAsRead('{{ $notification->id }}')">
                            <i class="icofont-check"></i>
                        </button>
                        @endif
                    </div>
                </li>
               @empty
                <li class="py-3 text-center text-muted">
                    @if ($searchTerm)
                        <small>No notifications match "{{ $searchTerm }}"</small>
                    @else
                        <small>No notifications yet</small>
                    @endif
                </li>
               @endforelse
            </ul>
        </div>
    </div>
    <a class="card-footer text-center border-top-0" href="{{ route('notifications.index', $searchTerm ? ['q' => $searchTerm] : []) }}">
        @if ($searchTerm)
            View all results for "{{ $searchTerm }}"
        @elseif ($unreadCount)
            View all notifications ({{ $unreadCount }} unread)
        @else
            View all notifications
        @endif
    </a>
</div>
