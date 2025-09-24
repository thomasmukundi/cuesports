<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->notifications()->orderBy('created_at', 'desc');
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $notifications = $query->paginate($request->get('per_page', 20));
        
        return response()->json($notifications);
    }

    /**
     * Get unread notifications
     */
    public function unread()
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        $user = Auth::user();
        
        $notification = Notification::where('player_id', $user->id)
            ->where('id', $notificationId)
            ->firstOrFail();
        
        $notification->update(['is_read' => true]);
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $updated = $user->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json([
            'message' => "Marked {$updated} notifications as read"
        ]);
    }
}
