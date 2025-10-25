<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TournamentController as AdminTournamentController;
use Illuminate\Support\Facades\Route;

// Test route with CORS headers
Route::get('test', function() {
    return response()->json([
        'success' => true,
        'message' => 'Admin API is working'
    ])->header('Access-Control-Allow-Origin', '*')
      ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
      ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

// OPTIONS route for CORS preflight
Route::options('{any}', function() {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
})->where('any', '.*');

// Simple admin login route with CORS headers
Route::post('login', function(\Illuminate\Http\Request $request) {
    try {
        // Only allow admin@cuesports.com to login
        if ($request->email !== 'admin@cuesports.com') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only authorized admin email allowed.'
            ], 401)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        $user = \App\Models\User::where('email', $request->email)
                                ->where('is_admin', true)
                                ->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful'
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Login error: ' . $e->getMessage()
        ], 500)->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
});

// Simple tournament creation route (no auth for testing)
Route::post('create-tournament', function(\Illuminate\Http\Request $request) {
    try {
        $tournament = \App\Models\Tournament::create([
            'name' => $request->name ?? 'Test Tournament',
            'description' => $request->description ?? 'Test description',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'registration_deadline' => now()->addDays(5)->format('Y-m-d'),
            'tournament_charge' => $request->entry_fee ?? 0,
            'entry_fee' => $request->entry_fee ?? 0,
            'max_participants' => $request->max_participants ?? 100,
            'status' => 'registration',
            'created_by' => 1,
        ]);

        // Log tournament creation for email notifications
        \Illuminate\Support\Facades\Log::info('🏆 New tournament created', [
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name,
            'created_by' => 'Admin (Test Route)',
            'entry_fee' => $tournament->entry_fee,
            'max_participants' => $tournament->max_participants,
            'registration_deadline' => $tournament->registration_deadline,
            'start_date' => $tournament->start_date
        ]);

        // Automatically send tournament announcement emails
        try {
            // Get users based on tournament area scope
            $recipients = [];
            
            $usersQuery = \App\Models\User::where('is_admin', '!=', true)
                ->orWhereNull('is_admin');
            
            // Apply area scoping based on tournament scope
            if ($tournament->area_scope && $tournament->area_name) {
                switch ($tournament->area_scope) {
                    case 'community':
                        $usersQuery->whereHas('community', function($q) use ($tournament) {
                            $q->where('name', $tournament->area_name);
                        });
                        break;
                    case 'county':
                        $usersQuery->whereHas('county', function($q) use ($tournament) {
                            $q->where('name', $tournament->area_name);
                        });
                        break;
                    case 'region':
                        $usersQuery->whereHas('region', function($q) use ($tournament) {
                            $q->where('name', $tournament->area_name);
                        });
                        break;
                    case 'national':
                    default:
                        // National tournaments - send to all users (no filtering)
                        break;
                }
            }
            
            $users = $usersQuery->select('id', 'name', 'email')->get();
            
            foreach ($users as $user) {
                $recipients[] = [
                    'email' => $user->email,
                    'name' => $user->name
                ];
                
                // Also create push notification for each user
                \App\Models\Notification::create([
                    'player_id' => $user->id,
                    'type' => 'tournament_created',
                    'message' => "New tournament '{$tournament->name}' is now open for registration!",
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'area_scope' => $tournament->area_scope,
                        'area_name' => $tournament->area_name
                    ]
                ]);
            }

            // Use the EmailService to send tournament announcements
            $emailService = new \App\Services\EmailService();
            
            // Prepare tournament data for email
            $tournamentData = [
                'tournament_name' => $tournament->name,
                'tournament_description' => $tournament->description,
                'registration_deadline' => $tournament->registration_deadline ? 
                    \Carbon\Carbon::parse($tournament->registration_deadline)->format('M j, Y g:i A') : 'TBD',
                'tournament_date' => $tournament->start_date ? 
                    \Carbon\Carbon::parse($tournament->start_date)->format('M j, Y') : null,
                'entry_fee' => $tournament->entry_fee,
                'prize_pool' => $tournament->prize_pool ?? 0,
                'tournament_level' => $tournament->level ?? 'open',
                'max_participants' => $tournament->max_participants,
            ];

            $emailResults = $emailService->sendBulkEmailsQueued(
                $recipients,
                'tournament_announcement',
                $tournamentData
            );

            \Illuminate\Support\Facades\Log::info('📧 Tournament announcement emails sent', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'total_recipients' => $emailResults['total'],
                'emails_sent' => $emailResults['queued'],
                'emails_failed' => $emailResults['failed']
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Failed to send tournament announcement emails', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'tournament' => $tournament,
            'message' => 'Tournament created successfully and announcement emails sent'
        ], 201)->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating tournament: ' . $e->getMessage()
        ], 500)->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
});

