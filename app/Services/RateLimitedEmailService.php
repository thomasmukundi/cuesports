<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendVerificationEmailJob;

class RateLimitedEmailService
{
    private const MAX_EMAILS_PER_MINUTE = 30; // Adjust based on your SMTP provider limits
    private const MAX_EMAILS_PER_EMAIL_PER_HOUR = 3; // Prevent spam to same email
    private const RATE_LIMIT_KEY = 'email_rate_limit';
    private const EMAIL_RATE_LIMIT_KEY = 'email_rate_limit_';

    /**
     * Send verification email with rate limiting
     */
    public function sendVerificationCode(string $email, string $code, ?string $name = null): bool
    {
        // Check per-email rate limit first (prevent spam)
        if (!$this->checkEmailRateLimit($email)) {
            Log::warning('Per-email rate limit exceeded', [
                'email' => $email,
                'limit' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR
            ]);
            return false; // Don't send if same email is being spammed
        }

        // Check global rate limit and determine delay
        $delaySeconds = 0;
        if (!$this->checkGlobalRateLimit()) {
            Log::warning('Global email rate limit exceeded', [
                'email' => $email,
                'current_minute' => now()->format('Y-m-d H:i')
            ]);
            
            // Add delay when rate limited
            $delaySeconds = rand(30, 90); // 30-90 second delay when rate limited
        } else {
            // Check current load to determine if we need a small delay
            $currentLoad = $this->getCurrentLoad();
            if ($currentLoad > 20) { // If more than 20 emails this minute
                $delaySeconds = rand(1, 5); // Small delay to spread load
            }
            // Otherwise, send immediately (delaySeconds = 0)
        }

        // Increment counters
        $this->incrementGlobalRateLimit();
        $this->incrementEmailRateLimit($email);

        // Try to dispatch to queue, but fallback to immediate sending if queue isn't working
        try {
            if ($delaySeconds === 0) {
                // For immediate emails, try queue first but fallback quickly
                SendVerificationEmailJob::dispatch($email, $code, $name, 'verification', $delaySeconds);
                
                // Check if queue is working by testing job count
                $this->verifyQueueIsWorking($email, $code, $name, 'verification');
            } else {
                // For delayed emails, always use queue
                SendVerificationEmailJob::dispatch($email, $code, $name, 'verification', $delaySeconds);
            }

            Log::info('Rate-limited verification email queued', [
                'email' => $email,
                'code' => $code,
                'delay_seconds' => $delaySeconds,
                'immediate' => $delaySeconds === 0
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue verification email, sending immediately', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to immediate sending
            return $this->sendImmediately($email, $code, $name, 'verification');
        }
    }

    /**
     * Send password reset email with rate limiting
     */
    public function sendPasswordResetCode(string $email, string $code, ?string $name = null): bool
    {
        // Check per-email rate limit first (prevent spam)
        if (!$this->checkEmailRateLimit($email)) {
            Log::warning('Per-email rate limit exceeded for password reset', [
                'email' => $email,
                'limit' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR
            ]);
            return false; // Don't send if same email is being spammed
        }

        // Check global rate limit and determine delay
        $delaySeconds = 0;
        if (!$this->checkGlobalRateLimit()) {
            Log::warning('Global email rate limit exceeded for password reset', [
                'email' => $email,
                'current_minute' => now()->format('Y-m-d H:i')
            ]);
            
            // Add delay when rate limited
            $delaySeconds = rand(30, 90); // 30-90 second delay when rate limited
        } else {
            // Check current load to determine if we need a small delay
            $currentLoad = $this->getCurrentLoad();
            if ($currentLoad > 20) { // If more than 20 emails this minute
                $delaySeconds = rand(1, 5); // Small delay to spread load
            }
            // Otherwise, send immediately (delaySeconds = 0)
        }

        // Increment counters
        $this->incrementGlobalRateLimit();
        $this->incrementEmailRateLimit($email);

        // Try to dispatch to queue, but fallback to immediate sending if queue isn't working
        try {
            if ($delaySeconds === 0) {
                // For immediate emails, try queue first but fallback quickly
                SendVerificationEmailJob::dispatch($email, $code, $name, 'password_reset', $delaySeconds);
                
                // Check if queue is working by testing job count
                $this->verifyQueueIsWorking($email, $code, $name, 'password_reset');
            } else {
                // For delayed emails, always use queue
                SendVerificationEmailJob::dispatch($email, $code, $name, 'password_reset', $delaySeconds);
            }

            Log::info('Rate-limited password reset email queued', [
                'email' => $email,
                'code' => $code,
                'delay_seconds' => $delaySeconds,
                'immediate' => $delaySeconds === 0
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue password reset email, sending immediately', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to immediate sending
            return $this->sendImmediately($email, $code, $name, 'password_reset');
        }
    }

    /**
     * Check if global rate limit is exceeded
     */
    private function checkGlobalRateLimit(): bool
    {
        $key = self::RATE_LIMIT_KEY . ':' . now()->format('Y-m-d-H-i');
        $current = Cache::get($key, 0);
        
        return $current < self::MAX_EMAILS_PER_MINUTE;
    }

    /**
     * Increment global rate limit counter
     */
    private function incrementGlobalRateLimit(): void
    {
        $key = self::RATE_LIMIT_KEY . ':' . now()->format('Y-m-d-H-i');
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, 120); // Store for 2 minutes
    }

    /**
     * Check if per-email rate limit is exceeded
     */
    private function checkEmailRateLimit(string $email): bool
    {
        $key = self::EMAIL_RATE_LIMIT_KEY . md5($email) . ':' . now()->format('Y-m-d-H');
        $current = Cache::get($key, 0);
        
        return $current < self::MAX_EMAILS_PER_EMAIL_PER_HOUR;
    }

    /**
     * Increment per-email rate limit counter
     */
    private function incrementEmailRateLimit(string $email): void
    {
        $key = self::EMAIL_RATE_LIMIT_KEY . md5($email) . ':' . now()->format('Y-m-d-H');
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, 3600); // Store for 1 hour
    }

    /**
     * Get current load (emails sent this minute)
     */
    private function getCurrentLoad(): int
    {
        $key = self::RATE_LIMIT_KEY . ':' . now()->format('Y-m-d-H-i');
        return Cache::get($key, 0);
    }

    /**
     * Verify queue is working and fallback to immediate sending if not
     */
    private function verifyQueueIsWorking(string $email, string $code, ?string $name, string $type): void
    {
        // For immediate emails on production, send directly to avoid queue delays
        if (app()->environment('production')) {
            Log::info('Production environment detected, sending email immediately to ensure delivery', [
                'email' => $email,
                'type' => $type
            ]);
            
            // Send immediately in production to ensure reliability
            $this->sendImmediately($email, $code, $name, $type);
        }
    }

    /**
     * Send email immediately without queue
     */
    private function sendImmediately(string $email, string $code, ?string $name, string $type): bool
    {
        try {
            $data = [
                'name' => $name ?? 'User',
                'code' => $code,
                'app_name' => config('app.name', 'CueSports Kenya'),
            ];

            $template = $type === 'password_reset' ? 'emails.password-reset' : 'emails.verification-code';
            $subject = $type === 'password_reset' 
                ? 'Password Reset Code - ' . config('app.name')
                : 'Email Verification Code - ' . config('app.name');

            \Mail::send($template, $data, function ($message) use ($email, $name, $subject) {
                $message->to($email, $name)->subject($subject);
            });

            Log::info('Email sent immediately (bypassed queue)', [
                'email' => $email,
                'type' => $type,
                'method' => 'immediate'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email immediately', [
                'email' => $email,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get current rate limit status
     */
    public function getRateLimitStatus(): array
    {
        $globalKey = self::RATE_LIMIT_KEY . ':' . now()->format('Y-m-d-H-i');
        $globalCount = Cache::get($globalKey, 0);
        
        return [
            'global_emails_this_minute' => $globalCount,
            'global_limit' => self::MAX_EMAILS_PER_MINUTE,
            'global_remaining' => max(0, self::MAX_EMAILS_PER_MINUTE - $globalCount),
            'per_email_limit_per_hour' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR,
            'current_load' => $globalCount,
            'will_be_immediate' => $globalCount <= 20 // Indicates if next email will be immediate
        ];
    }
}
