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
            $credentialsEnv = config('services.firebase.credentials.file');
            $factory = new Factory();

            if (!$credentialsEnv) {
                Log::warning('Firebase credentials not configured (FIREBASE_CREDENTIALS env is empty)');
                throw new \RuntimeException('Firebase credentials missing');
            }

            // Detect if FIREBASE_CREDENTIALS is raw JSON or a file path
            $trimmed = ltrim($credentialsEnv);
            if (str_starts_with($trimmed, '{')) {
                $decoded = json_decode($credentialsEnv, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON in FIREBASE_CREDENTIALS env: ' . json_last_error_msg());
                }
                Log::info('Initializing Firebase with JSON credentials from env');
                $factory = $factory->withServiceAccount($decoded);
            } else {
                // Treat as file path
                if (!is_file($credentialsEnv)) {
                    Log::error('Firebase credentials file not found', ['path' => $credentialsEnv]);
                    throw new \RuntimeException('Firebase credentials file not found at path: ' . $credentialsEnv);
                }
                Log::info('Initializing Firebase with credentials file', ['path' => $credentialsEnv]);
                $factory = $factory->withServiceAccount($credentialsEnv);
            }

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
            
            // Logging is handled by the Notification model to avoid duplicates
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
     * Send push notification to multiple devices (legacy - use sendBulkNotifications for better performance)
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
     * Send bulk notifications efficiently (optimized for large batches)
     */
    public function sendBulkNotifications(array $userIds, string $type, array $notificationData = []): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'invalid_tokens' => 0];
        
        try {
            // Get users with valid FCM tokens in batches
            $users = \App\Models\User::whereIn('id', $userIds)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->select('id', 'fcm_token', 'name')
                ->get();
                
            if ($users->isEmpty()) {
                \Log::info("No users with FCM tokens found for bulk notification");
                return $results;
            }
            
            $title = $this->getNotificationTitle($type, $notificationData);
            $body = $this->getNotificationBody($type, $notificationData);
            $data = $this->getNotificationData($type, $notificationData);
            
            \Log::info("Starting bulk push notifications", [
                'type' => $type,
                'user_count' => $users->count(),
                'title' => $title
            ]);
            
            // Process in smaller batches to avoid memory issues
            $batchSize = 50;
            $batches = $users->chunk($batchSize);
            
            foreach ($batches as $batch) {
                $messages = [];
                
                foreach ($batch as $user) {
                    // Validate token format before sending
                    if (!$this->isValidFcmToken($user->fcm_token)) {
                        $results['invalid_tokens']++;
                        continue;
                    }
                    
                    $notification = Notification::create($title, $body);
                    $message = CloudMessage::withTarget('token', $user->fcm_token)
                        ->withNotification($notification)
                        ->withData($data);
                    
                    $messages[] = $message;
                }
                
                // Send batch if we have valid messages
                if (!empty($messages)) {
                    try {
                        $report = $this->messaging->sendAll($messages);
                        $results['sent'] += $report->successes()->count();
                        $results['failed'] += $report->failures()->count();
                        
                        // Log any failures for debugging
                        if ($report->hasFailures()) {
                            foreach ($report->failures()->getItems() as $failure) {
                                \Log::warning("Push notification failed in batch", [
                                    'error' => $failure->error()->getMessage()
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error("Batch push notification failed", [
                            'batch_size' => count($messages),
                            'error' => $e->getMessage()
                        ]);
                        $results['failed'] += count($messages);
                    }
                }
                
                // Small delay between batches to avoid rate limiting
                usleep(100000); // 0.1 second
            }
            
            \Log::info("Bulk push notifications completed", $results);
            
        } catch (\Exception $e) {
            \Log::error("Bulk notification error", [
                'error' => $e->getMessage(),
                'user_count' => count($userIds)
            ]);
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
            case 'pairing':
                // Check if this is a next round notification
                if (isset($data['opponent_name']) && strpos($data['message'] ?? '', 'advanced to the next round') !== false) {
                    return 'Next Round Match';
                }
                return 'New Match Pairing';
            case 'match_scheduled':
                return 'Match Scheduled';
            case 'match_result_submitted':
                return 'Match Result Submitted';
            case 'result_confirmation':
                return 'Match Result Confirmation';
            case 'match_result_confirmed':
                return 'Match Result Confirmed';
            case 'match_result_rejected':
            case 'match_rejected':
                return 'Match Result Rejected';
            case 'match_forfeit':
                return 'Match Forfeit';
            case 'next_round_match':
                return 'Next Round Match';
            case 'tournament_position':
                return 'Tournament Position';
            case 'tournament_created':
            case 'tournament_announcement':
                return 'New Tournament Available';
            case 'tournament_started':
                return 'Tournament Started';
            case 'tournament_completed':
                return 'Tournament Completed';
            case 'chat_message':
                return 'New Message';
            case 'other':
                // Check if this is a chat message
                if (isset($data['chat_message']) || isset($data['message_id'])) {
                    return 'New Message';
                }
                return 'Notification';
            case 'admin_message':
                // Check if this is a tournament announcement
                if (isset($data['tournament_id']) || isset($data['tournament_name'])) {
                    return 'New Tournament Available';
                }
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
            case 'pairing':
                // Check if this is a next round notification
                if (isset($data['opponent_name']) && strpos($data['message'] ?? '', 'advanced to the next round') !== false) {
                    $tournamentName = $data['tournament_name'] ?? 'tournament';
                    return "You've advanced to the next round! New match created in {$tournamentName}.";
                }
                return $data['message'] ?? "You have been paired for a new match";
            case 'match_scheduled':
                return "Your match has been scheduled for {$data['scheduled_date']}";
            case 'match_result_submitted':
                return "Match result submitted. Please confirm the result.";
            case 'result_confirmation':
                return "Your opponent has submitted match results. Please confirm or dispute.";
            case 'match_result_confirmed':
                return "Match result has been confirmed. Check your tournament progress.";
            case 'match_result_rejected':
            case 'match_rejected':
                return "Match results were rejected. Please resubmit the correct scores.";
            case 'match_forfeit':
                return "A match has been forfeited. Check your tournament status.";
            case 'next_round_match':
                $tournamentName = $data['tournament_name'] ?? ($data['tournament_id'] ? 'tournament' : 'tournament');
                return "You've advanced to the next round! New match created in {$tournamentName}.";
            case 'tournament_position':
                $position = $data['position'] ?? 'a position';
                $positionText = match($position) {
                    1 => '1st place',
                    2 => '2nd place', 
                    3 => '3rd place',
                    default => is_numeric($position) ? "{$position}th place" : $position
                };
                return "Congratulations! You finished in {$positionText}!";
            case 'tournament_created':
            case 'tournament_announcement':
                $tournamentName = $data['tournament_name'] ?? 'tournament';
                return "New tournament '{$tournamentName}' is now open for registration!";
            case 'tournament_started':
                return "Tournament {$data['tournament_name']} has started. Good luck!";
            case 'tournament_completed':
                return "Tournament {$data['tournament_name']} has been completed. Check results!";
            case 'chat_message':
                return $data['message'] ?? 'You have a new message';
            case 'other':
                // Check if this is a chat message
                if (isset($data['chat_message']) || isset($data['message_id'])) {
                    return $data['chat_message'] ?? ($data['message'] ?? 'You have a new message');
                }
                return $data['message'] ?? 'You have a new notification';
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
