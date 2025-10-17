<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestBasicEmail extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:test-basic {email}';

    /**
     * The console description of the command.
     */
    protected $description = 'Send a basic test email without templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("📧 SENDING BASIC TEST EMAIL");
        $this->info("==========================");
        $this->info("To: {$email}");
        $this->info("Time: " . now()->toDateTimeString());
        $this->line("");

        try {
            // Send a simple raw email
            Mail::raw('This is a test email from CueSports Kenya. If you receive this, your email configuration is working correctly. Test code: ' . rand(100000, 999999), function ($message) use ($email) {
                $message->from(config('mail.from.address'), config('mail.from.name'))
                        ->to($email)
                        ->subject('Test Email - CueSports Kenya - ' . now()->format('H:i:s'));
            });

            $this->info("✅ Basic email sent successfully!");
            $this->line("");
            
            $this->info("📋 WHAT TO CHECK:");
            $this->line("1. Check your inbox for the test email");
            $this->line("2. Check your spam/junk folder");
            $this->line("3. If you receive this email, the issue is with templates");
            $this->line("4. If you don't receive this email, the issue is with SMTP config");
            $this->line("");
            
            $this->warn("⏰ Wait 2-3 minutes for delivery");
            
        } catch (\Exception $e) {
            $this->error("❌ Basic email failed!");
            $this->error("Error: " . $e->getMessage());
            $this->line("");
            
            $this->info("🔧 This indicates SMTP configuration issues:");
            $this->line("- Check your .env MAIL_* settings");
            $this->line("- Verify SMTP credentials");
            $this->line("- Check network connectivity to SMTP server");
        }
    }
}
