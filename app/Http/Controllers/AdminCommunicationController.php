<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tournament;
use App\Services\EmailService;
use App\Services\BrevoEmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminCommunicationController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send communication email to all players
     */
    public function sendToAllPlayers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'action_required' => 'boolean',
            'target_audience' => 'required|in:all,active,verified',
        ]);

        try {
            // Get recipients based on target audience
            $recipients = $this->getRecipients($validated['target_audience']);

            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No recipients found for the selected audience'
                ], 400);
            }

            // Use optimized bulk email method for better performance
            $results = $this->emailService->sendBulkEmailsQueued(
                $recipients,
                'admin_communication',
                [
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'action_required' => $validated['action_required'] ?? false
                ]
            );

            Log::info('Admin communication sent', [
                'subject' => $validated['subject'],
                'target_audience' => $validated['target_audience'],
                'total_recipients' => $results['total'],
                'sent' => $results['queued'],
                'failed' => $results['failed']
            ]);

            return response()->json([
                'success' => true,
                'message' => "Communication sent successfully to {$results['queued']} out of {$results['total']} recipients",
                'results' => [
                    'total' => $results['total'],
                    'sent' => $results['queued'],
                    'failed' => $results['failed']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send admin communication', [
                'subject' => $validated['subject'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send communication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send tournament announcement to all players
     */
    public function sendTournamentAnnouncement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'target_audience' => 'required|in:all,eligible,community,county,region',
        ]);

        try {
            // Get tournament details
            $tournament = \App\Models\Tournament::with(['community', 'county', 'region'])
                ->findOrFail($validated['tournament_id']);

            // Get recipients based on target audience and tournament scope
            $recipients = $this->getTournamentRecipients($validated['target_audience'], $tournament);

            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible recipients found for this tournament'
                ], 400);
            }

            // Prepare tournament data for email
            $tournamentData = [
                'tournament_name' => $tournament->name,
                'tournament_description' => $tournament->description,
                'registration_deadline' => $tournament->registration_deadline ? 
                    \Carbon\Carbon::parse($tournament->registration_deadline)->format('M j, Y g:i A') : 'TBD',
                'tournament_date' => $tournament->start_date ? 
                    \Carbon\Carbon::parse($tournament->start_date)->format('M j, Y') : null,
                'entry_fee' => $tournament->entry_fee,
                'prize_pool' => $tournament->prize_pool,
                'tournament_level' => $tournament->level,
                'max_participants' => $tournament->max_participants,
            ];

            // Send tournament emails using the same working pattern
            $results = [
                'total' => count($recipients),
                'sent' => 0,
                'failed' => 0,
                'errors' => []
            ];

            // Pre-filter recipients to only valid emails (same as admin communications)
            $validRecipients = [];
            $invalidCount = 0;
            
            foreach ($recipients as $recipient) {
                $email = $recipient['email'];
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalidCount++;
                    continue;
                }
                
                if (preg_match('/example\.com|test\.com|fake\.com|dummy\.com/', $email)) {
                    $invalidCount++;
                    continue;
                }
                
                $validRecipients[] = $recipient;
            }
            
            Log::info("🏆 Tournament email filtering complete", [
                'tournament' => $tournamentData['tournament_name'],
                'total_original' => count($recipients),
                'valid_recipients' => count($validRecipients),
                'filtered_out' => $invalidCount
            ]);
            
            $results['total'] = count($validRecipients);

            foreach ($validRecipients as $index => $recipient) {
                $email = $recipient['email'];
                $name = $recipient['name'];

                try {
                    // Use EXACT same working pattern with tournament data
                    $data = [
                        'name' => $name ?? 'User',
                        'app_name' => config('app.name', 'CueSports Kenya'),
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                        'environment' => config('app.env'),
                        'app_url' => config('app.url'),
                        // Tournament specific data with safe variable names
                        'tournament_title' => $tournamentData['tournament_name'],
                        'tournament_info' => $tournamentData['tournament_description'], // Keep original formatting
                        'registration_deadline' => $tournamentData['registration_deadline'],
                        'tournament_date' => $tournamentData['tournament_date'],
                        'entry_fee' => $tournamentData['entry_fee'],
                        'prize_pool' => $tournamentData['prize_pool'],
                        'tournament_level' => $tournamentData['tournament_level'],
                        'max_participants' => $tournamentData['max_participants'],
                    ];

                    Mail::send('emails.tournament-announcement-final', $data, function ($mailMessage) use ($email, $name, $tournamentData) {
                        $mailMessage->to($email, $name)
                                   ->subject('New Tournament: ' . $tournamentData['tournament_name'] . ' - ' . config('app.name'));
                    });

                    $results['sent']++;
                    Log::info("✅ Tournament email sent", ['email' => $email, 'index' => $index + 1]);
                    
                    // Longer delay to avoid spam detection
                    usleep(1000000); // 1 second delay

                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Error sending to {$email}: " . $e->getMessage();
                    
                    Log::error('❌ Tournament email failed', [
                        'email' => $email,
                        'error' => $e->getMessage(),
                        'index' => $index + 1
                    ]);
                    
                    // If we get spam errors, add extra delay
                    if (strpos($e->getMessage(), 'spam') !== false) {
                        Log::warning("⚠️ Spam detected in tournament email, adding extra delay");
                        usleep(2000000); // 2 second delay after spam error
                    }
                }
            }

            Log::info('Tournament announcement sent', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'target_audience' => $validated['target_audience'],
                'total_recipients' => $results['total'],
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ]);

            return response()->json([
                'success' => true,
                'message' => "Tournament announcement sent to {$results['sent']} out of {$results['total']} players",
                'results' => [
                    'total' => $results['total'],
                    'sent' => $results['sent'],
                    'failed' => $results['failed']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send tournament announcement', [
                'tournament_id' => $validated['tournament_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send tournament announcement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test communication with hardcoded valid emails
     */
    public function sendTestCommunication(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'action_required' => 'boolean',
        ]);

        try {
            // Hardcoded test recipients - known valid emails
            $recipients = [
                [
                    'email' => 'mukundithomas8@gmail.com',
                    'name' => 'Mukundi Thomas'
                ],
                [
                    'email' => 'thomasngomono90@gmail.com',
                    'name' => 'Thomas Ngomono'
                ]
            ];

            Log::info('🧪 Starting test communication', [
                'subject' => $validated['subject'],
                'recipients' => count($recipients)
            ]);

            // Test with direct email sending like the working test script
            $results = [
                'total' => count($recipients),
                'sent' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($recipients as $recipient) {
                $email = $recipient['email'];
                $name = $recipient['name'];

                // Skip invalid emails
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $results['failed']++;
                    $results['errors'][] = "Invalid email format: {$email}";
                    continue;
                }

                try {
                    // Use EXACT same data structure as working sendTestEmail
                    // CRITICAL: Rename 'message' to avoid conflict with Mail closure parameter
                    $data = [
                        'name' => $name ?? 'Test User',
                        'app_name' => config('app.name', 'CueSports Kenya'),
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                        'environment' => config('app.env'),
                        'email_subject' => $validated['subject'], // Renamed to avoid conflicts
                        'email_content' => strip_tags($validated['message']), // Strip HTML and rename
                        'action_required' => $validated['action_required'] ?? false,
                        'app_url' => config('app.url'),
                    ];

                    // EXACT same pattern as sendTestEmail - same variable names
                    Mail::send('emails.admin-communication-working', $data, function ($mailMessage) use ($email, $name) {
                        $mailMessage->to($email, $name)
                                   ->subject('Admin Communication - ' . config('app.name'));
                    });

                    $results['sent']++;
                    Log::info('✅ Direct admin email sent', ['email' => $email]);

                    // Small delay between emails
                    usleep(500000); // 0.5 second

                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Error sending to {$email}: " . $e->getMessage();
                    Log::error('❌ Direct admin email failed', [
                        'email' => $email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('🧪 Test communication completed', [
                'subject' => $validated['subject'],
                'total_recipients' => $results['total'],
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ]);

            return response()->json([
                'success' => true,
                'message' => "Test communication sent successfully to {$results['sent']} out of {$results['total']} recipients",
                'results' => [
                    'total' => $results['total'],
                    'sent' => $results['sent'],
                    'failed' => $results['failed']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('🧪 Failed to send test communication', [
                'subject' => $validated['subject'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test communication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recipients based on target audience
     */
    private function getRecipients(string $targetAudience): array
    {
        $query = User::select('email', 'name');

        switch ($targetAudience) {
            case 'all':
                // All users
                break;
            
            case 'active':
                // Users who have logged in within the last 30 days
                $query->where('updated_at', '>=', now()->subDays(30));
                break;
            
            case 'verified':
                // Users with verified emails
                $query->whereNotNull('email_verified_at');
                break;
        }

        return $query->get()->map(function ($user) {
            return [
                'email' => $user->email,
                'name' => $user->name
            ];
        })->toArray();
    }

    /**
     * Get tournament-specific recipients
     */
    private function getTournamentRecipients(string $targetAudience, $tournament): array
    {
        $query = User::select('email', 'name')->whereNotNull('email_verified_at');

        // Special tournaments and national tournaments go to all users
        if ($tournament->special || $tournament->area_scope === 'national') {
            // Send to all verified users regardless of target audience
            if ($targetAudience !== 'all') {
                // Still apply audience filters for special/national tournaments
                switch ($targetAudience) {
                    case 'eligible':
                        // All verified users are eligible for special/national tournaments
                        break;
                    case 'community':
                        $query->whereNotNull('community_id');
                        break;
                    case 'county':
                        $query->whereNotNull('county_id');
                        break;
                    case 'region':
                        $query->whereNotNull('region_id');
                        break;
                }
            }
        } else {
            // Filter based on tournament scope and area name
            switch ($targetAudience) {
                case 'all':
                    // All users, but still filter by tournament scope
                    $this->applyTournamentScopeFilter($query, $tournament);
                    break;
                
                case 'eligible':
                    // Users who can participate in this tournament
                    $this->applyTournamentScopeFilter($query, $tournament);
                    break;
                
                case 'community':
                    if ($tournament->area_scope === 'community' && $tournament->area_name) {
                        $community = \App\Models\Community::where('name', $tournament->area_name)->first();
                        if ($community) {
                            $query->where('community_id', $community->id);
                        } else {
                            return []; // No matching community
                        }
                    } else {
                        $query->whereNotNull('community_id');
                    }
                    break;
                
                case 'county':
                    if ($tournament->area_scope === 'county' && $tournament->area_name) {
                        $county = \App\Models\County::where('name', $tournament->area_name)->first();
                        if ($county) {
                            $query->where('county_id', $county->id);
                        } else {
                            return []; // No matching county
                        }
                    } else {
                        $query->whereNotNull('county_id');
                    }
                    break;
                
                case 'region':
                    if ($tournament->area_scope === 'regional' && $tournament->area_name) {
                        $region = \App\Models\Region::where('name', $tournament->area_name)->first();
                        if ($region) {
                            $query->where('region_id', $region->id);
                        } else {
                            return []; // No matching region
                        }
                    } else {
                        $query->whereNotNull('region_id');
                    }
                    break;
            }
        }

        return $query->get()->map(function ($user) {
            return [
                'email' => $user->email,
                'name' => $user->name
            ];
        })->toArray();
    }

    /**
     * Apply tournament scope filtering
     */
    private function applyTournamentScopeFilter($query, $tournament)
    {
        if ($tournament->area_scope === 'community' && $tournament->area_name) {
            $community = \App\Models\Community::where('name', $tournament->area_name)->first();
            if ($community) {
                $query->where('community_id', $community->id);
            }
        } elseif ($tournament->area_scope === 'county' && $tournament->area_name) {
            $county = \App\Models\County::where('name', $tournament->area_name)->first();
            if ($county) {
                $query->where('county_id', $county->id);
            }
        } elseif ($tournament->area_scope === 'regional' && $tournament->area_name) {
            $region = \App\Models\Region::where('name', $tournament->area_name)->first();
            if ($region) {
                $query->where('region_id', $region->id);
            }
        }
    }

    /**
     * Get communication statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'verified_users' => User::whereNotNull('email_verified_at')->count(),
                'active_users' => User::where('updated_at', '>=', now()->subDays(30))->count(),
                'users_by_level' => [
                    'community' => User::whereNotNull('community_id')->count(),
                    'county' => User::whereNotNull('county_id')->count(),
                    'region' => User::whereNotNull('region_id')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test email to verify email configuration
     */
    public function sendTestEmail(): JsonResponse
    {
        try {
            $testEmail = 'thomasngomono90@gmail.com';
            $emailService = new BrevoEmailService();
            
            Log::info('Admin test email initiated', [
                'test_email' => $testEmail,
                'admin_user' => auth()->user()->email ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);

            // Generate a test verification code
            $testCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Send test email using BrevoEmailService
            $result = $emailService->sendVerificationCode(
                $testEmail,
                $testCode,
                'Thomas (Test User)'
            );

            if ($result) {
                Log::info('Admin test email sent successfully', [
                    'test_email' => $testEmail,
                    'test_code' => $testCode,
                    'admin_user' => auth()->user()->email ?? 'unknown'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Test email sent successfully to {$testEmail}",
                    'details' => [
                        'recipient' => $testEmail,
                        'test_code' => $testCode,
                        'sent_at' => now()->format('Y-m-d H:i:s'),
                        'note' => 'Check inbox and spam folder for the test email'
                    ]
                ]);
            } else {
                Log::error('Admin test email failed to send', [
                    'test_email' => $testEmail,
                    'admin_user' => auth()->user()->email ?? 'unknown'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test email. Check system logs for details.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Admin test email exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_user' => auth()->user()->email ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test email failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
