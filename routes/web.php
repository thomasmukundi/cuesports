<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/blog', function () {
    return view('blog');
})->name('blog');

Route::get('/features', function () {
    return view('features');
})->name('features');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::post('/contact', [App\Http\Controllers\ContactController::class, 'store'])->name('contact.store');

// Email Testing Routes (for development/testing)
Route::get('/email-test', [App\Http\Controllers\EmailTestController::class, 'index'])->name('email.test');
Route::post('/email-test/send', [App\Http\Controllers\EmailTestController::class, 'sendTest'])->name('email.test.send');
Route::post('/email-test/test-all', [App\Http\Controllers\EmailTestController::class, 'testAll'])->name('email.test.all');

Route::middleware('web-inertia')->group(function () {

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // Tournament routes
    Route::get('/tournaments', [AdminController::class, 'tournaments'])->name('admin.tournaments');
    Route::get('/tournaments/create', [AdminController::class, 'createTournament'])->name('admin.tournaments.create');
    Route::post('/tournaments', [AdminController::class, 'storeTournament'])->name('admin.tournaments.store');
    Route::get('/tournaments/{tournament}', [AdminController::class, 'viewTournament'])->name('admin.tournaments.view');
    Route::get('/tournaments/{tournament}/edit', [AdminController::class, 'editTournament'])->name('admin.tournaments.edit');
    Route::put('/tournaments/{tournament}', [AdminController::class, 'updateTournament'])->name('admin.tournaments.update');
    Route::delete('/tournaments/{tournament}', [AdminController::class, 'deleteTournament'])->name('admin.tournaments.destroy');
    Route::post('/tournaments/{tournament}/initialize/{level}', [AdminController::class, 'initializeTournamentLevel'])->name('admin.tournaments.initialize');
    
    // Community routes
    Route::get('/communities', [AdminController::class, 'communities'])->name('admin.communities');
    Route::get('/communities/create', [AdminController::class, 'createCommunity'])->name('admin.communities.create');
    Route::post('/communities', [AdminController::class, 'storeCommunity'])->name('admin.communities.store');
    Route::get('/communities/{community}/edit', [AdminController::class, 'editCommunity'])->name('admin.communities.edit');
    Route::put('/communities/{community}', [AdminController::class, 'updateCommunity'])->name('admin.communities.update');
    Route::delete('/communities/{community}', [AdminController::class, 'deleteCommunity'])->name('admin.communities.delete');
    Route::get('/communities/{community}', [AdminController::class, 'viewCommunity'])->name('admin.communities.view');
    
    Route::get('/matches', [AdminController::class, 'matches'])->name('admin.matches');
    Route::delete('/matches/{match}', [AdminController::class, 'deleteMatch'])->name('admin.matches.destroy');
    
    Route::get('/players', [AdminController::class, 'players'])->name('admin.players');
    Route::get('/players/{player}', [AdminController::class, 'viewPlayer'])->name('admin.players.view');
    Route::delete('/players/{player}', [AdminController::class, 'deletePlayer'])->name('admin.players.destroy');
    Route::delete('/players', [AdminController::class, 'bulkDeletePlayers'])->name('admin.players.bulk-destroy');
    
    Route::get('/messages', [AdminController::class, 'messages'])->name('admin.messages');
    
    // Winners routes
    Route::get('/winners', [AdminController::class, 'winners'])->name('admin.winners');
    Route::get('/winners/create', [AdminController::class, 'createWinner'])->name('admin.winners.create');
    Route::post('/winners', [AdminController::class, 'storeWinner'])->name('admin.winners.store');
    Route::get('/winners/{winner}/edit', [AdminController::class, 'editWinner'])->name('admin.winners.edit');
    Route::put('/winners/{winner}', [AdminController::class, 'updateWinner'])->name('admin.winners.update');
    Route::delete('/winners/{winner}', [AdminController::class, 'deleteWinner'])->name('admin.winners.destroy');
    
    // Transactions routes
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('admin.transactions');
    Route::get('/transactions/{transaction}', [AdminController::class, 'showTransaction'])->name('admin.transactions.show');
    Route::post('/transactions/{transaction}/status', [AdminController::class, 'updateTransactionStatus'])->name('admin.transactions.update-status');
    
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
    
    // API endpoints for cascading dropdowns
    Route::get('/api/counties/{region}', [AdminController::class, 'getCountiesByRegion'])->name('admin.api.counties');
    Route::get('/api/communities/{county}', [AdminController::class, 'getCommunitiesByCounty'])->name('admin.api.communities');
    Route::get('/api/tournaments', [AdminController::class, 'getTournamentsApi'])->name('admin.api.tournaments');
    
    // API endpoints for tournament area selection
    Route::get('/api/regions', [AdminController::class, 'getRegionsApi'])->name('admin.api.regions');
    Route::get('/api/counties', [AdminController::class, 'getCountiesApi'])->name('admin.api.counties.all');
    Route::get('/api/communities', [AdminController::class, 'getCommunitiesApi'])->name('admin.api.communities.all');
    
    // Communication routes
    Route::get('/communications', function() {
        return view('admin.communications');
    })->name('admin.communications');
    Route::post('/communications/send-all', [App\Http\Controllers\AdminCommunicationController::class, 'sendToAllPlayers'])->name('admin.communications.send-all');
    Route::post('/communications/test-send', [App\Http\Controllers\AdminCommunicationController::class, 'sendTestCommunication'])->name('admin.communications.test-send');
    Route::post('/communications/tournament-announcement', [App\Http\Controllers\AdminCommunicationController::class, 'sendTournamentAnnouncement'])->name('admin.communications.tournament');
    Route::get('/communications/stats', [App\Http\Controllers\AdminCommunicationController::class, 'getStats'])->name('admin.communications.stats');
    
    // Admin Password Change Routes
    Route::get('/change-password', [App\Http\Controllers\AdminPasswordController::class, 'showChangePassword'])->name('admin.password.form');
    Route::post('/change-password/send-code', [App\Http\Controllers\AdminPasswordController::class, 'sendVerificationCode'])->name('admin.password.send-code');
    Route::post('/change-password/change', [App\Http\Controllers\AdminPasswordController::class, 'changePassword'])->name('admin.password.change');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin_auth.php';
