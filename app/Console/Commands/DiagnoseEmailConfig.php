<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class DiagnoseEmailConfig extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:diagnose {email}';

    /**
     * The console description of the command.
     */
    protected $description = 'Diagnose email configuration and test delivery';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("🔍 EMAIL CONFIGURATION DIAGNOSIS");
        $this->info("================================");
        $this->line("");

        // Check mail configuration
        $this->info("📧 MAIL CONFIGURATION:");
        $this->line("- Driver: " . config('mail.default'));
        $this->line("- Host: " . config('mail.mailers.smtp.host'));
        $this->line("- Port: " . config('mail.mailers.smtp.port'));
        $this->line("- Username: " . config('mail.mailers.smtp.username'));
        $this->line("- Encryption: " . config('mail.mailers.smtp.encryption'));
        $this->line("- From Address: " . config('mail.from.address'));
        $this->line("- From Name: " . config('mail.from.name'));
        $this->line("");

        // Check if templates exist
        $this->info("📄 EMAIL TEMPLATES:");
        $verificationTemplate = resource_path('views/emails/verification-code.blade.php');
        $passwordResetTemplate = resource_path('views/emails/password-reset.blade.php');
        
        $this->line("- Verification template: " . ($this->fileExists($verificationTemplate) ? '✅ EXISTS' : '❌ MISSING'));
        $this->line("- Password reset template: " . ($this->fileExists($passwordResetTemplate) ? '✅ EXISTS' : '❌ MISSING'));
        $this->line("");

        // Test basic email sending
        $this->info("📤 TESTING EMAIL DELIVERY:");
        
        try {
            $testData = [
                'name' => 'Test User',
                'code' => '123456',
                'app_name' => config('app.name', 'CueSports Kenya'),
            ];

            $this->line("Sending test email to: {$email}");
            
            Mail::send('emails.verification-code', $testData, function ($message) use ($email) {
                $message->from(config('mail.from.address'), config('mail.from.name'))
                        ->to($email, 'Test User')
                        ->subject('Test Email - ' . config('app.name'));
            });

            $this->info("✅ Test email sent successfully!");
            $this->line("");
            
            $this->info("📋 TROUBLESHOOTING CHECKLIST:");
            $this->line("1. Check your email inbox AND spam folder");
            $this->line("2. Verify SMTP credentials are correct");
            $this->line("3. Check if your SMTP provider requires app passwords");
            $this->line("4. Verify your domain's SPF/DKIM records");
            $this->line("5. Check SMTP provider logs for delivery status");
            $this->line("");
            
            $this->warn("⚠️  If email doesn't arrive:");
            $this->line("- SMTP sent successfully but email not delivered");
            $this->line("- This indicates SMTP provider or authentication issues");
            $this->line("- Check your SMTP provider's delivery logs");
            
        } catch (\Exception $e) {
            $this->error("❌ Test email failed!");
            $this->error("Error: " . $e->getMessage());
            $this->line("");
            
            $this->warn("🔧 COMMON FIXES:");
            $this->line("1. Check SMTP credentials in .env file");
            $this->line("2. Verify SMTP host and port settings");
            $this->line("3. Check if 2FA requires app password");
            $this->line("4. Verify firewall allows SMTP connections");
        }
    }
    
    private function fileExists($path)
    {
        return file_exists($path);
    }
}
