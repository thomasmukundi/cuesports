<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        // Check global rate limit
        if (!$this->checkGlobalRateLimit()) {
            Log::warning('Global email rate limit exceeded', [
                'email' => $email,
                'current_minute' => now()->format('Y-m-d H:i')
            ]);
            
            // Queue with longer delay when rate limited
            SendVerificationEmailJob::dispatch($email, $code, $name, 'verification')
                ->delay(rand(60, 120)); // 1-2 minute delay
            
            return true; // Still return true as it's queued
        }

        // Check per-email rate limit
        if (!$this->checkEmailRateLimit($email)) {
            Log::warning('Per-email rate limit exceeded', [
                'email' => $email,
                'limit' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR
            ]);
            return false; // Don't send if same email is being spammed
        }

        // Increment counters
        $this->incrementGlobalRateLimit();
        $this->incrementEmailRateLimit($email);

        // Dispatch to queue with small random delay
        SendVerificationEmailJob::dispatch($email, $code, $name, 'verification')
            ->delay(rand(1, 10));

        Log::info('Rate-limited verification email queued', [
            'email' => $email,
            'code' => $code
        ]);

        return true;
    }

    /**
     * Send password reset email with rate limiting
     */
    public function sendPasswordResetCode(string $email, string $code, ?string $name = null): bool
    {
        // Check global rate limit
        if (!$this->checkGlobalRateLimit()) {
            Log::warning('Global email rate limit exceeded for password reset', [
                'email' => $email,
                'current_minute' => now()->format('Y-m-d H:i')
            ]);
            
            // Queue with longer delay when rate limited
            SendVerificationEmailJob::dispatch($email, $code, $name, 'password_reset')
                ->delay(rand(60, 120)); // 1-2 minute delay
            
            return true; // Still return true as it's queued
        }

        // Check per-email rate limit
        if (!$this->checkEmailRateLimit($email)) {
            Log::warning('Per-email rate limit exceeded for password reset', [
                'email' => $email,
                'limit' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR
            ]);
            return false; // Don't send if same email is being spammed
        }

        // Increment counters
        $this->incrementGlobalRateLimit();
        $this->incrementEmailRateLimit($email);

        // Dispatch to queue with small random delay
        SendVerificationEmailJob::dispatch($email, $code, $name, 'password_reset')
            ->delay(rand(1, 10));

        Log::info('Rate-limited password reset email queued', [
            'email' => $email,
            'code' => $code
        ]);

        return true;
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
            'per_email_limit_per_hour' => self::MAX_EMAILS_PER_EMAIL_PER_HOUR
        ];
    }
}
