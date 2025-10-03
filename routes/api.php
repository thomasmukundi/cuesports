<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Admin\TournamentController as AdminTournamentController;
use App\Http\Controllers\TournamentRegistrationController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Api\TournamentProgressionController;
use App\Http\Controllers\Api\LevelCompletionController;
use App\Http\Controllers\Api\TestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// JWT Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');
    
    // FCM Token management
    Route::post('/fcm-token', [AuthController::class, 'updateFcmToken'])->middleware('auth:api');
    Route::delete('/fcm-token', [AuthController::class, 'removeFcmToken'])->middleware('auth:api');
});

// Email verification routes (public)
Route::prefix('verification')->group(function () {
    Route::post('/send-code', [App\Http\Controllers\VerificationController::class, 'sendCode']);
    Route::post('/verify-code', [App\Http\Controllers\VerificationController::class, 'verifyCode']);
    Route::post('/resend-code', [App\Http\Controllers\VerificationController::class, 'resendCode']);
    Route::get('/check-status', [App\Http\Controllers\VerificationController::class, 'checkStatus']);
});

// Password reset route (public)
Route::post('/reset-password', [App\Http\Controllers\VerificationController::class, 'resetPassword']);

// Public debug endpoint for storage configuration
Route::get('/debug/config', function() {
    return response()->json([
        'filesystem_default' => config('filesystems.default'),
        'filesystem_disk_env' => env('FILESYSTEM_DISK'),
        'app_env' => env('APP_ENV'),
        'timestamp' => now()->toISOString(),
    ]);
});

// Complete registration after email verification (public)
Route::post('/complete-registration', [App\Http\Controllers\CompleteRegistrationController::class, 'completeRegistration']);

