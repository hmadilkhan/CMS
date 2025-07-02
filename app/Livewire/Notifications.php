<?php

namespace App\Livewire;

use Livewire\Component;

class Notifications extends Component
{
    public $notifications;

    public function mount()
    {
        $this->notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->whereNull('read_at')->get();
    }

    public function unread($notificationId,$url)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->read_at = date("Y-m-d H:i:s");
            $notification->save();
            return $this->redirect($url);
        }
    }

    public function render()
    {
        return view('livewire.notifications');
    }
}