// ADMIN ROUTES WITH SANCTUM AUTHENTICATION
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Auth routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', function(\Illuminate\Http\Request $request) {
        try {
            $user = $request->user();
            
            if (!$user || !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403)->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            }
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting user: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Tournament Management - Add CORS headers for admin dashboard
    Route::get('tournaments', function() {
        try {
            $tournaments = \App\Models\Tournament::with(['registeredUsers'])
                ->withCount(['matches', 'registeredUsers'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'tournaments' => $tournaments
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading tournaments: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    Route::post('tournaments', function(\Illuminate\Http\Request $request) {
        try {
            $tournament = \App\Models\Tournament::create([
                'name' => $request->name ?? 'New Tournament',
                'description' => $request->description ?? 'Tournament description',
                'start_date' => $request->start_date ?? now()->addDays(7)->format('Y-m-d'),
                'end_date' => $request->end_date ?? now()->addDays(30)->format('Y-m-d'),
                'registration_deadline' => $request->registration_deadline ?? now()->addDays(5)->format('Y-m-d'),
                'tournament_charge' => $request->entry_fee ?? 0,
                'entry_fee' => $request->entry_fee ?? 0,
                'max_participants' => $request->max_participants ?? 100,
                'status' => 'registration',
                'created_by' => auth()->id(),
            ]);

            // Log tournament creation for email notifications
            \Illuminate\Support\Facades\Log::info('🏆 New tournament created', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'created_by' => 'Admin (ID: ' . auth()->id() . ')',
                'entry_fee' => $tournament->entry_fee,
                'max_participants' => $tournament->max_participants,
                'registration_deadline' => $tournament->registration_deadline,
                'start_date' => $tournament->start_date,
                'description' => $tournament->description
            ]);

            // Automatically send tournament announcement emails
            try {
                // Get users based on tournament area scope
                $recipients = [];
                
                $usersQuery = \App\Models\User::where('is_admin', '!=', true)
                    ->orWhereNull('is_admin');
                
                // Apply area scoping based on tournament scope
                if ($tournament->area_scope && $tournament->area_name) {
                    switch ($tournament->area_scope) {
                        case 'community':
                            $usersQuery->whereHas('community', function($q) use ($tournament) {
                                $q->where('name', $tournament->area_name);
                            });
                            break;
                        case 'county':
                            $usersQuery->whereHas('county', function($q) use ($tournament) {
                                $q->where('name', $tournament->area_name);
                            });
                            break;
                        case 'region':
                            $usersQuery->whereHas('region', function($q) use ($tournament) {
                                $q->where('name', $tournament->area_name);
                            });
                            break;
                        case 'national':
                        default:
                            // National tournaments - send to all users (no filtering)
                            break;
                    }
                }
                
                $users = $usersQuery->select('id', 'name', 'email')->get();
                
                foreach ($users as $user) {
                    $recipients[] = [
                        'email' => $user->email,
                        'name' => $user->name
                    ];
                    
                    // Also create push notification for each user
                    \App\Models\Notification::create([
                        'player_id' => $user->id,
                        'type' => 'tournament_created',
                        'message' => "New tournament '{$tournament->name}' is now open for registration!",
                        'data' => [
                            'tournament_id' => $tournament->id,
                            'tournament_name' => $tournament->name,
                            'area_scope' => $tournament->area_scope,
                            'area_name' => $tournament->area_name
                        ]
                    ]);
                }

                // Use the EmailService to send tournament announcements
                $emailService = new \App\Services\EmailService();
                
                // Prepare tournament data for email
                $tournamentData = [
                    'tournament_name' => $tournament->name,
                    'tournament_description' => $tournament->description,
                    'registration_deadline' => $tournament->registration_deadline ? 
                        \Carbon\Carbon::parse($tournament->registration_deadline)->format('M j, Y g:i A') : 'TBD',
                    'tournament_date' => $tournament->start_date ? 
                        \Carbon\Carbon::parse($tournament->start_date)->format('M j, Y') : null,
                    'entry_fee' => $tournament->entry_fee,
                    'prize_pool' => $tournament->prize_pool ?? 0,
                    'tournament_level' => $tournament->level ?? 'open',
                    'max_participants' => $tournament->max_participants,
                ];

                $emailResults = $emailService->sendBulkEmailsQueued(
                    $recipients,
                    'tournament_announcement',
                    $tournamentData
                );

                \Illuminate\Support\Facades\Log::info('📧 Tournament announcement emails sent', [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'total_recipients' => $emailResults['total'],
                    'emails_sent' => $emailResults['queued'],
                    'emails_failed' => $emailResults['failed']
                ]);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('❌ Failed to send tournament announcement emails', [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'tournament' => $tournament,
                'message' => 'Tournament created successfully and announcement emails sent'
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating tournament: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // User management - commented out until AdminUserController is created
    // Route::get('users', [AdminUserController::class, 'index']);
    // Route::get('users/{user}', [AdminUserController::class, 'show']);
    // Route::put('users/{user}', [AdminUserController::class, 'update']);
    // Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
    
    // Dashboard with enhanced statistics and caching
    Route::get('dashboard', function() {
        try {
            // Clear cache and get fresh data for debugging
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
            
            // Use single query with aggregates for better performance
            $stats = \Illuminate\Support\Facades\Cache::remember('admin_dashboard_stats', 300, function() {
                $totalTournaments = \App\Models\Tournament::count();
                $totalUsers = \App\Models\User::count();
                $totalMatches = \DB::table('matches')->count();
                $totalEnrollments = \DB::table('tournament_registrations')->count();
                
                // Tournament status breakdown in single query
                $tournamentStats = \App\Models\Tournament::selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "registration" THEN 1 ELSE 0 END) as pending
                ')->first();
                
                return [
                    'total_tournaments' => $totalTournaments,
                    'total_users' => $totalUsers,
                    'total_matches' => $totalMatches,
                    'total_enrollments' => $totalEnrollments,
                    'active_tournaments' => $tournamentStats->active ?? 0,
                    'completed_tournaments' => $tournamentStats->completed ?? 0,
                    'pending_tournaments' => $tournamentStats->pending ?? 0
                ];
            });
            
            // User growth data (cached separately for 1 hour)
            $userGrowth = \Illuminate\Support\Facades\Cache::remember('admin_user_growth', 3600, function() {
                $userGrowthLabels = [];
                $userGrowthData = [];
                
                // Use single query for user growth
                $growthData = \App\Models\User::selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as count
                ')
                ->where('created_at', '>=', now()->subDays(6))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');
                
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $dateStr = $date->format('Y-m-d');
                    $userGrowthLabels[] = $date->format('M d');
                    $userGrowthData[] = $growthData->get($dateStr)->count ?? 0;
                }
                
                return [
                    'labels' => $userGrowthLabels,
                    'data' => $userGrowthData
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => array_merge($stats, [
                    'user_growth_labels' => $userGrowth['labels'],
                    'user_growth_data' => $userGrowth['data']
                ])
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dashboard error: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Tournament management - commented out until we use web routes
    // Route::get('tournaments', [AdminTournamentController::class, 'index']);
    // Route::post('tournaments', [AdminTournamentController::class, 'store']);
    // Route::get('tournaments/{tournament}', [AdminTournamentController::class, 'show']);
    // Route::put('tournaments/{tournament}', [AdminTournamentController::class, 'update']);
    // Route::delete('tournaments/{tournament}', [AdminTournamentController::class, 'destroy']);
    
    // Tournament registrations endpoint
    Route::get('tournaments/{tournament}/registrations', function($tournamentId) {
        try {
            $tournament = \App\Models\Tournament::findOrFail($tournamentId);
            
            // Get registered users with their details
            $registrations = \Illuminate\Support\Facades\DB::table('registered_users')
                ->join('users', 'registered_users.player_id', '=', 'users.id')
                ->where('registered_users.tournament_id', $tournamentId)
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.community_id',
                    'users.county_id', 
                    'users.region_id',
                    'users.total_points',
                    'registered_users.payment_status',
                    'registered_users.status',
                    'registered_users.registration_date'
                ])
                ->get();
            
            // Group by geographic levels
            $byRegion = $registrations->groupBy('region_id');
            $byCounty = $registrations->groupBy('county_id');
            $byCommunity = $registrations->groupBy('community_id');
            
            return response()->json([
                'success' => true,
                'tournament' => $tournament,
                'registrations' => $registrations,
                'statistics' => [
                    'total_registrations' => $registrations->count(),
                    'by_region' => $byRegion->map->count(),
                    'by_county' => $byCounty->map->count(),
                    'by_community' => $byCommunity->map->count(),
                    'payment_completed' => $registrations->where('payment_status', 'completed')->count(),
                    'payment_pending' => $registrations->where('payment_status', 'pending')->count()
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading registrations: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Route::get('tournaments/{tournament}/progress', [AdminTournamentController::class, 'progress']);
    // Route::get('tournaments/{tournament}/matches', [AdminTournamentController::class, 'matches']);
    // Route::get('tournaments/{tournament}/statistics', [AdminTournamentController::class, 'statistics']);
    // Route::get('tournaments/{tournament}/pending-approvals', [AdminTournamentController::class, 'pendingApprovals']);
    // Route::post('matches/{match}/reject', [AdminMatchController::class, 'reject']);
    
    // Notification management - commented out until controllers are created
    // Route::get('notifications', [AdminNotificationController::class, 'index']);
    // Route::post('notifications', [AdminNotificationController::class, 'store']);
    // Route::put('notifications/{notification}', [AdminNotificationController::class, 'update']);
    // Route::delete('notifications/{notification}', [AdminNotificationController::class, 'destroy']);
    
    // Player Communication & Admin Messages
    Route::post('messages', function(\Illuminate\Http\Request $request) {
        try {
            $message = \App\Models\Message::create([
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type ?? 'general',
                'tournament_id' => $request->tournament_id,
                'created_by' => auth()->id(),
                'sent_at' => now()
            ]);

            // Send notifications based on type
            if ($message->type === 'tournament' && $message->tournament_id) {
                // Send to tournament participants
                $tournament = \App\Models\Tournament::find($message->tournament_id);
                if ($tournament) {
                    $users = $tournament->registeredUsers;
                    
                    foreach ($users as $user) {
                        \App\Models\Notification::create([
                            'player_id' => $user->id,
                            'type' => 'admin_message',
                            'message' => $message->title . ': ' . $message->message,
                            'data' => ['admin_message_id' => $message->id]
                        ]);
                    }
                }
            } else {
                // Send to all users (exclude admins)
                $users = \App\Models\User::where('is_admin', '!=', true)->orWhereNull('is_admin')->get();
                
                foreach ($users as $user) {
                    \App\Models\Notification::create([
                        'player_id' => $user->id,
                        'type' => 'admin_message',
                        'message' => $message->title . ': ' . $message->message,
                        'data' => ['admin_message_id' => $message->id]
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending message: ' . $e->getMessage()
            ], 500);
        }
    });
    
    Route::get('messages', function() {
        $messages = \App\Models\Message::with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['messages' => $messages]);
    });
    
    // Tournament status endpoint for dynamic UI
    Route::get('tournaments/{tournament}/status', function($tournamentId) {
        try {
            $tournament = \App\Models\Tournament::findOrFail($tournamentId);
            
            $canInitialize = true;
            $initializeMessage = '';
            $buttonText = 'Initialize Tournament';
            
            // Check start date
            if ($tournament->start_date && $tournament->start_date->isFuture()) {
                $canInitialize = false;
                $initializeMessage = 'Start date (' . $tournament->start_date->format('Y-m-d') . ') has not arrived yet';
            }
            
            // Check registrations
            $registeredCount = $tournament->registeredUsers()->count();
            if ($registeredCount === 0) {
                $canInitialize = false;
                $initializeMessage = 'No users registered for this tournament';
            }
            
            // Check current level status
            $matches = \App\Models\PoolMatch::where('tournament_id', $tournament->id)->get();
            $currentLevel = 'community'; // Default starting level
            
            if ($matches->isNotEmpty()) {
                $levels = ['community', 'county', 'regional', 'national'];
                $completedLevels = [];
                
                foreach ($levels as $level) {
                    $levelMatches = $matches->where('level', $level);
                    if ($levelMatches->isNotEmpty()) {
                        $completedMatches = $levelMatches->where('status', 'completed')->count();
                        $totalMatches = $levelMatches->count();
                        
                        if ($completedMatches === $totalMatches) {
                            $completedLevels[] = $level;
                        } else {
                            $currentLevel = $level;
                            $canInitialize = false;
                            $initializeMessage = ucfirst($level) . ' level in progress (' . ($totalMatches - $completedMatches) . ' matches remaining)';
                            break;
                        }
                    }
                }
                
                // Determine next level
                if (count($completedLevels) > 0) {
                    $lastCompleted = end($completedLevels);
                    $nextLevelIndex = array_search($lastCompleted, $levels) + 1;
                    if ($nextLevelIndex < count($levels)) {
                        $currentLevel = $levels[$nextLevelIndex];
                        $buttonText = 'Initialize ' . ucfirst($currentLevel) . ' Level';
                    } else {
                        $buttonText = 'Tournament Complete';
                        $canInitialize = false;
                        $initializeMessage = 'Tournament has been completed';
                    }
                }
            }
            
            return response()->json([
                'can_initialize' => $canInitialize,
                'message' => $initializeMessage,
                'button_text' => $buttonText,
                'current_level' => $currentLevel,
                'registered_count' => $registeredCount,
                'total_matches' => $matches->count(),
                'completed_matches' => $matches->where('status', 'completed')->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting tournament status: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Paginated tournaments endpoint with optimized queries
    Route::get('tournaments/paginated', function(\Illuminate\Http\Request $request) {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            
            // Load more data per page for better responsiveness
            $perPage = min($perPage, 50); // Cap at 50 for performance
            
            // Use selectRaw to count registrations efficiently
            $tournaments = \DB::table('tournaments')
                ->leftJoin('tournament_registrations', 'tournaments.id', '=', 'tournament_registrations.tournament_id')
                ->selectRaw('tournaments.*, COUNT(tournament_registrations.id) as registrations_count')
                ->groupBy('tournaments.id')
                ->orderBy('tournaments.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'tournaments' => $tournaments->items(),
                    'pagination' => [
                        'current_page' => $tournaments->currentPage(),
                        'total_pages' => $tournaments->lastPage(),
                        'per_page' => $tournaments->perPage(),
                        'total' => $tournaments->total()
                    ]
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading tournaments: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Enhanced Tournaments endpoint with pagination
    Route::get('tournaments', function(\Illuminate\Http\Request $request) {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            
            $tournaments = \App\Models\Tournament::with(['registeredUsers'])
                ->withCount(['registeredUsers'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'tournaments' => $tournaments->items(),
                    'pagination' => [
                        'current_page' => $tournaments->currentPage(),
                        'total_pages' => $tournaments->lastPage(),
                        'per_page' => $tournaments->perPage(),
                        'total' => $tournaments->total()
                    ]
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading tournaments: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Matches endpoint with pagination and filters
    Route::get('matches', function(\Illuminate\Http\Request $request) {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $region = $request->get('region');
            $county = $request->get('county');
            $community = $request->get('community');
            
            // Optimized query with proper table references
            $query = \DB::table('matches')
                ->join('tournaments', 'matches.tournament_id', '=', 'tournaments.id')
                ->join('users as p1', 'matches.player_1_id', '=', 'p1.id')
                ->join('users as p2', 'matches.player_2_id', '=', 'p2.id')
                ->leftJoin('users as winner', 'matches.winner_id', '=', 'winner.id')
                ->select([
                    'matches.id',
                    'matches.match_name',
                    'matches.player_1_points',
                    'matches.player_2_points',
                    'matches.status',
                    'matches.level',
                    'matches.round_name',
                    'matches.created_at',
                    'tournaments.name as tournament_name',
                    'p1.name as player_1_name',
                    'p2.name as player_2_name',
                    'winner.name as winner_name'
                ]);
            
            if ($region) {
                $query->where('p1.region_id', $region)->orWhere('p2.region_id', $region);
            }
            if ($county) {
                $query->where('p1.county_id', $county)->orWhere('p2.county_id', $county);
            }
            if ($community) {
                $query->where('p1.community_id', $community)->orWhere('p2.community_id', $community);
            }
            
            $matches = $query->orderBy('matches.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'matches' => $matches->items(),
                    'pagination' => [
                        'current_page' => $matches->currentPage(),
                        'total_pages' => $matches->lastPage(),
                        'per_page' => $matches->perPage(),
                        'total' => $matches->total()
                    ]
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading matches: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Players endpoint with pagination and filters (optimized)
    Route::get('players', function(\Illuminate\Http\Request $request) {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');
            $region = $request->get('region');
            $status = $request->get('status');
            
            // Use joins instead of with() for better performance
            $query = \App\Models\User::leftJoin('regions', 'users.region_id', '=', 'regions.id')
                ->leftJoin('counties', 'users.county_id', '=', 'counties.id')
                ->leftJoin('communities', 'users.community_id', '=', 'communities.id')
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.phone',
                    'users.total_points',
                    'users.created_at',
                    'regions.name as region_name',
                    'counties.name as county_name',
                    'communities.name as community_name'
                ]);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                      ->orWhere('users.email', 'like', "%{$search}%")
                      ->orWhere('users.username', 'like', "%{$search}%");
                });
            }
            
            if ($region) {
                $query->where('users.region_id', $region);
            }
            
            $players = $query->orderBy('users.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'players' => $players->items(),
                    'pagination' => [
                        'current_page' => $players->currentPage(),
                        'total_pages' => $players->lastPage(),
                        'per_page' => $players->perPage(),
                        'total' => $players->total()
                    ]
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading players: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Messages (support) endpoint with pagination (optimized)
    Route::get('messages', function(\Illuminate\Http\Request $request) {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $status = $request->get('status');
            
            // Use join instead of with() for better performance
            $query = \App\Models\ContactSupport::join('users', 'contact_support.user_id', '=', 'users.id')
                ->select([
                    'contact_support.*',
                    'users.name as user_name',
                    'users.email as user_email'
                ]);
            
            if ($status) {
                $query->where('contact_support.status', $status);
            }
            
            $messages = $query->orderBy('contact_support.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages->items(),
                    'pagination' => [
                        'current_page' => $messages->currentPage(),
                        'total_pages' => $messages->lastPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total()
                    ]
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading messages: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Communities endpoint with pagination and filters (optimized)
    Route::get('communities', function(\Illuminate\Http\Request $request) {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $region = $request->get('region');
            $county = $request->get('county');
            
            // Use joins for better performance
            $query = \App\Models\Community::join('counties', 'communities.county_id', '=', 'counties.id')
                ->join('regions', 'counties.region_id', '=', 'regions.id')
                ->select([
                    'communities.*',
                    'counties.name as county_name',
                    'regions.name as region_name'
                ]);
            
            if ($region) {
                $query->where('regions.id', $region);
            }
            if ($county) {
                $query->where('counties.id', $county);
            }
            
            $communities = $query->orderBy('communities.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'communities' => $communities->items(),
                    'pagination' => [
                        'current_page' => $communities->currentPage(),
                        'total_pages' => $communities->lastPage(),
                        'per_page' => $communities->perPage(),
                        'total' => $communities->total()
                    ]
                ]
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading communities: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
    
    // Create community endpoint
    Route::post('communities', function(\Illuminate\Http\Request $request) {
        try {
            $community = \App\Models\Community::create([
                'name' => $request->name,
                'county_id' => $request->county_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $community,
                'message' => 'Community created successfully'
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating community: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    });
});
