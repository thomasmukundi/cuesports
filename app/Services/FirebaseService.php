<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Http\HttpClientOptions;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials.file'));
            
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send push notification to a single device
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            
            Log::info('Push notification sent successfully', [
                'token' => substr($fcmToken, 0, 20) . '...',
                'title' => $title
            ]);
            
            return true;
        } catch (MessagingException $e) {
            // Check if it's an SSL certificate error
            if (strpos($e->getMessage(), 'SSL certificate problem') !== false || 
                strpos($e->getMessage(), 'cURL error 60') !== false) {
                Log::warning('Push notification skipped due to SSL certificate issue (development environment)', [
                    'token' => substr($fcmToken, 0, 20) . '...',
                    'title' => $title,
                    'error' => 'SSL certificate verification failed'
                ]);
                return false; // Return false but don't treat as critical error
            }
            
            Log::error('Failed to send push notification', [
                'token' => substr($fcmToken, 0, 20) . '...',
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            // Check if it's an SSL certificate error
            if (strpos($e->getMessage(), 'SSL certificate problem') !== false || 
                strpos($e->getMessage(), 'cURL error 60') !== false) {
                Log::warning('Push notification skipped due to SSL certificate issue (development environment)', [
                    'token' => substr($fcmToken, 0, 20) . '...',
                    'title' => $title,
                    'error' => 'SSL certificate verification failed'
                ]);
                return false; // Return false but don't treat as critical error
            }
            
            Log::error('Unexpected error sending push notification', [
                'token' => substr($fcmToken, 0, 20) . '...',
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToMultipleDevices(array $fcmTokens, string $title, string $body, array $data = []): array
    {
        $results = [];
        
        foreach ($fcmTokens as $token) {
            $results[$token] = $this->sendToDevice($token, $title, $body, $data);
        }
        
        return $results;
    }

    /**
     * Send notification based on notification type with appropriate formatting
     */
    public function sendNotificationByType(string $fcmToken, string $type, array $notificationData): bool
    {
        $title = $this->getNotificationTitle($type, $notificationData);
        $body = $this->getNotificationBody($type, $notificationData);
        $data = $this->getNotificationData($type, $notificationData);
        
        return $this->sendToDevice($fcmToken, $title, $body, $data);
    }

    /**
     * Get notification title based on type
     */
    private function getNotificationTitle(string $type, array $data): string
    {
        switch ($type) {
            case 'match_created':
                return 'New Match Created';
            case 'match_scheduled':
                return 'Match Scheduled';
            case 'match_result_submitted':
                return 'Match Result Submitted';
            case 'match_result_confirmed':
                return 'Match Result Confirmed';
            case 'match_forfeit':
                return 'Match Forfeit';
            case 'tournament_created':
                return 'New Tournament Available';
            case 'tournament_started':
                return 'Tournament Started';
            case 'tournament_completed':
                return 'Tournament Completed';
            case 'chat_message':
                return 'New Message';
            case 'admin_message':
                return 'Admin Announcement';
            default:
                return 'CueSports Kenya';
        }
    }

    /**
     * Get notification body based on type
     */
    private function getNotificationBody(string $type, array $data): string
    {
        switch ($type) {
            case 'match_created':
                return "You have a new match in {$data['tournament_name']} tournament";
            case 'match_scheduled':
                return "Your match has been scheduled for {$data['scheduled_date']}";
            case 'match_result_submitted':
                return "Match result submitted. Please confirm the result.";
            case 'match_result_confirmed':
                return "Match result has been confirmed. Check your tournament progress.";
            case 'match_forfeit':
                return "A match has been forfeited. Check your tournament status.";
            case 'tournament_created':
                return "A new {$data['level']} level tournament is now available for registration";
            case 'tournament_started':
                return "Tournament {$data['tournament_name']} has started. Good luck!";
            case 'tournament_completed':
                return "Tournament {$data['tournament_name']} has been completed. Check results!";
            case 'chat_message':
                return $data['message'] ?? 'You have a new message';
            case 'admin_message':
                return $data['message'] ?? 'You have a new announcement';
            default:
                return $data['message'] ?? 'You have a new notification';
        }
    }

    /**
     * Get additional data for push notification
     */
    private function getNotificationData(string $type, array $data): array
    {
        $pushData = [
            'type' => $type,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        // Add relevant IDs for navigation
        if (isset($data['match_id'])) {
            $pushData['match_id'] = (string)$data['match_id'];
        }
        if (isset($data['tournament_id'])) {
            $pushData['tournament_id'] = (string)$data['tournament_id'];
        }
        if (isset($data['chat_id'])) {
            $pushData['chat_id'] = (string)$data['chat_id'];
        }

        return $pushData;
    }

    /**
     * Validate FCM token format
     */
    public function isValidFcmToken(string $token): bool
    {
        // FCM tokens are typically 152+ characters long and contain specific patterns
        return strlen($token) > 140 && preg_match('/^[A-Za-z0-9_-]+$/', str_replace(':', '', $token));
    }
}
