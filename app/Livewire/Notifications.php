<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Notifications extends Component
{
    /**
     * How many unread notifications the dropdown shows at once.
     * The full list lives on the notifications index page.
     */
    public int $limit = 10;

    public function unread($notificationId, $url)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->read_at = date('Y-m-d H:i:s');
            $notification->save();

            return $this->redirect($url);
        }
    }

    public function markAllAsRead()
    {
        $user = auth()->user();

        $updated = $user->unreadNotifications()->update(['read_at' => now()]);

        Log::info('Notifications marked as read', ['user_id' => $user->id, 'updated' => $updated]);

        $user->unsetRelation('notifications')->unsetRelation('unreadNotifications');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.notifications', [
            'notifications' => $user->notifications()
                ->whereNull('read_at')
                ->orderBy('created_at', 'desc')
                ->limit($this->limit)
                ->get(),
            'unreadCount' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    }
}
