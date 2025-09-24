<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\AdminMessage;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Notification::where('player_id', $user->id);
        
        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $notifications = $query->orderBy('created_at', 'desc')->get();
        
        // Get admin messages (simplified to avoid missing column issues)
        $adminMessages = collect(); // Empty collection for now
        
        // Combine and format notifications
        $allNotifications = collect();
        
        // Add regular notifications
        foreach ($notifications as $notification) {
            $allNotifications->push([
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $this->getNotificationTitle($notification->type),
                'message' => $notification->message,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'source' => 'notification'
            ]);
        }
        
        // Add admin messages
        foreach ($adminMessages as $message) {
            $allNotifications->push([
                'id' => $message->id,
                'type' => 'admin_message',
                'title' => $message->title,
                'message' => $message->message,
                'data' => null,
                'read_at' => null, // Admin messages don't track read status per user
                'created_at' => $message->created_at,
                'source' => 'admin_message'
            ]);
        }
        
        // Sort by creation date
        $allNotifications = $allNotifications->sortByDesc('created_at')->values();
        
        return response()->json([
            'success' => true,
            'notifications' => $allNotifications
        ]);
    }
    
    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $user = auth()->user();
        
        $unreadCount = Notification::where('player_id', $user->id)
            ->whereNull('read_at')
            ->count();
            
        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $user = auth()->user();
        
        // Check if notification belongs to user
        if ($notification->player_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $notification->update(['read_at' => now()]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        
        Notification::where('player_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
            
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
    
    /**
     * Get notification title based on type
     */
    private function getNotificationTitle($type)
    {
        switch ($type) {
            case 'pairing':
                return 'New Match Pairing';
            case 'match_result':
                return 'Match Result Submitted';
            case 'match_scheduled':
                return 'Match Scheduled';
            case 'tournament_registration':
                return 'Tournament Registration';
            case 'tournament_update':
                return 'Tournament Update';
            case 'admin_message':
                return 'Admin Message';
            default:
                return 'Notification';
        }
    }

    /**
     * Clear all notifications for the user
     */
    public function clearAll()
    {
        $user = auth()->user();
        
        $deletedCount = Notification::where('player_id', $user->id)->delete();
        
        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} notifications",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Real-time notification check for polling
     */
    public function realTimeCheck()
    {
        $user = auth()->user();
        
        $unreadCount = Notification::where('player_id', $user->id)
            ->whereNull('read_at')
            ->count();
            
        $latestNotification = Notification::where('player_id', $user->id)
            ->latest()
            ->first();
        
        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'latest_notification' => $latestNotification ? [
                'id' => $latestNotification->id,
                'type' => $latestNotification->type,
                'title' => $this->getNotificationTitle($latestNotification->type),
                'message' => $latestNotification->message,
                'created_at' => $latestNotification->created_at->toISOString(),
            ] : null,
            'timestamp' => now()->toISOString()
        ]);
    }
}
