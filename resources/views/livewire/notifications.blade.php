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
    <div class="tab-content card-body">
        <div class="tab-pane fade show active">
            <ul class="list-unstyled list mb-0">
                @forelse ($notifications as $notification)
                <li class="py-2 mb-1 border-bottom">
                    <a wire:click.prevent="unread('{{ $notification->id }}','{{ $notification->data['url'] ?? '#' }}')" href="#" class="d-flex" wire:loading.attr="disabled">
                        <img class="avatar rounded-circle" src="assets/images/xs/avatar1.jpg" alt="">
                        <div class="flex-fill ms-2">
                            <p class="d-flex justify-content-between mb-0 "><span class="font-weight-bold">{{ $notification->data['mentioned_by'] ?? 'System' }}</span> <small>{{ $notification->created_at->diffForHumans() }}</small></p>
                            <span class="">{{ $notification->data['note'] ?? $notification->data['subject'] ?? 'New notification' }} @if(isset($notification->data['project_name']))<span class="badge bg-success">{{$notification->data['project_name']}}</span>@endif</span>
                        </div>
                    </a>
                </li>
               @empty
                <li class="py-3 text-center text-muted">
                    <small>No unread notifications</small>
                </li>
               @endforelse
            </ul>
        </div>
    </div>
    <a class="card-footer text-center border-top-0" href="{{ route('notifications.index') }}">
        @if ($unreadCount > $notifications->count())
            View all {{ $unreadCount }} notifications
        @else
            View all notifications
        @endif
    </a>
</div>
