<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            return redirect($notification->data['url'] ?? route('notifications.index'));
        }
        return redirect()->back();
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return redirect()->back()->with('success', 'All notifications marked as read');
    }
}