// Public routes
Route::post('/register-old', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login-old', [App\Http\Controllers\AuthController::class, 'login']);

// Location endpoints (public)
Route::get('/regions', [LocationController::class, 'getRegions']);
Route::get('/counties', [LocationController::class, 'getCountiesByRegion']);
Route::get('/communities', [LocationController::class, 'getCommunitiesByCounty']);
Route::get('/counties/all', [LocationController::class, 'getAllCounties']);

// Communities endpoint
Route::get('/communities/list', [App\Http\Controllers\Api\CommunityController::class, 'index']);
Route::get('/communities/{community}', [App\Http\Controllers\Api\CommunityController::class, 'show']);

// UNPROTECTED TOURNAMENT INITIALIZATION FOR TESTING
Route::post('/tournament-init/{id}', function($id) {
    file_put_contents(storage_path('logs/debug.log'), "[" . date('Y-m-d H:i:s') . "] UNPROTECTED INIT ROUTE HIT with ID: " . $id . "\n", FILE_APPEND);
    
    try {
        $tournament = \App\Models\Tournament::findOrFail($id);
        $matchService = new \App\Services\MatchAlgorithmService();
        $result = $matchService->initialize($id, 'special');
        
        return response()->json([
            'success' => true,
            'message' => 'Tournament initialized successfully',
            'result' => $result
        ]);
    } catch (\Exception $e) {
        file_put_contents(storage_path('logs/debug.log'), "[" . date('Y-m-d H:i:s') . "] INIT ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// ADMIN TOURNAMENT ROUTES - WITH CUSTOM API AUTH
Route::middleware([App\Http\Middleware\ApiAuthenticate::class . ':sanctum'])->prefix('admin')->group(function () {
    Route::post('/tournaments/{tournament}/initialize', [AdminTournamentController::class, 'initialize']);
    Route::post('/tournaments/{tournament}/generate-next-round', [AdminTournamentController::class, 'generateNextRound']);
    Route::get('/tournaments/{tournament}/check-completion', [AdminTournamentController::class, 'checkCompletion']);
    Route::get('/tournaments/{tournament}/matches', [AdminTournamentController::class, 'matches']);
    Route::get('/tournaments/{tournament}/statistics', [AdminTournamentController::class, 'statistics']);
    Route::get('/tournaments/{tournament}/pending-approvals', [AdminTournamentController::class, 'pendingApprovals']);
});

// JWT Protected routes
Route::middleware('auth:api')->group(function () {
    Route::post('/change-password', [App\Http\Controllers\AuthController::class, 'changePassword']);
    
    // User Dashboard and Statistics
    Route::get('/dashboard', [App\Http\Controllers\Api\UserController::class, 'dashboard']);
    Route::get('/user/dashboard', [App\Http\Controllers\Api\UserController::class, 'dashboard']);
    Route::get('/statistics', [App\Http\Controllers\Api\UserController::class, 'statistics']);
    Route::put('/user/update-community', [App\Http\Controllers\Api\UserController::class, 'updateCommunity']);
    Route::post('/user/update-profile-image', [App\Http\Controllers\Api\UserController::class, 'updateProfileImage']);
    
    // Tournaments
    Route::get('/tournaments', [App\Http\Controllers\Api\TournamentController::class, 'index']);
    Route::get('/tournaments/featured', [App\Http\Controllers\Api\TournamentController::class, 'featured']);
    Route::post('/tournaments/{tournament}/register', [App\Http\Controllers\Api\TournamentController::class, 'register']);
    Route::get('/tournaments/my-registrations', [App\Http\Controllers\Api\TournamentController::class, 'myRegistrations']);
    
    // TinyPesa payment routes
    Route::post('/tournaments/{tournament}/initialize-payment', [TournamentRegistrationController::class, 'initializePayment']);
    Route::get('/tournaments/{tournament}/check-payment-status', [TournamentRegistrationController::class, 'checkPaymentStatus']);
    Route::get('/test-tinypesa', [TournamentRegistrationController::class, 'testTinyPesa']);
    
    // Matches
    Route::get('/matches', [App\Http\Controllers\Api\MatchController::class, 'index']);
    Route::get('/matches/{match}', [App\Http\Controllers\Api\MatchController::class, 'show']);
    Route::post('/matches/{match}/propose-dates', [App\Http\Controllers\Api\MatchController::class, 'proposeDates']);
    Route::post('/matches/{match}/schedule', [App\Http\Controllers\Api\MatchController::class, 'scheduleMatch']);
    Route::post('/matches/{match}/select-dates', [App\Http\Controllers\Api\MatchController::class, 'selectDates']);
    Route::post('/matches/{match}/submit-results', [App\Http\Controllers\Api\MatchController::class, 'submitWinLoseResult']);
    Route::post('/matches/{match}/submit-points', [App\Http\Controllers\Api\MatchController::class, 'submitPointsResult']);
    Route::post('/matches/{match}/confirm-results', [App\Http\Controllers\Api\MatchController::class, 'confirmResults']);
    Route::post('/matches/{match}/forfeit', [App\Http\Controllers\Api\MatchController::class, 'forfeitMatch']);
    Route::get('/matches/{match}/messages', [App\Http\Controllers\Api\MatchController::class, 'getMessages']);
    Route::post('/matches/{match}/messages', [App\Http\Controllers\Api\MatchController::class, 'sendMessage']);
    
    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::get('/notifications/real-time-check', [App\Http\Controllers\Api\NotificationController::class, 'realTimeCheck']);
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/clear-all', [App\Http\Controllers\Api\NotificationController::class, 'clearAll']);
    
    // Contact Support
    Route::post('/contact-support', [App\Http\Controllers\Api\ContactSupportController::class, 'store']);
    Route::get('/contact-support', [App\Http\Controllers\Api\ContactSupportController::class, 'index']);
    
    // Payment Processing
    Route::post('/tournaments/{tournament}/pay', [PaymentController::class, 'processTournamentPayment']);
    Route::get('/payments/{payment}/status', [PaymentController::class, 'getPaymentStatus']);
    Route::get('/payments/history', [PaymentController::class, 'getPaymentHistory']);
    
});

// Public endpoints (no authentication required)
Route::get('/users', [App\Http\Controllers\Api\UserController::class, 'index']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [App\Http\Controllers\PlayerController::class, 'updateProfile']);
    Route::post('/logout-old', [App\Http\Controllers\AuthController::class, 'logout']);
    
    // Player Stats and Leaderboard
    Route::get('/players/leaderboard', [App\Http\Controllers\PlayerController::class, 'leaderboard']);
    Route::get('/players/debug-leaderboard', [App\Http\Controllers\PlayerController::class, 'debugLeaderboard']);
    Route::get('/players/simple-leaderboard', [App\Http\Controllers\PlayerController::class, 'simpleLeaderboard']);
    Route::get('/players/top-shooters-detailed', [App\Http\Controllers\PlayerController::class, 'topShootersDetailed']);
    Route::get('/players/personal-leaderboard', [App\Http\Controllers\PlayerController::class, 'personalLeaderboard']);
    Route::get('/players/awards', [App\Http\Controllers\PlayerController::class, 'awards']);
    Route::get('/players/{player}/stats', [App\Http\Controllers\PlayerController::class, 'playerStats']);
    Route::get('/players/my-stats', [App\Http\Controllers\PlayerController::class, 'myStats']);
    
    // Tournament Registration (legacy - using different endpoints to avoid conflicts)
    Route::prefix('tournaments')->group(function () {
        Route::get('/available', [TournamentRegistrationController::class, 'available']);
        Route::post('/{tournament}/confirm-payment', [TournamentRegistrationController::class, 'confirmPayment']);
        Route::delete('/{tournament}/cancel', [TournamentRegistrationController::class, 'cancel']);
    });
    
    
    // Chat
    Route::prefix('chat')->group(function () {
        Route::get('/match/{match}', [ChatController::class, 'getMatchMessages']);
        Route::post('/matches/{match}/messages', [MatchController::class, 'sendMessage']);
        Route::get('/conversations', [ChatController::class, 'getConversations']);
    });
    
    // Tournament progression routes
    Route::post('/tournament-progression/check-round-completion', [TournamentProgressionController::class, 'checkRoundCompletion']);
    Route::post('/tournament-progression/determine-positions', [TournamentProgressionController::class, 'determinePositions']);
    
    // Level completion routes
    Route::post('/level-completion/check', [LevelCompletionController::class, 'checkLevelCompletion']);
    Route::post('/level-completion/initialize-next', [LevelCompletionController::class, 'initializeNextLevel']);
    
    // Notifications (legacy - using different endpoints to avoid conflicts)
    Route::prefix('notifications-legacy')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    });
    
    // Player Stats
    Route::prefix('players')->group(function () {
        Route::get('/leaderboard', [PlayerController::class, 'leaderboard']);
        Route::get('/{player}/stats', [PlayerController::class, 'playerStats']);
        Route::get('/my-stats', [PlayerController::class, 'myStats']);
    });
    
    // Test route without middleware
    Route::post('/admin/test-init/{tournamentId}', function($tournamentId) {
        file_put_contents(storage_path('logs/debug.log'), "[" . date('Y-m-d H:i:s') . "] TEST ROUTE HIT with ID: " . $tournamentId . "\n", FILE_APPEND);
        return response()->json(['message' => 'Test route hit', 'id' => $tournamentId]);
    });
    
    // Direct tournament initialization without any middleware
    Route::post('/test-tournament-init/{id}', function($id) {
        file_put_contents(storage_path('logs/debug.log'), "[" . date('Y-m-d H:i:s') . "] DIRECT INIT ROUTE HIT with ID: " . $id . "\n", FILE_APPEND);
        
        try {
            $tournament = \App\Models\Tournament::findOrFail($id);
            $matchService = new \App\Services\MatchAlgorithmService();
            $result = $matchService->initialize($id, 'special');
            
            return response()->json([
                'success' => true,
                'message' => 'Tournament initialized successfully',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });

    // Admin routes - REMOVED DUPLICATE
    // Tournament initialization without auth for testing - MOVED TO TOP LEVEL
    
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        // Tournament Management
        Route::prefix('tournaments')->group(function () {
            Route::get('/', [AdminTournamentController::class, 'index']);
            Route::post('/', [AdminTournamentController::class, 'store']);
            Route::get('/{tournament}', [AdminTournamentController::class, 'show']);
            Route::put('/{tournament}', [AdminTournamentController::class, 'update']);
            Route::delete('/{tournament}', [AdminTournamentController::class, 'destroy']);
            // Route::post('/{tournament}/initialize', [AdminTournamentController::class, 'initialize']); // Moved outside auth
            Route::post('/{tournament}/generate-next-round', [AdminTournamentController::class, 'generateNextRound']);
            Route::get('/{tournament}/check-completion', [AdminTournamentController::class, 'checkCompletion']);
            Route::get('/{tournament}/matches', [AdminTournamentController::class, 'matches']);
            Route::get('/{tournament}/statistics', [AdminTournamentController::class, 'statistics']);
            Route::put('/{tournament}/automation-mode', [AdminTournamentController::class, 'updateAutomationMode']);
            Route::get('/{tournament}/pending-approvals', [AdminTournamentController::class, 'pendingApprovals']);
        });
        
        // Player Management
        Route::prefix('players')->group(function () {
            Route::get('/registrations/pending', [App\Http\Controllers\Admin\PlayerController::class, 'pendingRegistrations']);
            Route::post('/registrations/{registration}/approve', [App\Http\Controllers\Admin\PlayerController::class, 'approveRegistration']);
            Route::post('/registrations/{registration}/reject', [App\Http\Controllers\Admin\PlayerController::class, 'rejectRegistration']);
        });
        
        // System Stats
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index']);
    });
});

// Real-time status endpoints for mobile app optimization
Route::middleware('auth:api')->group(function () {
    Route::get('/matches/{match}/status', [App\Http\Controllers\Api\MatchController::class, 'getStatus']);
    Route::get('/matches/{match}/messages/since/{timestamp}', [App\Http\Controllers\Api\MatchController::class, 'getMessagesSince']);
    
    // Test endpoints for push notifications
    Route::post('/test/push-notification', [TestController::class, 'testPushNotification']);
    Route::get('/test/fcm-token-status', [TestController::class, 'getFcmTokenStatus']);
    
    // Debug endpoints for storage issues
    Route::get('/debug/storage', [App\Http\Controllers\Api\DebugController::class, 'debugStorage']);
    Route::get('/debug/storage-redirect', [App\Http\Controllers\Api\DebugController::class, 'testStorageRedirect']);
});
