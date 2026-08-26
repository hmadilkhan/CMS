<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Notifications extends Component
{
    /**
     * How many notifications the dropdown shows at once.
     * The full list lives on the notifications index page.
     */
    public int $limit = 10;

    public function unread($notificationId, $url)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            if (is_null($notification->read_at)) {
                $notification->read_at = date('Y-m-d H:i:s');
                $notification->save();
            }

            return $this->redirect($url);
        }
    }

    /**
     * Mark a single notification as read without navigating away, so the
     * user can dismiss the "new" state of one item and stay in the dropdown.
     */
    public function markAsRead($notificationId)
    {
        $user = auth()->user();

        $user->notifications()
            ->whereKey($notificationId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->refreshBell($user);
    }

    public function markAllAsRead()
    {
        $user = auth()->user();

        $updated = $user->unreadNotifications()->update(['read_at' => now()]);

        Log::info('Notifications marked as read', ['user_id' => $user->id, 'updated' => $updated]);

        $this->refreshBell($user);
    }

    /**
     * Drop the cached relations and tell the header bell (which lives outside
     * this component) how many unread notifications are left, so its pulse
     * animation can stop without a page reload.
     */
    protected function refreshBell($user): void
    {
        $user->unsetRelation('notifications')->unsetRelation('unreadNotifications');

        $this->dispatch(
            'notifications-updated',
            unread: $user->notifications()->whereNull('read_at')->count()
        );
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.notifications', [
            // Read notifications stay listed - marking as read only clears the
            // "new" highlight, it does not hide the notification.
            'notifications' => $user->notifications()
                ->orderBy('created_at', 'desc')
                ->limit($this->limit)
                ->get(),
            'unreadCount' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    }
}
