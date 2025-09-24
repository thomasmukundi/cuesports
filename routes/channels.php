<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Private channel for individual users
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Presence channel for match participants
Broadcast::channel('match.{matchId}', function ($user, $matchId) {
    $match = \App\Models\PoolMatch::find($matchId);
    if (!$match) {
        return false;
    }
    
    return $match->player_1_id === $user->id || $match->player_2_id === $user->id;
});

// Public channel for tournaments
Broadcast::channel('tournaments', function () {
    return true;
});

// Admin channel
Broadcast::channel('admin', function ($user) {
    return $user->email === env('ADMIN_EMAIL', 'admin@cuesports.com');
});
