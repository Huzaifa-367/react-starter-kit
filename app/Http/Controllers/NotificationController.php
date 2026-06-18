<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->userNotifications()
            ->latest()
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($notifications);
        }

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark the specified notification as read.
     */
    public function markRead(Request $request, int $id): RedirectResponse
    {
        $notification = $request->user()->userNotifications()
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        // Clear the cached unread count
        Cache::forget("user:{$request->user()->id}:unread_notifications_count");

        return back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications for the user as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->userNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Clear the cached unread count
        Cache::forget("user:{$request->user()->id}:unread_notifications_count");

        return back()->with('status', 'All notifications marked as read.');
    }

    /**
     * Get the count of unread notifications for the authenticated user (cached).
     */
    public function unreadCount(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = $request->user()->id;
        
        $count = Cache::remember("user:{$userId}:unread_notifications_count", 900, function () use ($request) {
            return $request->user()->userNotifications()
                ->whereNull('read_at')
                ->count();
        });

        return response()->json(['unread_count' => $count]);
    }
}
