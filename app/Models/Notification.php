<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'type',
        'message',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Send push notification when a new notification is created
        static::created(function ($notification) {
            $notification->sendPushNotification();
        });
    }

    /**
     * Send push notification to the user
     */
    public function sendPushNotification(): void
    {
        try {
            // Get the user's FCM token with fresh data
            $user = $this->player()->first();
            
            if (!$user || !$user->fcm_token) {
                Log::info('No FCM token found for user', [
                    'user_id' => $this->player_id,
                    'notification_id' => $this->id,
                    'user_found' => $user ? 'yes' : 'no',
                    'fcm_token_exists' => $user && $user->fcm_token ? 'yes' : 'no'
                ]);
                return;
            }

            // Initialize Firebase service
            $firebaseService = new FirebaseService();
            
            // Ensure data is an array (defensive programming)
            $notificationData = $this->data;
            if (is_string($notificationData)) {
                $notificationData = json_decode($notificationData, true) ?? [];
            } elseif (!is_array($notificationData)) {
                $notificationData = [];
            }
            
            // Send push notification
            $success = $firebaseService->sendNotificationByType(
                $user->fcm_token,
                $this->type,
                array_merge($notificationData, [
                    'message' => $this->message,
                    'notification_id' => $this->id
                ])
            );

            if ($success) {
                Log::info('Push notification sent successfully', [
                    'user_id' => $this->player_id,
                    'notification_id' => $this->id,
                    'type' => $this->type
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $this->player_id,
                'notification_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
