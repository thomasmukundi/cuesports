<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Notification;
use App\Services\TournamentUtilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TournamentNotificationService
{
    /**
     * Send pairing notifications - consolidated approach to prevent multiple notifications per player
     */
    public static function sendPairingNotifications(Tournament $tournament, string $level): void
    {
        Log::info('sendPairingNotifications called - CONSOLIDATED VERSION', [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'timestamp' => now()
        ]);
        
        // Get all matches for this tournament and level
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('status', 'pending')
            ->get();
            
        Log::info('Found matches for pairing notifications', [
            'match_count' => $matches->count(),
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);
        
        // Group matches by player to send consolidated notifications
        $playerMatches = [];
        
        foreach ($matches as $match) {
            // Add match for player 1
            if (!isset($playerMatches[$match->player_1_id])) {
                $playerMatches[$match->player_1_id] = [];
            }
            $playerMatches[$match->player_1_id][] = [
                'match_id' => $match->id,
                'opponent_id' => $match->player_2_id,
                'round_name' => $match->round_name,
                'match_name' => $match->match_name
            ];
            
            // Add match for player 2
            if (!isset($playerMatches[$match->player_2_id])) {
                $playerMatches[$match->player_2_id] = [];
            }
            $playerMatches[$match->player_2_id][] = [
                'match_id' => $match->id,
                'opponent_id' => $match->player_1_id,
                'round_name' => $match->round_name,
                'match_name' => $match->match_name
            ];
        }
        
        Log::info('Grouped matches by player', [
            'player_count' => count($playerMatches),
            'total_player_matches' => array_sum(array_map('count', $playerMatches))
        ]);
        
        // Send notification to each player
        foreach ($playerMatches as $playerId => $playerMatchesData) {
            $matchCount = count($playerMatchesData);
            
            // Check if player already has a pairing notification for this tournament level
            $existingNotification = Notification::where('player_id', $playerId)
                ->where('type', 'pairing')
                ->where('data->tournament_id', $tournament->id)
                ->where('data->level', $level)
                ->exists();
                
            if (!$existingNotification) {
                $message = $matchCount === 1 
                    ? "You have been paired for a match in {$tournament->name}. Please select your available days."
                    : "You have been paired for {$matchCount} matches in {$tournament->name}. Please select your available days.";
                
                Log::info('Creating pairing notification for player', [
                    'player_id' => $playerId,
                    'match_count' => $matchCount,
                    'tournament_id' => $tournament->id,
                    'level' => $level
                ]);
                
                Notification::create([
                    'player_id' => $playerId,
                    'type' => 'pairing',
                    'message' => $message,
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'level' => $level,
                        'match_count' => $matchCount,
                        'matches' => $playerMatchesData
                    ]
                ]);
            } else {
                Log::info('Skipping duplicate pairing notification for player', [
                    'player_id' => $playerId,
                    'tournament_id' => $tournament->id,
                    'level' => $level
                ]);
            }
        }
        
        Log::info('Pairing notifications completed', [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'players_notified' => count($playerMatches)
        ]);
    }

    /**
     * Send notifications for level initialization
     */
    public static function sendLevelInitializationNotifications(Tournament $tournament, string $level, Collection $players): void
    {
        foreach ($players as $player) {
            Notification::create([
                'player_id' => $player->id,
                'type' => 'tournament_started',
                'message' => "You have qualified for the {$level} level tournament. Check your matches to see your opponents.",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'level' => $level
                ]
            ]);
        }
        
        Log::info("Level initialization notifications sent", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'players_notified' => $players->count()
        ]);
    }

    /**
     * Send match scheduling notifications
     */
    public static function sendMatchSchedulingNotifications(PoolMatch $match, string $scheduledDate): void
    {
        $message = "Your match has been scheduled for {$scheduledDate}";
        
        if ($match->player1) {
            Notification::create([
                'player_id' => $match->player_1_id,
                'type' => 'match_scheduled',
                'message' => $message,
                'data' => [
                    'match_id' => $match->id,
                    'scheduled_date' => $scheduledDate
                ]
            ]);
        }
        
        if ($match->player2) {
            Notification::create([
                'player_id' => $match->player_2_id,
                'type' => 'match_scheduled',
                'message' => $message,
                'data' => [
                    'match_id' => $match->id,
                    'scheduled_date' => $scheduledDate
                ]
            ]);
        }
        
        Log::info("Match scheduling notifications sent", [
            'match_id' => $match->id,
            'scheduled_date' => $scheduledDate
        ]);
    }

    /**
     * Send notifications to winners with proper tie messaging
     */
    public static function sendWinnerNotifications(Tournament $tournament, string $level, array $actualPositions, array $tieInfo = []): void
    {
        foreach ($actualPositions as $playerId => $actualPosition) {
            $message = "Congratulations! You finished in position {$actualPosition}";
            
            // Add tie information if applicable
            if (isset($tieInfo[$playerId])) {
                $tieData = $tieInfo[$playerId];
                if ($tieData['is_tied']) {
                    $message .= " (tied with " . ($tieData['tied_count'] - 1) . " other player" . 
                               ($tieData['tied_count'] > 2 ? "s" : "") . ")";
                    $message .= ". Tie broken by " . $tieData['tie_breaker_method'];
                }
            }
            
            $message .= " in the {$tournament->name} {$level} level tournament!";
            
            Log::info('Creating winner notification', [
                'player_id' => $playerId,
                'position' => $actualPosition,
                'tournament_id' => $tournament->id,
                'level' => $level,
                'message' => $message
            ]);
            
            Notification::create([
                'player_id' => $playerId,
                'type' => 'tournament_position',
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'level' => $level,
                    'position' => $actualPosition,
                    'tie_info' => $tieInfo[$playerId] ?? null,
                    'message' => $message
                ]
            ]);
        }
        
        Log::info("Winner notifications sent", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_notified' => count($actualPositions)
        ]);
    }

    /**
     * Send notification with detailed metrics information
     */
    public static function sendMetricsBasedPositionNotification(Tournament $tournament, array $positionData): void
    {
        $player = User::find($positionData['player_id']);
        $metrics = $positionData['metrics'];
        
        if (!$player) {
            Log::warning("Player not found for metrics notification", [
                'player_id' => $positionData['player_id']
            ]);
            return;
        }
        
        $message = "🏆 Tournament Complete! You finished in position #{$positionData['position']} ";
        $message .= "in {$tournament->name}.\n\n";
        $message .= "📊 Your Performance:\n";
        $message .= "• Win Rate: " . number_format($metrics['win_rate'], 1) . "%\n";
        $message .= "• Total Points: {$metrics['total_points']}\n";
        $message .= "• Matches Played: {$metrics['matches_played']}\n";
        $message .= "• Wins: {$metrics['wins']}\n\n";
        $message .= "🎯 Position determined by performance metrics.\n";
        $message .= "Congratulations on completing the tournament! 🎉";
        
        // Create notification
        Notification::create([
            'player_id' => $player->id,
            'type' => 'tournament_position_metrics',
            'message' => $message,
            'title' => 'Tournament Complete - Position #' . $positionData['position'],
            'data' => [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'position' => $positionData['position'],
                'metrics' => $metrics,
                'message' => $message
            ]
        ]);
        
        // Send push notification if player has FCM token
        if ($player->fcm_token) {
            $pushTitle = "Tournament Complete!";
            $pushBody = "You finished #{$positionData['position']} in {$tournament->name}";
            
            app(\App\Services\PushNotificationService::class)->sendNotification(
                $player->fcm_token,
                $pushTitle,
                $pushBody,
                [
                    'type' => 'tournament_position_metrics',
                    'tournament_id' => $tournament->id,
                    'position' => $positionData['position']
                ]
            );
        }
        
        Log::info("Metrics-based position notification sent", [
            'player_id' => $player->id,
            'position' => $positionData['position'],
            'tournament_id' => $tournament->id
        ]);
    }

    /**
     * Send notifications for special tournament initialization
     */
    public static function sendSpecialTournamentNotifications(Tournament $tournament, $players): void
    {
        foreach ($players as $player) {
            Notification::create([
                'player_id' => $player->id,
                'type' => 'tournament_started',
                'message' => "You have been registered for the special tournament: {$tournament->name}. Check your matches to see your opponents.",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'tournament_type' => 'special'
                ]
            ]);
        }
        
        Log::info("Special tournament notifications sent", [
            'tournament_id' => $tournament->id,
            'players_notified' => count($players)
        ]);
    }

    /**
     * Send losers tournament notifications
     */
    public static function sendLosersTournamentNotifications(Tournament $tournament, string $level, ?int $groupId, int $winnersNeeded): void
    {
        // Get all winners for positions 4-6
        $allWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->whereIn('position', [4, 5, 6])
            ->with('player')
            ->get();
        
        foreach ($allWinners as $winner) {
            if ($winner->player) {
                $positionText = match($winner->position) {
                    4 => '4th',
                    5 => '5th', 
                    6 => '6th',
                    default => $winner->position . 'th'
                };
                
                $levelName = TournamentUtilityService::getLevelName($level, $groupId);
                $message = "Congratulations! You finished in {$positionText} place in the {$tournament->name}";
                if ($levelName) {
                    $message .= " ({$levelName} level)";
                }
                $message .= " tournament!";
                
                Notification::create([
                    'player_id' => $winner->player->id,
                    'type' => 'tournament_position',
                    'message' => $message,
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'level' => $level,
                        'level_name' => $levelName,
                        'position' => $winner->position,
                        'message' => $message
                    ]
                ]);
            }
        }
        
        Log::info("Sent losers tournament notifications", [
            'tournament_id' => $tournament->id,
            'winners_notified' => $allWinners->count(),
            'winners_needed' => $winnersNeeded
        ]);
    }

    /**
     * Send position notifications to players
     */
    public static function sendPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $positions): void
    {
        foreach ($positions as $position => $playerId) {
            $positionText = match($position) {
                1 => '1st',
                2 => '2nd',
                3 => '3rd',
                default => $position . 'th'
            };
            
            $message = "Congratulations! You finished in {$positionText} place in the {$tournament->name}";
            if ($levelName) {
                $message .= " ({$levelName} level)";
            }
            $message .= " tournament!";
            
            Notification::create([
                'player_id' => $playerId,
                'type' => 'tournament_position',
                'message' => $message,
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'level' => $level,
                    'level_name' => $levelName,
                    'position' => $position,
                    'message' => $message
                ]
            ]);
        }
        
        Log::info("Position notifications sent", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'positions_notified' => count($positions)
        ]);
    }

    /**
     * Send level completion notification to admin
     */
    public static function sendLevelCompletionNotification(Tournament $tournament, string $level, array $completionData): void
    {
        // Find admin users (you may need to adjust this based on your admin identification logic)
        $adminUsers = User::where('email', 'admin@cuesports.com')
            ->orWhere('role', 'admin')
            ->get();
        
        $message = "Level {$level} of tournament '{$tournament->name}' has been completed.";
        $message .= " Winners: " . count($completionData['winners'] ?? []);
        
        foreach ($adminUsers as $admin) {
            Notification::create([
                'player_id' => $admin->id,
                'type' => 'level_completion',
                'message' => "Tournament level {$level} completed for {$tournament->name}",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'level' => $level,
                    'completion_data' => $completionData,
                    'message' => $message
                ]
            ]);
        }
        
        Log::info("Level completion notification sent to admins", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'admins_notified' => $adminUsers->count()
        ]);
    }

    /**
     * Send round completion notification
     */
    public static function sendRoundCompletionNotification(Tournament $tournament, string $level, string $roundName, array $roundData): void
    {
        $playersInRound = collect($roundData['matches'] ?? [])->flatMap(function($match) {
            return [$match['player_1_id'] ?? null, $match['player_2_id'] ?? null];
        })->filter()->unique();
        
        $message = "Round {$roundName} has been completed in {$tournament->name}. Check your tournament progress!";
        
        foreach ($playersInRound as $playerId) {
            Notification::create([
                'player_id' => $playerId,
                'type' => 'round_completion',
                'message' => "Round {$roundName} completed in {$tournament->name}",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'level' => $level,
                    'round_name' => $roundName,
                    'message' => $message
                ]
            ]);
        }
        
        Log::info("Round completion notifications sent", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'round_name' => $roundName,
            'players_notified' => $playersInRound->count()
        ]);
    }
}
