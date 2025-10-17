<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class BrevoEmailService
{
    private const BREVO_API_URL = 'https://api.brevo.com/v3/smtp/email';

    /**
     * Send verification code email using Brevo API
     */
    public function sendVerificationCode(string $email, string $code, ?string $name = null): bool
    {
        $stepId = uniqid('brevo_email_');
        
        Log::info("🚀 STEP 1: Starting Brevo verification email process", [
            'step_id' => $stepId,
            'email' => $email,
            'code' => $code,
            'name' => $name,
            'api_provider' => 'brevo',
            'timestamp' => now()->toISOString()
        ]);

        Log::info("✅ STEP 2: Using Brevo API - no rate limiting", [
            'step_id' => $stepId,
            'email' => $email,
            'reason' => 'API-based sending with Brevo'
        ]);

        // Send email via Brevo API
        return $this->sendEmailViaBrevoApi($stepId, $email, $code, $name, 'verification');
    }

    /**
     * Send password reset code email using Brevo API
     */
    public function sendPasswordResetCode(string $email, string $code, ?string $name = null): bool
    {
        $stepId = uniqid('brevo_reset_');
        
        Log::info("🚀 STEP 1: Starting Brevo password reset email process", [
            'step_id' => $stepId,
            'email' => $email,
            'code' => $code,
            'name' => $name,
            'api_provider' => 'brevo',
            'timestamp' => now()->toISOString()
        ]);

        Log::info("✅ STEP 2: Using Brevo API - no rate limiting", [
            'step_id' => $stepId,
            'email' => $email,
            'reason' => 'API-based sending with Brevo'
        ]);

        // Send email via Brevo API
        return $this->sendEmailViaBrevoApi($stepId, $email, $code, $name, 'password_reset');
    }

    /**
     * Send email via Brevo API with detailed logging
     */
    private function sendEmailViaBrevoApi(string $stepId, string $email, string $code, ?string $name, string $type): bool
    {
        try {
            Log::info("📧 STEP 3: Preparing Brevo API email data", [
                'step_id' => $stepId,
                'email' => $email,
                'type' => $type,
                'api_url' => self::BREVO_API_URL
            ]);

            // Prepare email content
            $emailData = $this->prepareEmailData($code, $name, $type);
            
            Log::info("✅ STEP 4: Email content prepared", [
                'step_id' => $stepId,
                'email' => $email,
                'subject' => $emailData['subject'],
                'has_html_content' => !empty($emailData['htmlContent'])
            ]);

            // Prepare Brevo API payload
            $payload = [
                'sender' => [
                    'name' => config('mail.from.name', 'CueSports Kenya'),
                    'email' => config('mail.from.address', 'mukundithomas8@gmail.com')
                ],
                'to' => [
                    [
                        'email' => $email,
                        'name' => $name ?? 'User'
                    ]
                ],
                'subject' => $emailData['subject'],
                'htmlContent' => $emailData['htmlContent']
            ];

            Log::info("📤 STEP 5: Sending email via Brevo API", [
                'step_id' => $stepId,
                'email' => $email,
                'api_url' => self::BREVO_API_URL,
                'sender_email' => $payload['sender']['email'],
                'sender_name' => $payload['sender']['name'],
                'subject' => $payload['subject']
            ]);

            // Add API protection delay
            $this->preventApiOverload($stepId, $email);

            // Send via Brevo API
            $response = Http::withHeaders([
                'api-key' => config('services.brevo.api_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post(self::BREVO_API_URL, $payload);

            Log::info("📮 STEP 6: Brevo API response received", [
                'step_id' => $stepId,
                'email' => $email,
                'status_code' => $response->status(),
                'response_successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info("🎉 STEP 7: Email sent successfully via Brevo API!", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'type' => $type,
                    'method' => 'brevo_api',
                    'message_id' => $responseData['messageId'] ?? 'unknown',
                    'success' => true,
                    'timestamp' => now()->toISOString()
                ]);

                return true;
            } else {
                $errorData = $response->json();
                
                Log::error("💥 STEP ERROR: Brevo API request failed", [
                    'step_id' => $stepId,
                    'email' => $email,
                    'type' => $type,
                    'status_code' => $response->status(),
                    'error_response' => $errorData,
                    'timestamp' => now()->toISOString()
                ]);

                return false;
            }

        } catch (Exception $e) {
            Log::error("💥 STEP ERROR: Brevo API exception", [
                'step_id' => $stepId,
                'email' => $email,
                'type' => $type,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'timestamp' => now()->toISOString()
            ]);

            return false;
        }
    }

    /**
     * Prepare email content based on type
     */
    private function prepareEmailData(string $code, ?string $name, string $type): array
    {
        $userName = $name ?? 'User';
        $appName = config('app.name', 'CueSports Kenya');

        if ($type === 'password_reset') {
            $subject = "Password Reset Code - {$appName}";
            $htmlContent = $this->getPasswordResetHtml($userName, $code, $appName);
        } else {
            $subject = "Email Verification Code - {$appName}";
            $htmlContent = $this->getVerificationHtml($userName, $code, $appName);
        }

        return [
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];
    }

    /**
     * Get verification email HTML content
     */
    private function getVerificationHtml(string $name, string $code, string $appName): string
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .code-box { background: #f8f9fa; border: 2px solid #007bff; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .verification-code { font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 3px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$appName}</h1>
                    <p>Email Verification</p>
                </div>
                <div class='content'>
                    <h2>Hello {$name}!</h2>
                    <p>Thank you for registering with {$appName}. To complete your registration, please use the verification code below:</p>
                    
                    <div class='code-box'>
                        <div class='verification-code'>{$code}</div>
                        <p style='margin: 10px 0 0 0; color: #666; font-size: 14px;'>Enter this code in the app to verify your email</p>
                    </div>
                    
                    <div class='warning'>
                        <strong>Important:</strong> This code will expire in 10 minutes for security reasons.
                    </div>
                    
                    <p>If you didn't create an account with {$appName}, you can safely ignore this email.</p>
                    <p>Welcome to the exciting world of competitive pool tournaments!</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>The {$appName} Team</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Get password reset email HTML content
     */
    private function getPasswordResetHtml(string $name, string $code, string $appName): string
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .code-box { background: #f8f9fa; border: 2px solid #dc3545; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .reset-code { font-size: 32px; font-weight: bold; color: #dc3545; letter-spacing: 3px; }
                .warning { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$appName}</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class='content'>
                    <h2>Hello {$name}!</h2>
                    <p>We received a request to reset your password for your {$appName} account. Use the code below to reset your password:</p>
                    
                    <div class='code-box'>
                        <div class='reset-code'>{$code}</div>
                        <p style='margin: 10px 0 0 0; color: #666; font-size: 14px;'>Enter this code in the app to reset your password</p>
                    </div>
                    
                    <div class='warning'>
                        <strong>Security Notice:</strong> This code will expire in 10 minutes. If you didn't request a password reset, please ignore this email.
                    </div>
                    
                    <p>For your security, never share this code with anyone.</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>The {$appName} Team</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Prevent API overload with intelligent delays
     */
    private function preventApiOverload(string $stepId, string $email): void
    {
        $lastEmailKey = 'last_brevo_email_sent';
        $lastEmailTime = Cache::get($lastEmailKey, 0);
        $currentTime = time();
        $timeSinceLastEmail = $currentTime - $lastEmailTime;
        
        // If last email was sent less than 2 seconds ago, add delay (API is faster than SMTP)
        if ($timeSinceLastEmail < 2) {
            $delaySeconds = 2 - $timeSinceLastEmail;
            
            Log::info("⏳ STEP 5.1: Adding Brevo API protection delay", [
                'step_id' => $stepId,
                'email' => $email,
                'time_since_last_email' => $timeSinceLastEmail,
                'delay_seconds' => $delaySeconds,
                'reason' => 'Prevent API rate limiting'
            ]);
            
            sleep($delaySeconds);
            
            Log::info("✅ STEP 5.2: API protection delay completed", [
                'step_id' => $stepId,
                'email' => $email,
                'delay_completed' => true
            ]);
        } else {
            Log::info("✅ STEP 5.1: No API delay needed", [
                'step_id' => $stepId,
                'email' => $email,
                'time_since_last_email' => $timeSinceLastEmail,
                'reason' => 'Sufficient time gap (2+ seconds)'
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
        $lastEmailKey = 'last_brevo_email_sent';
        $lastEmailTime = Cache::get($lastEmailKey, 0);
        $timeSinceLastEmail = time() - $lastEmailTime;
        
        return [
            'email' => $email,
            'provider' => 'brevo_api',
            'rate_limiting' => 'api_based',
            'can_send_now' => true,
            'last_email_sent' => $lastEmailTime > 0 ? date('Y-m-d H:i:s', $lastEmailTime) : 'never',
            'time_since_last_email' => $timeSinceLastEmail,
            'api_protection' => $timeSinceLastEmail < 2 ? 'will_add_delay' : 'no_delay_needed',
            'api_key_configured' => !empty(config('services.brevo.api_key')),
            'current_time' => now()->format('Y-m-d H:i:s')
        ];
    }
}
