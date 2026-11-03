<?php

namespace App\Http\Controllers;

use App\Support\NotificationSearch;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $search = NotificationSearch::term($request->query('q'));

        $notifications = NotificationSearch::apply(auth()->user()->notifications(), $search)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
        $unreadCount = auth()->user()->unreadNotifications()->count();
        return view('notifications.index', compact('notifications', 'unreadCount', 'search'));
    }

    /**
     * Mark as read and open the linked record - used by the "View Details" link.
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            return redirect($notification->data['url'] ?? route('notifications.index'));
        }
        return redirect()->back();
    }

    /**
     * Mark a single notification as read and stay on the notifications page.
     */
    public function markAsReadOnly(Request $request, $id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }

        // The page calls this with fetch() so the user keeps their scroll position.
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => (bool) $notification,
                'unread' => auth()->user()->unreadNotifications()->count(),
            ], $notification ? 200 : 404);
        }

        return $notification
            ? redirect()->back()->with('success', 'Notification marked as read')
            : redirect()->back();
    }

    public function markAllAsRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread' => 0]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }
}
