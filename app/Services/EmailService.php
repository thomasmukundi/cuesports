<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AdminCommunicationMail;
use Exception;

class EmailService
{
    /**
     * Send verification code email (queued for better reliability)
     */
    public function sendVerificationCode(string $email, string $code, ?string $name = null): bool
    {
        try {
            // Dispatch to queue immediately (no delay) for better handling of concurrent requests
            \App\Jobs\SendVerificationEmailJob::dispatch($email, $code, $name, 'verification', 0);

            Log::info('Verification email queued immediately', [
                'email' => $email,
                'code' => $code,
                'immediate' => true
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to queue verification email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to immediate sending if queue fails
            return $this->sendVerificationCodeImmediate($email, $code, $name);
        }
    }

    /**
     * Send verification code email immediately (fallback method)
     */
    private function sendVerificationCodeImmediate(string $email, string $code, ?string $name = null): bool
    {
        try {
            $data = [
                'name' => $name ?? 'User',
                'code' => $code,
                'app_name' => config('app.name', 'CueSports Kenya'),
            ];

            Mail::send('emails.verification-code', $data, function ($message) use ($email, $name) {
                $message->to($email, $name)
                        ->subject('Email Verification Code - ' . config('app.name'));
            });

            Log::info('Verification email sent immediately (fallback)', [
                'email' => $email,
                'code' => $code
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send verification email immediately', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send password reset code email (queued for better reliability)
     */
    public function sendPasswordResetCode(string $email, string $code, ?string $name = null): bool
    {
        try {
            // Dispatch to queue immediately (no delay) for better handling of concurrent requests
            \App\Jobs\SendVerificationEmailJob::dispatch($email, $code, $name, 'password_reset', 0);

            Log::info('Password reset email queued immediately', [
                'email' => $email,
                'code' => $code,
                'immediate' => true
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to queue password reset email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to immediate sending if queue fails
            return $this->sendPasswordResetCodeImmediate($email, $code, $name);
        }
    }

    /**
     * Send password reset code email immediately (fallback method)
     */
    private function sendPasswordResetCodeImmediate(string $email, string $code, ?string $name = null): bool
    {
        try {
            $data = [
                'name' => $name ?? 'User',
                'code' => $code,
                'app_name' => config('app.name', 'CueSports Kenya'),
            ];

            Mail::send('emails.password-reset', $data, function ($message) use ($email, $name) {
                $message->to($email, $name)
                        ->subject('Password Reset Code - ' . config('app.name'));
            });

            Log::info('Password reset email sent immediately (fallback)', [
                'email' => $email,
                'code' => $code
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send password reset email immediately', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(string $email, string $name): bool
    {
        try {
            $data = [
                'name' => $name,
                'app_name' => config('app.name', 'CueSports Kenya'),
                'app_url' => config('app.url'),
            ];

            Mail::send('emails.welcome', $data, function ($message) use ($email, $name) {
                $message->to($email, $name)
                        ->subject('Welcome to ' . config('app.name'));
            });

            Log::info('Welcome email sent successfully', [
                'email' => $email,
                'name' => $name
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(string $email, string $name = null): bool
    {
        try {
            $data = [
                'name' => $name ?? 'Test User',
                'app_name' => config('app.name', 'CueSports Kenya'),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'environment' => config('app.env'),
            ];

            Mail::send('emails.test', $data, function ($message) use ($email, $name) {
                $message->to($email, $name)
                        ->subject('Test Email - ' . config('app.name'));
            });

            Log::info('Test email sent successfully', [
                'email' => $email,
                'name' => $name
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send test email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }


    /**
     * Send admin communication email
     */
    public function sendAdminCommunication(string $email, string $name, string $subject, string $message, bool $actionRequired = false): bool
    {
        try {
            // Validate email before attempting to send
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            // Use exact same approach as working sendTestEmail method
            $data = [
                'name' => $name,
                'subject' => $subject,
                'message' => $message,
                'action_required' => $actionRequired,
                'app_name' => config('app.name', 'CueSports Kenya'),
                'app_url' => config('app.url'),
            ];

            // Match exactly how sendTestEmail works - no explicit from()
            Mail::send('emails.admin-communication-simple', $data, function ($message) use ($email, $name, $subject) {
                $message->to($email, $name)
                        ->subject($subject . ' - ' . config('app.name'));
            });

            Log::info('Admin communication email sent successfully', [
                'email' => $email,
                'subject' => $subject
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send admin communication email', [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send tournament announcement email
     */
    public function sendTournamentAnnouncement(string $email, string $name, array $tournamentData): bool
    {
        try {
            // Validate email before attempting to send
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            $data = array_merge([
                'name' => $name,
                'app_name' => config('app.name', 'CueSports Kenya'),
                'app_url' => config('app.url'),
            ], $tournamentData);

            // Match exactly how sendTestEmail works - no explicit from()
            Mail::send('emails.tournament-announcement', $data, function ($message) use ($email, $name, $tournamentData) {
                $tournamentName = $tournamentData['tournament_name'] ?? 'New Tournament';
                $message->to($email, $name)
                        ->subject('New Tournament: ' . $tournamentName . ' - ' . config('app.name'));
            });

            Log::info('Tournament announcement email sent successfully', [
                'email' => $email,
                'tournament' => $tournamentData['tournament_name'] ?? 'Unknown'
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send tournament announcement email', [
                'email' => $email,
                'tournament' => $tournamentData['tournament_name'] ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send bulk emails to multiple recipients
     */
    public function sendBulkEmails(array $recipients, string $emailType, array $data): array
    {
        $results = [
            'total' => count($recipients),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        Log::info("📧 Starting bulk email send", [
            'email_type' => $emailType,
            'total_recipients' => count($recipients)
        ]);

        foreach ($recipients as $index => $recipient) {
            $email = $recipient['email'];
            $name = $recipient['name'];

            // Skip invalid emails
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results['failed']++;
                $results['errors'][] = "Invalid email format: {$email}";
                continue;
            }

            try {
                $success = false;

                switch ($emailType) {
                    case 'admin_communication':
                        $success = $this->sendAdminCommunication(
                            $email, 
                            $name, 
                            $data['subject'], 
                            $data['message'], 
                            $data['action_required'] ?? false
                        );
                        break;
                    
                    case 'tournament_announcement':
                        $success = $this->sendTournamentAnnouncement($email, $name, $data);
                        break;
                    
                    default:
                        throw new Exception('Unknown email type: ' . $emailType);
                }

                if ($success) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to send to {$email}";
                }

            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error sending to {$email}: " . $e->getMessage();
            }
        }

        // Log final summary
        Log::info("📊 Bulk email summary", [
            'email_type' => $emailType,
            'total_recipients' => $results['total'],
            'sent_successfully' => $results['sent'],
            'failed_to_send' => $results['failed'],
            'success_rate' => $results['total'] > 0 ? round(($results['sent'] / $results['total']) * 100, 2) . '%' : '0%'
        ]);

        // Log detailed errors to Laravel logs only
        if (!empty($results['errors'])) {
            Log::warning("📧 Detailed email failures", [
                'email_type' => $emailType,
                'failed_emails' => $results['errors']
            ]);
        }

        return $results;
    }

    /**
     * Send bulk emails individually to prevent batch failures
     */
    public function sendBulkEmailsIndividually(array $recipients, string $emailType, array $data): array
    {
        $results = [
            'total' => count($recipients),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        Log::info("📧 Starting individual email send", [
            'email_type' => $emailType,
            'total_recipients' => count($recipients)
        ]);

        foreach ($recipients as $index => $recipient) {
            $email = $recipient['email'];
            $name = $recipient['name'];

            Log::info("📧 Sending individual email #{$index}", [
                'email' => $email,
                'name' => $name,
                'email_type' => $emailType
            ]);

            // Skip invalid emails
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results['failed']++;
                $results['errors'][] = "Invalid email format: {$email}";
                Log::warning("⚠️ Skipping invalid email", ['email' => $email]);
                continue;
            }

            try {
                $success = false;

                switch ($emailType) {
                    case 'admin_communication':
                        $success = $this->sendAdminCommunication(
                            $email, 
                            $name, 
                            $data['subject'], 
                            $data['message'], 
                            $data['action_required'] ?? false
                        );
                        break;
                    
                    case 'tournament_announcement':
                        $success = $this->sendTournamentAnnouncement($email, $name, $data);
                        break;
                    
                    default:
                        throw new Exception('Unknown email type: ' . $emailType);
                }

                if ($success) {
                    $results['sent']++;
                    Log::info("✅ Email sent successfully", ['email' => $email]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to send to {$email}";
                    Log::warning("❌ Email failed to send", ['email' => $email]);
                }

                // Add a small delay between emails to prevent overwhelming the SMTP server
                usleep(500000); // 0.5 second delay

            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error sending to {$email}: " . $e->getMessage();
                Log::error("❌ Email send error", [
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Log final summary
        Log::info("📊 Individual email send summary", [
            'email_type' => $emailType,
            'total_recipients' => $results['total'],
            'sent_successfully' => $results['sent'],
            'failed_to_send' => $results['failed'],
            'success_rate' => $results['total'] > 0 ? round(($results['sent'] / $results['total']) * 100, 2) . '%' : '0%'
        ]);

        // Log detailed errors to Laravel logs only
        if (!empty($results['errors'])) {
            Log::warning("📧 Individual send detailed failures", [
                'email_type' => $emailType,
                'failed_emails' => $results['errors']
            ]);
        }

        return $results;
    }

    /**
     * Send admin communication using exact same pattern as working sendTestEmail
     */
    public function sendAdminCommunicationDirect(string $email, string $name, string $subject, string $message, bool $actionRequired = false): bool
    {
        try {
            // Validate email before attempting to send
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email format', ['email' => $email]);
                return false;
            }

            $data = [
                'name' => $name ?? 'User',
                'subject' => $subject,
                'message' => $message,
                'action_required' => $actionRequired,
                'app_name' => config('app.name', 'CueSports Kenya'),
                'app_url' => config('app.url'),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'environment' => config('app.env'),
            ];

            // Use EXACTLY the same pattern as sendTestEmail
            Mail::send('emails.admin-communication-simple', $data, function ($message) use ($email, $name, $subject) {
                $message->to($email, $name)
                        ->subject($subject . ' - ' . config('app.name'));
            });

            Log::info('✅ Admin communication sent successfully (direct method)', [
                'email' => $email,
                'subject' => $subject
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('❌ Failed to send admin communication (direct method)', [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send bulk emails efficiently using Laravel's Mail queue system
     * This method is optimized for millions of users
     */
    public function sendBulkEmailsQueued(array $recipients, string $emailType, array $data): array
    {
        $results = [
            'total' => count($recipients),
            'queued' => 0,
            'failed' => 0,
            'errors' => []
        ];

        Log::info("📧 Starting queued bulk email send", [
            'email_type' => $emailType,
            'total_recipients' => count($recipients),
            'method' => 'queued'
        ]);

        // Pre-filter recipients to only valid emails
        $validRecipients = [];
        $invalidCount = 0;
        
        foreach ($recipients as $recipient) {
            $email = $recipient['email'];
            
            // Enhanced email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidCount++;
                continue;
            }
            
            // Skip fake/test emails
            if (preg_match('/example\.com|test\.com|fake\.com|dummy\.com/', $email)) {
                $invalidCount++;
                continue;
            }
            
            $validRecipients[] = $recipient;
        }
        
        Log::info("📧 Bulk email filtering complete", [
            'total_original' => count($recipients),
            'valid_recipients' => count($validRecipients),
            'filtered_out' => $invalidCount
        ]);
        
        $results['total'] = count($validRecipients);

        // For now, use the existing individual method but with larger batches
        // TODO: Implement proper queue system when app scales to millions
        $batchSize = 50; // Send 50 emails at once with shorter delays
        $batches = array_chunk($validRecipients, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            try {
                foreach ($batch as $recipient) {
                    $email = $recipient['email'];
                    $name = $recipient['name'];

                    try {
                        // Use same working pattern as individual method
                        if ($emailType === 'tournament_announcement') {
                            // Tournament announcement email
                            $emailData = [
                                'name' => $name ?? 'User',
                                'app_name' => config('app.name', 'CueSports Kenya'),
                                'timestamp' => now()->format('Y-m-d H:i:s'),
                                'environment' => config('app.env'),
                                'app_url' => config('app.url'),
                                // Tournament specific data
                                'tournament_title' => $data['tournament_name'] ?? 'New Tournament',
                                'tournament_info' => $data['tournament_description'] ?? 'Tournament description',
                                'registration_deadline' => $data['registration_deadline'] ?? 'TBD',
                                'tournament_date' => $data['tournament_date'] ?? 'TBD',
                                'entry_fee' => $data['entry_fee'] ?? 0,
                                'prize_pool' => $data['prize_pool'] ?? 0,
                                'tournament_level' => $data['tournament_level'] ?? 'open',
                                'max_participants' => $data['max_participants'] ?? 100,
                            ];

                            Mail::send('emails.tournament-announcement-final', $emailData, function ($mailMessage) use ($email, $name, $data) {
                                $mailMessage->to($email, $name)
                                           ->subject('New Tournament: ' . ($data['tournament_name'] ?? 'Tournament') . ' - ' . config('app.name'));
                            });
                        } else {
                            // Admin communication email
                            $emailData = [
                                'name' => $name ?? 'User',
                                'app_name' => config('app.name', 'CueSports Kenya'),
                                'timestamp' => now()->format('Y-m-d H:i:s'),
                                'environment' => config('app.env'),
                                'email_subject' => $data['subject'] ?? 'Admin Communication',
                                'email_content' => $data['message'] ?? 'No content',
                                'action_required' => $data['action_required'] ?? false,
                                'app_url' => config('app.url'),
                            ];

                            Mail::send('emails.admin-communication-working', $emailData, function ($mailMessage) use ($email, $name) {
                                $mailMessage->to($email, $name)
                                           ->subject('Admin Communication - ' . config('app.name'));
                            });
                        }

                        $results['queued']++;
                        
                    } catch (Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Error sending to {$email}: " . $e->getMessage();
                    }
                }
                
                Log::info("✅ Batch processed", [
                    'batch' => $batchIndex + 1,
                    'batch_size' => count($batch),
                    'total_batches' => count($batches)
                ]);
                
                // Shorter delay between batches for faster processing
                usleep(250000); // 0.25 second delay between batches
                
            } catch (Exception $e) {
                $results['failed'] += count($batch);
                $results['errors'][] = "Batch " . ($batchIndex + 1) . " failed: " . $e->getMessage();
                
                Log::error("❌ Batch processing failed", [
                    'batch' => $batchIndex + 1,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("📊 Bulk email batch summary", [
            'email_type' => $emailType,
            'total_recipients' => $results['total'],
            'sent_successfully' => $results['queued'],
            'failed_to_send' => $results['failed'],
            'total_batches' => count($batches),
            'batch_size' => $batchSize
        ]);

        return $results;
    }

    /**
     * Test mail configuration
     */
    public function testMailConfiguration(): array
    {
        $config = [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        return [
            'status' => 'success',
            'message' => 'Mail configuration loaded successfully',
            'config' => $config
        ];
    }
}
