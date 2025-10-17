<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimpleEmailService;
use App\Models\Verification;

class TraceEmailSending extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:trace {email} {--type=verification : Type of email (verification|password_reset)}';

    /**
     * The console description of the command.
     */
    protected $description = 'Trace email sending process step by step';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $type = $this->option('type');
        
        $this->info("🔍 TRACING EMAIL SENDING PROCESS");
        $this->info("================================");
        $this->info("Email: {$email}");
        $this->info("Type: {$type}");
        $this->info("Timestamp: " . now()->toISOString());
        $this->line("");

        // Show current email status
        $emailService = new SimpleEmailService();
        $status = $emailService->getEmailStatus($email);
        
        $this->info("📊 CURRENT EMAIL STATUS:");
        $this->line("- Emails sent this hour: {$status['emails_sent_this_hour']}/{$status['limit_per_hour']}");
        $this->line("- Remaining this hour: {$status['remaining_this_hour']}");
        $this->line("- Can send now: " . ($status['can_send_now'] ? 'YES' : 'NO'));
        $this->line("");

        if (!$status['can_send_now']) {
            $this->error("❌ Cannot send email - rate limit exceeded!");
            return;
        }

        // Generate verification code
        $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->info("🎯 TRIGGERING EMAIL SEND:");
        $this->line("Generated code: {$code}");
        $this->line("");

        // Create verification record to trigger the full flow
        if ($type === 'verification') {
            $this->info("📝 Creating verification record...");
            
            $verification = Verification::create([
                'verification_type' => Verification::TYPE_SIGN_UP,
                'code' => $code,
                'email' => $email,
                'user_id' => null,
                'expires_at' => now()->addMinutes(15),
                'is_used' => false,
                'metadata' => ['name' => 'Test User']
            ]);
            
            $this->info("✅ Verification record created (ID: {$verification->id})");
            $this->line("");
            
            $this->info("📧 Triggering sendEmail() method...");
            $result = $verification->sendEmail();
            
        } else {
            $this->info("🔐 Sending password reset email directly...");
            $result = $emailService->sendPasswordResetCode($email, $code, 'Test User');
        }

        $this->line("");
        $this->info("🏁 FINAL RESULT:");
        if ($result) {
            $this->info("✅ Email sending process completed successfully!");
        } else {
            $this->error("❌ Email sending process failed!");
        }
        
        $this->line("");
        $this->info("📋 Check the logs above for detailed step-by-step tracing.");
        $this->info("Look for log entries with step_id or verification_id to follow the flow.");
    }
}
