<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestController extends Controller
{
    /**
     * Test push notification by creating a test notification
     */
    public function testPushNotification(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Create a test notification
            $notification = Notification::create([
                'player_id' => $user->id,
                'type' => 'admin_message',
                'message' => 'Test push notification from CueSports Kenya!',
                'data' => [
                    'test' => true,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test notification created and push notification sent',
                'notification_id' => $notification->id,
                'user_has_fcm_token' => !empty($user->fcm_token)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's FCM token status
     */
    public function getFcmTokenStatus(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            return response()->json([
                'success' => true,
                'has_fcm_token' => !empty($user->fcm_token),
                'fcm_token_updated_at' => $user->fcm_token_updated_at,
                'user_id' => $user->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get FCM token status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
