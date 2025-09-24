<?php

use Illuminate\Support\Facades\Route;

// Test route to verify we can hit the controller
Route::post('/test-init/{tournamentId}', function($tournamentId) {
    file_put_contents(storage_path('logs/debug.log'), "[" . date('Y-m-d H:i:s') . "] TEST ROUTE HIT with ID: " . $tournamentId . "\n", FILE_APPEND);
    
    return response()->json([
        'message' => 'Test route hit successfully',
        'tournament_id' => $tournamentId,
        'timestamp' => now()
    ]);
});
