<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RateLimitedEmailService;
use Illuminate\Support\Facades\Mail;

class TestEmailSending extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:test {email} {--direct : Send directly without rate limiting}';

    /**
     * The console description of the command.
     */
    protected $description = 'Test email sending functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->info("Testing email sending to: {$email}");
        $this->info("Verification code: {$code}");
        
        if ($this->option('direct')) {
            $this->testDirectSending($email, $code);
        } else {
            $this->testRateLimitedSending($email, $code);
        }
    }
    
    private function testDirectSending($email, $code)
    {
        $this->info("Testing direct email sending...");
        
        try {
            $data = [
                'name' => 'Test User',
                'code' => $code,
                'app_name' => config('app.name', 'CueSports Kenya'),
            ];

            Mail::send('emails.verification-code', $data, function ($message) use ($email) {
                $message->to($email, 'Test User')
                        ->subject('Test Verification Code - ' . config('app.name'));
            });

            $this->info("✅ Direct email sent successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ Direct email failed: " . $e->getMessage());
        }
    }
    
    private function testRateLimitedSending($email, $code)
    {
        $this->info("Testing rate-limited email sending...");
        
        try {
            $emailService = new RateLimitedEmailService();
            $result = $emailService->sendVerificationCode($email, $code, 'Test User');
            
            if ($result) {
                $this->info("✅ Rate-limited email queued successfully!");
                
                // Show rate limit status
                $status = $emailService->getRateLimitStatus();
                $this->info("Rate limit status:");
                $this->line("- Emails this minute: {$status['global_emails_this_minute']}/{$status['global_limit']}");
                $this->line("- Will be immediate: " . ($status['will_be_immediate'] ? 'Yes' : 'No'));
                
            } else {
                $this->error("❌ Rate-limited email failed!");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Rate-limited email failed: " . $e->getMessage());
        }
    }
}
