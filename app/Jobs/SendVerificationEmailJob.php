<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $code;
    protected $name;
    protected $type;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300]; // 10s, 30s, 1m, 2m, 5m
    }

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, string $code, string $name = null, string $type = 'verification')
    {
        $this->email = $email;
        $this->code = $code;
        $this->name = $name ?? 'User';
        $this->type = $type;
        
        // Add delay to spread out email sending
        $this->delay(rand(1, 10)); // Random delay 1-10 seconds
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $data = [
                'name' => $this->name,
                'code' => $this->code,
                'app_name' => config('app.name', 'CueSports Kenya'),
            ];

            $template = $this->type === 'password_reset' ? 'emails.password-reset' : 'emails.verification-code';
            $subject = $this->type === 'password_reset' 
                ? 'Password Reset Code - ' . config('app.name')
                : 'Email Verification Code - ' . config('app.name');

            Mail::send($template, $data, function ($message) use ($subject) {
                $message->to($this->email, $this->name)
                        ->subject($subject);
            });

            Log::info('Queued verification email sent successfully', [
                'email' => $this->email,
                'type' => $this->type,
                'attempt' => $this->attempts()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send queued verification email', [
                'email' => $this->email,
                'type' => $this->type,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            // Check if this is an SMTP connection error
            if (str_contains($e->getMessage(), '421') || str_contains($e->getMessage(), 'Too many concurrent')) {
                // Add extra delay for SMTP connection errors
                $this->release(rand(30, 60)); // Release back to queue with 30-60s delay
                return;
            }

            // For other errors, let the default retry mechanism handle it
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Verification email job failed permanently', [
            'email' => $this->email,
            'type' => $this->type,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);
    }
}
