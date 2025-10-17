<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SimpleEmailService
{
    private const MAX_EMAILS_PER_EMAIL_PER_HOUR = 3; // Prevent spam to same email
    private const EMAIL_RATE_LIMIT_KEY = 'simple_email_limit_';

    /**
     * Send verification code email with comprehensive logging
     */
    public function sendVerificationCode(string $email, string $code, ?string $name = null): bool
    {
        $stepId = uniqid('email_');
        
        Log::info("🚀 STEP 1: Starting verification email process", [
            'step_id' => $stepId,
            'email' => $email,
            'code' => $code,
            'name' => $name,
            'timestamp' => now()->toISOString()
        ]);

        // Check per-email rate limit (prevent spam)
        if (!$this->checkEmailRateLimit($email)) {
            Log::warning("❌ STEP 2: Email rate limit exceeded", [
                'step_id' => $stepId,
                'email' => $email,
                'limit' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR,
                'reason' => 'Too many emails to same address'
            ]);
            return false;
        }

        Log::info("✅ STEP 2: Rate limit check passed", [
            'step_id' => $stepId,
            'email' => $email
        ]);

        // Increment rate limit counter
        $this->incrementEmailRateLimit($email);

        Log::info("✅ STEP 3: Rate limit counter incremented", [
            'step_id' => $stepId,
            'email' => $email
        ]);

        // Send email directly
        return $this->sendEmailDirect($stepId, $email, $code, $name, 'verification');
    }

    /**
     * Send password reset code email with comprehensive logging
     */
    public function sendPasswordResetCode(string $email, string $code, ?string $name = null): bool
    {
        $stepId = uniqid('reset_');
        
        Log::info("🚀 STEP 1: Starting password reset email process", [
            'step_id' => $stepId,
            'email' => $email,
            'code' => $code,
            'name' => $name,
            'timestamp' => now()->toISOString()
        ]);

        // Check per-email rate limit (prevent spam)
        if (!$this->checkEmailRateLimit($email)) {
            Log::warning("❌ STEP 2: Email rate limit exceeded", [
                'step_id' => $stepId,
                'email' => $email,
                'limit' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR,
                'reason' => 'Too many emails to same address'
            ]);
            return false;
        }

        Log::info("✅ STEP 2: Rate limit check passed", [
            'step_id' => $stepId,
            'email' => $email
        ]);

        // Increment rate limit counter
        $this->incrementEmailRateLimit($email);

        Log::info("✅ STEP 3: Rate limit counter incremented", [
            'step_id' => $stepId,
            'email' => $email
        ]);

        // Send email directly
        return $this->sendEmailDirect($stepId, $email, $code, $name, 'password_reset');
    }

    /**
     * Send email directly with detailed step-by-step logging
     */
    private function sendEmailDirect(string $stepId, string $email, string $code, ?string $name, string $type): bool
    {
        try {
            Log::info("📧 STEP 4: Preparing email data", [
                'step_id' => $stepId,
                'email' => $email,
                'type' => $type,
                'template_preparing' => true
            ]);

            // Prepare email data
            $data = [
                'name' => $name ?? 'User',
                'code' => $code,
                'app_name' => config('app.name', 'CueSports Kenya'),
            ];

            $template = $type === 'password_reset' ? 'emails.password-reset' : 'emails.verification-code';
            $subject = $type === 'password_reset' 
                ? 'Password Reset Code - ' . config('app.name')
                : 'Email Verification Code - ' . config('app.name');

            Log::info("✅ STEP 5: Email data prepared", [
                'step_id' => $stepId,
                'email' => $email,
                'template' => $template,
                'subject' => $subject,
                'data_keys' => array_keys($data)
            ]);

            Log::info("📤 STEP 6: Attempting to send email via Mail facade", [
                'step_id' => $stepId,
                'email' => $email,
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'sending_now' => true
            ]);

            // Add small delay to prevent SMTP connection overload
            $this->preventSmtpOverload($stepId, $email);

            // Send the email
            Mail::send($template, $data, function ($message) use ($email, $name, $subject, $stepId) {
                Log::info("📮 STEP 7: Inside Mail closure, setting recipients", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'name' => $name,
                    'subject' => $subject
                ]);
                
                $message->to($email, $name)->subject($subject);
                
                Log::info("✅ STEP 8: Mail message configured", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'message_configured' => true
                ]);
            });

            Log::info("🎉 STEP 9: Email sent successfully!", [
                'step_id' => $stepId,
                'email' => $email,
                'type' => $type,
                'method' => 'direct_smtp',
                'success' => true,
                'timestamp' => now()->toISOString()
            ]);

            return true;

        } catch (Exception $e) {
            // Check if this is an SMTP connection error that we can retry
            if (str_contains($e->getMessage(), '421') || str_contains($e->getMessage(), 'Too many concurrent')) {
                Log::warning("⚠️ STEP 6.3: SMTP connection limit hit, attempting retry", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'type' => $type,
                    'error_message' => $e->getMessage(),
                    'retry_attempt' => 1
                ]);
                
                // Wait 5 seconds and try once more
                sleep(5);
                
                try {
                    Log::info("🔄 STEP 6.4: Retrying email send after SMTP delay", [
                        'step_id' => $stepId,
                        'email' => $email,
                        'retry_delay' => 5
                    ]);
                    
                    Mail::send($template, $data, function ($message) use ($email, $name, $subject, $stepId) {
                        $message->to($email, $name)->subject($subject);
                    });
                    
                    Log::info("🎉 STEP 9: Email sent successfully on retry!", [
                        'step_id' => $stepId,
                        'email' => $email,
                        'type' => $type,
                        'method' => 'direct_smtp_retry',
                        'success' => true,
                        'timestamp' => now()->toISOString()
                    ]);
                    
                    return true;
                    
                } catch (Exception $retryException) {
                    Log::error("💥 STEP ERROR: Email retry also failed", [
                        'step_id' => $stepId,
                        'email' => $email,
                        'type' => $type,
                        'original_error' => $e->getMessage(),
                        'retry_error' => $retryException->getMessage(),
                        'timestamp' => now()->toISOString()
                    ]);
                }
            }
            
            Log::error("💥 STEP ERROR: Email sending failed", [
                'step_id' => $stepId,
                'email' => $email,
                'type' => $type,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);

            return false;
        }
    }

    /**
     * Check if per-email rate limit is exceeded
     */
    private function checkEmailRateLimit(string $email): bool
    {
        $key = self::EMAIL_RATE_LIMIT_KEY . md5($email) . ':' . now()->format('Y-m-d-H');
        $current = Cache::get($key, 0);
        
        Log::info("🔍 Rate limit check", [
            'email' => $email,
            'current_count' => $current,
            'limit' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR,
            'cache_key' => $key,
            'will_allow' => $current < self::MAX_EMAILS_PER_EMAIL_PER_HOUR
        ]);
        
        return $current < self::MAX_EMAILS_PER_EMAIL_PER_HOUR;
    }

    /**
     * Increment per-email rate limit counter
     */
    private function incrementEmailRateLimit(string $email): void
    {
        $key = self::EMAIL_RATE_LIMIT_KEY . md5($email) . ':' . now()->format('Y-m-d-H');
        $current = Cache::get($key, 0);
        $new_count = $current + 1;
        Cache::put($key, $new_count, 3600); // Store for 1 hour
        
        Log::info("📊 Rate limit incremented", [
            'email' => $email,
            'previous_count' => $current,
            'new_count' => $new_count,
            'cache_key' => $key,
            'expires_in_seconds' => 3600
        ]);
    }

    /**
     * Prevent SMTP connection overload with intelligent delays
     */
    private function preventSmtpOverload(string $stepId, string $email): void
    {
        $lastEmailKey = 'last_email_sent';
        $lastEmailTime = Cache::get($lastEmailKey, 0);
        $currentTime = time();
        $timeSinceLastEmail = $currentTime - $lastEmailTime;
        
        // If last email was sent less than 3 seconds ago, add delay
        if ($timeSinceLastEmail < 3) {
            $delaySeconds = 3 - $timeSinceLastEmail;
            
            Log::info("⏳ STEP 6.1: Adding SMTP protection delay", [
                'step_id' => $stepId,
                'email' => $email,
                'time_since_last_email' => $timeSinceLastEmail,
                'delay_seconds' => $delaySeconds,
                'reason' => 'Prevent SMTP connection overload'
            ]);
            
            sleep($delaySeconds);
            
            Log::info("✅ STEP 6.2: SMTP protection delay completed", [
                'step_id' => $stepId,
                'email' => $email,
                'delay_completed' => true
            ]);
        } else {
            Log::info("✅ STEP 6.1: No SMTP delay needed", [
                'step_id' => $stepId,
                'email' => $email,
                'time_since_last_email' => $timeSinceLastEmail,
                'reason' => 'Sufficient time gap'
            ]);
        }
        
        // Update last email time
        Cache::put($lastEmailKey, $currentTime, 300); // Store for 5 minutes
    }

    /**
     * Get email sending status for debugging
     */
    public function getEmailStatus(string $email): array
    {
        $key = self::EMAIL_RATE_LIMIT_KEY . md5($email) . ':' . now()->format('Y-m-d-H');
        $current = Cache::get($key, 0);
        
        return [
            'email' => $email,
            'emails_sent_this_hour' => $current,
            'limit_per_hour' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR,
            'remaining_this_hour' => max(0, self::MAX_EMAILS_PER_EMAIL_PER_HOUR - $current),
            'can_send_now' => $current < self::MAX_EMAILS_PER_EMAIL_PER_HOUR,
            'cache_key' => $key,
            'current_hour' => now()->format('Y-m-d H:00')
        ];
    }
}
