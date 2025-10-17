<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SimpleEmailService
{

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

        Log::info("✅ STEP 2: No rate limiting - proceeding directly to send", [
            'step_id' => $stepId,
            'email' => $email,
            'reason' => 'Rate limiting removed for better user experience'
        ]);

        // Send email directly without rate limiting
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

        Log::info("✅ STEP 2: No rate limiting - proceeding directly to send", [
            'step_id' => $stepId,
            'email' => $email,
            'reason' => 'Rate limiting removed for better user experience'
        ]);

        // Send email directly without rate limiting
        return $this->sendEmailDirect($stepId, $email, $code, $name, 'password_reset');
    }

    /**
     * Send email directly with detailed step-by-step logging
     */
    private function sendEmailDirect(string $stepId, string $email, string $code, ?string $name, string $type): bool
    {
        try {
            Log::info("📧 STEP 3: Preparing email data", [
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

            Log::info("✅ STEP 4: Email data prepared", [
                'step_id' => $stepId,
                'email' => $email,
                'template' => $template,
                'subject' => $subject,
                'data_keys' => array_keys($data)
            ]);

            Log::info("📤 STEP 5: Attempting to send email via Mail facade", [
                'step_id' => $stepId,
                'email' => $email,
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
                'template' => $template,
                'subject' => $subject,
                'sending_now' => true
            ]);

            // Add small delay to prevent SMTP connection overload
            $this->preventSmtpOverload($stepId, $email);

            // Check if template exists
            $templatePath = resource_path("views/{$template}.blade.php");
            Log::info("📄 STEP 5.5: Email template check", [
                'step_id' => $stepId,
                'template' => $template,
                'template_path' => $templatePath,
                'template_exists' => file_exists($templatePath)
            ]);

            // Send the email
            Mail::send($template, $data, function ($message) use ($email, $name, $subject, $stepId) {
                Log::info("📮 STEP 6: Inside Mail closure, setting recipients", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'name' => $name,
                    'subject' => $subject,
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name')
                ]);
                
                // Set from address explicitly
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($email, $name)->subject($subject);
                
                Log::info("✅ STEP 7: Mail message configured", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'message_configured' => true,
                    'message_to' => $email,
                    'message_subject' => $subject
                ]);
            });

            Log::info("🎉 STEP 8: Email sent successfully!", [
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
                Log::warning("⚠️ STEP 5.3: SMTP connection limit hit, attempting retry", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'type' => $type,
                    'error_message' => $e->getMessage(),
                    'retry_attempt' => 1
                ]);
                
                // Wait longer for SMTP connection limits (15-30 seconds)
                $retryDelay = rand(15, 30);
                sleep($retryDelay);
                
                try {
                    Log::info("🔄 STEP 5.4: Retrying email send after extended SMTP delay", [
                        'step_id' => $stepId,
                        'email' => $email,
                        'retry_delay' => $retryDelay,
                        'reason' => 'Extended delay for SMTP connection limit'
                    ]);
                    
                    Mail::send($template, $data, function ($message) use ($email, $name, $subject, $stepId) {
                        $message->to($email, $name)->subject($subject);
                    });
                    
                    Log::info("🎉 STEP 8: Email sent successfully on retry!", [
                        'step_id' => $stepId,
                        'email' => $email,
                        'type' => $type,
                        'method' => 'direct_smtp_retry',
                        'success' => true,
                        'retry_delay_used' => $retryDelay,
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
     * Prevent SMTP connection overload with intelligent delays
     */
    private function preventSmtpOverload(string $stepId, string $email): void
    {
        $lastEmailKey = 'last_email_sent';
        $lastEmailTime = Cache::get($lastEmailKey, 0);
        $currentTime = time();
        $timeSinceLastEmail = $currentTime - $lastEmailTime;
        
        // If last email was sent less than 10 seconds ago, add delay
        if ($timeSinceLastEmail < 10) {
            $delaySeconds = 10 - $timeSinceLastEmail;
            
            Log::info("⏳ STEP 5.1: Adding SMTP protection delay", [
                'step_id' => $stepId,
                'email' => $email,
                'time_since_last_email' => $timeSinceLastEmail,
                'delay_seconds' => $delaySeconds,
                'reason' => 'Prevent SMTP connection overload'
            ]);
            
            sleep($delaySeconds);
            
            Log::info("✅ STEP 5.2: SMTP protection delay completed", [
                'step_id' => $stepId,
                'email' => $email,
                'delay_completed' => true
            ]);
        } else {
            Log::info("✅ STEP 5.1: No SMTP delay needed", [
                'step_id' => $stepId,
                'email' => $email,
                'time_since_last_email' => $timeSinceLastEmail,
                'reason' => 'Sufficient time gap (10+ seconds)'
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
        $lastEmailKey = 'last_email_sent';
        $lastEmailTime = Cache::get($lastEmailKey, 0);
        $timeSinceLastEmail = time() - $lastEmailTime;
        
        return [
            'email' => $email,
            'rate_limiting' => 'disabled',
            'can_send_now' => true,
            'last_email_sent' => $lastEmailTime > 0 ? date('Y-m-d H:i:s', $lastEmailTime) : 'never',
            'time_since_last_email' => $timeSinceLastEmail,
            'smtp_protection' => $timeSinceLastEmail < 10 ? 'will_add_delay' : 'no_delay_needed',
            'current_time' => now()->format('Y-m-d H:i:s')
        ];
    }
}
