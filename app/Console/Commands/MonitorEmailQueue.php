<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SimpleEmailService;

class MonitorEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:monitor {--clear-failed : Clear failed jobs}';

    /**
     * The console description of the command.
     */
    protected $description = 'Monitor email queue status and rate limits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📧 Email Queue Monitor');
        $this->info('===================');

        // Check queue status
        $this->checkQueueStatus();
        
        // Check rate limits
        $this->checkRateLimits();
        
        // Check failed jobs
        $this->checkFailedJobs();
        
        // Clear failed jobs if requested
        if ($this->option('clear-failed')) {
            $this->clearFailedJobs();
        }
    }

    private function checkQueueStatus()
    {
        $this->info("\n📊 Queue Status:");
        
        try {
            $pendingJobs = DB::table('jobs')->count();
            $this->line("Pending jobs: {$pendingJobs}");
            
            if ($pendingJobs > 0) {
                $emailJobs = DB::table('jobs')
                    ->where('payload', 'like', '%SendVerificationEmailJob%')
                    ->count();
                $this->line("Email jobs: {$emailJobs}");
                
                $oldestJob = DB::table('jobs')
                    ->orderBy('created_at')
                    ->first();
                
                if ($oldestJob) {
                    $this->line("Oldest job: " . date('Y-m-d H:i:s', $oldestJob->created_at));
                }
            }
        } catch (\Exception $e) {
            $this->error("Error checking queue status: " . $e->getMessage());
        }
    }

    private function checkRateLimits()
    {
        $this->info("\n⏱️  Email System Status:");
        
        try {
            $emailService = new SimpleEmailService();
            $status = $emailService->getEmailStatus('test@example.com');
            
            $this->line("Rate limiting: {$status['rate_limiting']}");
            $this->line("Can send emails: " . ($status['can_send_now'] ? 'Yes' : 'No'));
            $this->line("Last email sent: {$status['last_email_sent']}");
            $this->line("SMTP protection: {$status['smtp_protection']}");
            
        } catch (\Exception $e) {
            $this->error("Error checking email system: " . $e->getMessage());
        }
    }

    private function checkFailedJobs()
    {
        $this->info("\n❌ Failed Jobs:");
        
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $this->line("Total failed jobs: {$failedJobs}");
            
            if ($failedJobs > 0) {
                $emailFailures = DB::table('failed_jobs')
                    ->where('payload', 'like', '%SendVerificationEmailJob%')
                    ->count();
                $this->line("Failed email jobs: {$emailFailures}");
                
                $recentFailures = DB::table('failed_jobs')
                    ->where('failed_at', '>', now()->subHour())
                    ->count();
                $this->line("Failed in last hour: {$recentFailures}");
                
                if ($emailFailures > 0) {
                    $this->warn("⚠️  Email delivery failures detected!");
                    
                    // Show recent failure reasons
                    $recentFailedJobs = DB::table('failed_jobs')
                        ->where('payload', 'like', '%SendVerificationEmailJob%')
                        ->orderBy('failed_at', 'desc')
                        ->limit(3)
                        ->get(['exception', 'failed_at']);
                    
                    foreach ($recentFailedJobs as $job) {
                        $exception = substr($job->exception, 0, 100) . '...';
                        $this->line("  - {$job->failed_at}: {$exception}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error checking failed jobs: " . $e->getMessage());
        }
    }

    private function clearFailedJobs()
    {
        $this->info("\n🧹 Clearing Failed Jobs:");
        
        try {
            $count = DB::table('failed_jobs')->count();
            
            if ($count > 0) {
                if ($this->confirm("Clear {$count} failed jobs?")) {
                    DB::table('failed_jobs')->truncate();
                    $this->info("✅ Cleared {$count} failed jobs");
                }
            } else {
                $this->info("No failed jobs to clear");
            }
        } catch (\Exception $e) {
            $this->error("Error clearing failed jobs: " . $e->getMessage());
        }
    }
}
