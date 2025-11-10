<?php

namespace App\Services;

use App\Models\PoolMatch;
use App\Models\Tournament;
use App\Models\Winner;
use App\Models\User;
use App\Models\Notification;
use App\Services\TournamentUtilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ThreePlayerTournamentService
{
    /**
     * Main robust 3-player tournament handler with comprehensive subcase logic
     */
    public function determine3PlayerWinnersRobust(Tournament $tournament, string $level, ?int $groupId)
    {
        \Log::info("=== ROBUST 3-PLAYER TOURNAMENT HANDLER START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'group_id' => $groupId
        ]);
        
        $winnersNeeded = $tournament->winners ?? 3;
        
        // Handle winners tournament (A, B, C) first
        $winnersPositions = $this->handle3PlayerWinnersTournament($tournament, $level, $groupId);
        
        // If we need more than 3 winners, create losers tournament (D, E, F)
        if ($winnersNeeded > 3) {
            \Log::info("Creating losers tournament for positions 4-6", [
                'winners_needed' => $winnersNeeded
            ]);
            // Don't call deprecated method - positions will be created automatically
            // when losers matches complete via the progression system
            \Log::info("Losers tournament will be created automatically when needed");
        } else {
            \Log::info("Only 3 winners needed - skipping losers tournament", [
                'winners_needed' => $winnersNeeded
            ]);
        }
        
        \Log::info("=== ROBUST 3-PLAYER TOURNAMENT HANDLER END ===");
    }

    /**
     * Handle 3-player winners tournament (A, B, C) with all subcases
     */
    public function handle3PlayerWinnersTournament(Tournament $tournament, string $level, ?int $groupId)
    {
        \Log::info("Handling 3-player winners tournament", [
            'tournament_id' => $tournament->id
        ]);
        
        // Get all matches for this tournament level/group
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_SF')
            ->where('status', 'completed')
            ->first();
            
        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_final')
            ->first();
            
        $tieBreakerMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_tie_breaker')
            ->first();
            
        $fairChanceMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_fair_chance')
            ->first();
        
        if (!$sfMatch) {
            \Log::warning("No completed 3_winners_SF match found");
            return null;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayerId = $sfMatch->bye_player_id;
        $byePlayer = $byePlayerId ? User::find($byePlayerId) : null;
        
        \Log::info("3-player tournament state", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser,
            'bye_player' => $byePlayer->id ?? 'not_found'
        ]);
        
        // Handle different subcases based on match completion
        return $this->handle3PlayerSubcases($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, 
                                          $finalMatch, $tieBreakerMatch, $fairChanceMatch);
    }

    /**
     * Handle all 3-player tournament subcases
     */
    public function handle3PlayerSubcases(Tournament $tournament, string $level, ?int $groupId, 
                                         $sfWinner, $sfLoser, $byePlayer, $finalMatch, $tieBreakerMatch, $fairChanceMatch)
    {
        // Case 1: A plays B, C gets bye. Loser of SF (B) plays with C in final
        if (!$finalMatch || $finalMatch->status !== 'completed') {
            // Create final match if not exists: SF loser vs bye player
            if (!$finalMatch) {
                $this->create3PlayerFinalMatch($tournament, $level, $groupId, $sfLoser, $byePlayer->id);
                return null; // Wait for final to complete
            }
            return null; // Wait for final to complete
        }
        
        // Final match is completed - determine winner
        $finalWinner = $finalMatch->winner_id;
        $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;
        
        \Log::info("Final match completed", [
            'final_winner' => $finalWinner,
            'final_loser' => $finalLoser
        ]);
        
        // Subcase 1a: C (bye player) wins final
        if ($finalWinner === $byePlayer->id) {
            return $this->handle3PlayerSubcase1a($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, $tieBreakerMatch);
        }
        
        // Subcase 1b: C (bye player) loses final
        return $this->handle3PlayerSubcase1b($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, $fairChanceMatch);
    }

    /**
     * Handle subcase 1a: Bye player (C) wins final - need tie breaker with SF winner (A)
     */
    public function handle3PlayerSubcase1a(Tournament $tournament, string $level, ?int $groupId, 
                                          $sfWinner, $sfLoser, $byePlayer, $tieBreakerMatch)
    {
        \Log::info("Subcase 1a: Bye player won final - tie breaker needed", [
            'sf_winner' => $sfWinner,
            'bye_player' => $byePlayer->id,
            'sf_loser' => $sfLoser
        ]);
        
        // C wins final, C plays with A in tie breaker
        if (!$tieBreakerMatch || $tieBreakerMatch->status !== 'completed') {
            if (!$tieBreakerMatch) {
                $this->create3PlayerTieBreakerMatch($tournament, $level, $groupId, $byePlayer->id, $sfWinner);
                return null; // Wait for tie breaker
            }
            return null; // Wait for tie breaker to complete
        }
        
        // Tie breaker completed - assign positions
        $tieBreakerWinner = $tieBreakerMatch->winner_id;
        $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) ? $tieBreakerMatch->player_2_id : $tieBreakerMatch->player_1_id;
        
        // Position 1: Tie breaker winner
        // Position 2: Tie breaker loser  
        // Position 3: SF loser (B)
        $this->createWinnerPositions($tournament, $level, $groupId, [
            1 => $tieBreakerWinner,
            2 => $tieBreakerLoser,
            3 => $sfLoser
        ]);
        
        \Log::info("Subcase 1a positions assigned", [
            'position_1' => $tieBreakerWinner,
            'position_2' => $tieBreakerLoser,
            'position_3' => $sfLoser
        ]);
        
        return [
            'position_1' => $tieBreakerWinner,
            'position_2' => $tieBreakerLoser,
            'position_3' => $sfLoser
        ];
    }

    /**
     * Handle subcase 1b: Bye player (C) loses final - need fair chance with SF winner (A)
     */
    public function handle3PlayerSubcase1b(Tournament $tournament, string $level, ?int $groupId,
                                          $sfWinner, $sfLoser, $byePlayer, $fairChanceMatch)
    {
        \Log::info("Subcase 1b: Bye player lost final - fair chance needed", [
            'sf_winner' => $sfWinner,
            'bye_player' => $byePlayer->id,
            'sf_loser' => $sfLoser
        ]);
        
        // C loses final, C plays with A in fair chance
        if (!$fairChanceMatch || $fairChanceMatch->status !== 'completed') {
            if (!$fairChanceMatch) {
                $this->create3PlayerFairChanceMatch($tournament, $level, $groupId, $byePlayer->id, $sfWinner);
                return null; // Wait for fair chance
            }
            return null; // Wait for fair chance to complete
        }
        
        // Fair chance completed - check for triple tie
        $fairChanceWinner = $fairChanceMatch->winner_id;
        
        if ($fairChanceWinner === $byePlayer->id) {
            // Triple tie scenario - all three players have 1 win each
            return $this->handle3PlayerTripleTie($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer->id);
        } else {
            // Standard positions:
            // Position 1: SF winner (A) - won fair chance
            // Position 2: SF loser (B) - won final
            // Position 3: Bye player (C) - lost both final and fair chance
            $this->createWinnerPositions($tournament, $level, $groupId, [
                1 => $sfWinner,
                2 => $sfLoser,
                3 => $byePlayer->id
            ]);
            
            \Log::info("Subcase 1b standard positions assigned", [
                'position_1' => $sfWinner,
                'position_2' => $sfLoser,
                'position_3' => $byePlayer->id
            ]);
            
            return [
                'position_1' => $sfWinner,
                'position_2' => $sfLoser,
                'position_3' => $byePlayer->id
            ];
        }
    }

    /**
     * Handle triple tie scenario using metrics
     */
    public function handle3PlayerTripleTie(Tournament $tournament, string $level, ?int $groupId, $playerA, $playerB, $playerC)
    {
        \Log::info("Handling triple tie scenario - using metrics", [
            'players' => [$playerA, $playerB, $playerC]
        ]);
        
        // Calculate metrics for each player
        $playerMetrics = [];
        $players = [$playerA, $playerB, $playerC];
        
        foreach ($players as $playerId) {
            $matches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('group_id', $groupId)
                ->where(function($q) use ($playerId) {
                    $q->where('player_1_id', $playerId)->orWhere('player_2_id', $playerId);
                })
                ->where('status', 'completed')
                ->get();
            
            $totalPoints = 0;
            $wins = 0;
            $totalMatches = $matches->count();
            
            foreach ($matches as $match) {
                if ($match->player_1_id == $playerId) {
                    $totalPoints += $match->player_1_points ?? 0;
                    if ($match->winner_id == $playerId) $wins++;
                } else {
                    $totalPoints += $match->player_2_points ?? 0;
                    if ($match->winner_id == $playerId) $wins++;
                }
            }
            
            $winRate = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;
            
            $playerMetrics[$playerId] = [
                'player_id' => $playerId,
                'total_points' => $totalPoints,
                'wins' => $wins,
                'total_matches' => $totalMatches,
                'win_rate' => $winRate,
                'name' => User::find($playerId)->name ?? 'Unknown'
            ];
            
            \Log::info("Player metrics calculated", [
                'player_id' => $playerId,
                'win_rate' => $winRate,
                'total_points' => $totalPoints,
                'wins' => $wins,
                'matches' => $totalMatches
            ]);
        }
        
        // Sort by win rate first, then total points
        uasort($playerMetrics, function($a, $b) {
            if ($a['win_rate'] != $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate']; // Higher win rate first
            }
            return $b['total_points'] <=> $a['total_points']; // Higher points first
        });
        
        $sortedPlayers = array_keys($playerMetrics);
        
        // Assign positions based on metrics
        $this->createWinnerPositions($tournament, $level, $groupId, [
            1 => $sortedPlayers[0],
            2 => $sortedPlayers[1],
            3 => $sortedPlayers[2]
        ]);
        
        \Log::info("Triple tie positions assigned using metrics", [
            'position_1' => $sortedPlayers[0],
            'position_2' => $sortedPlayers[1],
            'position_3' => $sortedPlayers[2],
            'metrics' => $playerMetrics
        ]);
        
        return [
            'position_1' => $sortedPlayers[0],
            'position_2' => $sortedPlayers[1],
            'position_3' => $sortedPlayers[2],
            'metrics' => $playerMetrics
        ];
    }

    /**
     * Generic method to create 3-player matches
     */
    private function create3PlayerMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id, string $roundName, string $matchName, ?int $byePlayerId = null)
    {
        \Log::info("Creating 3-player match", [
            'match_name' => $matchName,
            'round_name' => $roundName,
            'player_1' => $player1Id,
            'player_2' => $player2Id,
            'bye_player' => $byePlayerId
        ]);
        
        // Determine level name - null for special tournaments, proper level name for regular tournaments
        $levelName = ($level === 'special' || $tournament->special) 
            ? null 
            : \App\Services\TournamentUtilityService::getLevelName($level, $groupId);

        \App\Services\MatchCreationService::createMatch(
            $tournament,
            User::find($player1Id),
            User::find($player2Id),
            $roundName,
            $level,
            $groupId,
            $levelName,
            $byePlayerId,
            $matchName
        );
    }

    /**
     * Create 3-player final match (LEGACY - use create3PlayerMatch instead)
     * @deprecated
     */
    public function create3PlayerFinalMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        $this->create3PlayerMatch($tournament, $level, $groupId, $player1Id, $player2Id, '3_winners_final', '3_winners_final_match');
    }

    /**
     * Create 3-player tie breaker match (LEGACY - use create3PlayerMatch instead)
     * @deprecated
     */
    public function create3PlayerTieBreakerMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        $this->create3PlayerMatch($tournament, $level, $groupId, $player1Id, $player2Id, '3_winners_tie_breaker', '3_winners_tie_breaker_match');
    }

    /**
     * Create 3-player fair chance match (LEGACY - use create3PlayerMatch instead)
     * @deprecated
     */
    public function create3PlayerFairChanceMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        $this->create3PlayerMatch($tournament, $level, $groupId, $player1Id, $player2Id, '3_winners_fair_chance', '3_winners_fair_chance_match');
    }

    /**
     * Generate 3-player tournament matches
     */
    public function generate3PlayerMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $levelName)
    {
        $pairedPlayers = $players->shuffle()->values();
        
        \Log::info("Creating 3-player tournament matches", [
            'players' => $pairedPlayers->map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->toArray()
        ]);
        
        // Create semifinal match with bye player
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $pairedPlayers[0],
            $pairedPlayers[1],
            '3_SF',
            $level,
            $groupId,
            $levelName,
            $pairedPlayers[2]->id,
            '3_SF_match'
        );
        
        \Log::info("Created 3-player tournament matches", [
            'sf_match' => $pairedPlayers[0]->name . ' vs ' . $pairedPlayers[1]->name,
            'bye_player' => $pairedPlayers[2]->name
        ]);
    }

    /**
     * Create winner positions
     */
    private function createWinnerPositions(Tournament $tournament, string $level, ?int $groupId, array $positions)
    {
        foreach ($positions as $position => $playerId) {
            Winner::create([
                'player_id' => $playerId,
                'position' => $position,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
        }
        
        // Send notifications - determine proper level name
        $levelName = ($level === 'special' || $tournament->special) 
            ? null 
            : \App\Services\TournamentUtilityService::getLevelName($level, $groupId);
        $this->sendPositionNotifications($tournament, $level, $levelName, $positions);
    }

    /**
     * Send position notifications to players
     */
    private function sendPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $positions)
    {
        foreach ($positions as $position => $playerId) {
            $this->sendPositionNotification($tournament, $playerId, $position, $level, $levelName);
        }
    }

    /**
     * Send position notification to player
     */
    private function sendPositionNotification(Tournament $tournament, $playerId, $position, $level, $levelName)
    {
        try {
            $positionText = match($position) {
                1 => '1st',
                2 => '2nd', 
                3 => '3rd',
                4 => '4th',
                5 => '5th',
                6 => '6th',
                default => $position . 'th'
            };

            $message = "Congratulations! You finished in {$positionText} place in the {$tournament->name}";
            if ($levelName) {
                $message .= " ({$levelName} level)";
            }
            $message .= " tournament!";

            \App\Models\Notification::create([
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

            \Log::info("Sent position notification", [
                'player_id' => $playerId,
                'position' => $position,
                'tournament_id' => $tournament->id
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to send position notification", [
                'player_id' => $playerId,
                'position' => $position,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle 3-player losers tournament (D, E, F) with all subcases
     */
    public function handle3PlayerLosersTournament(Tournament $tournament, string $level, ?int $groupId, int $winnersNeeded)
    {
        \Log::info("Handling 3-player losers tournament", [
            'tournament_id' => $tournament->id,
            'winners_needed' => $winnersNeeded
        ]);
        
        // Get all matches for this tournament level/group
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();
            
        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_final')
            ->first();
            
        $tieBreakerMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_tie_breaker')
            ->first();
            
        $fairChanceMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_fair_chance')
            ->first();
        
        if (!$sfMatch) {
            \Log::warning("No completed losers_3_SF match found");
            return null;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayerId = $sfMatch->bye_player_id;
        $byePlayer = $byePlayerId ? User::find($byePlayerId) : null;
        
        // Handle different subcases based on match completion
        return $this->handle3PlayerLosersSubcases($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, 
                                                $finalMatch, $tieBreakerMatch, $fairChanceMatch, $winnersNeeded);
    }

    /**
     * Handle all 3-player losers tournament subcases
     */
    public function handle3PlayerLosersSubcases(Tournament $tournament, string $level, ?int $groupId, 
                                               $sfWinner, $sfLoser, $byePlayer, $finalMatch, $tieBreakerMatch, $fairChanceMatch, $winnersNeeded)
    {
        // Case 1: D plays E, F gets bye. Loser of SF (E) plays with F in final
        if (!$finalMatch || $finalMatch->status !== 'completed') {
            // Create final match if not exists: SF loser vs bye player
            if (!$finalMatch) {
                $this->create3PlayerLosersFinalMatch($tournament, $level, $groupId, $sfLoser, $byePlayer->id);
                return null; // Wait for final to complete
            }
            return null; // Wait for final to complete
        }
        
        // Final match is completed - determine winner
        $finalWinner = $finalMatch->winner_id;
        $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;
        
        \Log::info("Losers final match completed", [
            'final_winner' => $finalWinner,
            'final_loser' => $finalLoser
        ]);
        
        // Subcase 1a: F (bye player) wins final
        if ($finalWinner === $byePlayer->id) {
            return $this->handle3PlayerLosersSubcase1a($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, $tieBreakerMatch, $winnersNeeded);
        }
        
        // Subcase 1b: F (bye player) loses final
        return $this->handle3PlayerLosersSubcase1b($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, $fairChanceMatch, $winnersNeeded);
    }

    /**
     * Handle losers subcase 1a: Bye player (F) wins final - need tie breaker with SF winner (D)
     */
    public function handle3PlayerLosersSubcase1a(Tournament $tournament, string $level, ?int $groupId, 
                                                $sfWinner, $sfLoser, $byePlayer, $tieBreakerMatch, $winnersNeeded)
    {
        \Log::info("Losers Subcase 1a: Bye player won final - tie breaker needed");
        
        // F wins final, F plays with D in tie breaker
        if (!$tieBreakerMatch || $tieBreakerMatch->status !== 'completed') {
            if (!$tieBreakerMatch) {
                $this->create3PlayerLosersTieBreakerMatch($tournament, $level, $groupId, $byePlayer->id, $sfWinner);
                return null; // Wait for tie breaker
            }
            return null; // Wait for tie breaker to complete
        }
        
        // DEPRECATED: Positions should be created by handleLosers3PlayerTieBreakerComplete instead
        \Log::warning("DEPRECATED: handle3PlayerLosersSubcase1a should not create positions - use progression system instead");
        
        // Tie breaker completed - return positions but don't create them
        $tieBreakerWinner = $tieBreakerMatch->winner_id;
        $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) ? $tieBreakerMatch->player_2_id : $tieBreakerMatch->player_1_id;
        
        // Position 4: Tie breaker winner
        // Position 5: Tie breaker loser  
        // Position 6: SF loser (E)
        // DON'T CREATE POSITIONS HERE - let the progression system handle it
        // $this->createWinnerPositions($tournament, $level, $groupId, [
        //     4 => $tieBreakerWinner,
        //     5 => $tieBreakerLoser,
        //     6 => $sfLoser
        // ]);
        
        \Log::info("Losers Subcase 1a positions assigned", [
            'position_4' => $tieBreakerWinner,
            'position_5' => $tieBreakerLoser,
            'position_6' => $sfLoser
        ]);
        
        return [
            'position_4' => $tieBreakerWinner,
            'position_5' => $tieBreakerLoser,
            'position_6' => $sfLoser
        ];
    }

    /**
     * Handle losers subcase 1b: Bye player (F) loses final - need fair chance with SF winner (D)
     */
    public function handle3PlayerLosersSubcase1b(Tournament $tournament, string $level, ?int $groupId,
                                                $sfWinner, $sfLoser, $byePlayer, $fairChanceMatch, $winnersNeeded)
    {
        \Log::info("Losers Subcase 1b: Bye player lost final - fair chance needed");
        
        // F loses final, F plays with D in fair chance
        if (!$fairChanceMatch || $fairChanceMatch->status !== 'completed') {
            if (!$fairChanceMatch) {
                $this->create3PlayerLosersFairChanceMatch($tournament, $level, $groupId, $byePlayer->id, $sfWinner);
                return null; // Wait for fair chance
            }
            return null; // Wait for fair chance to complete
        }
        
        // Fair chance completed - check for triple tie
        $fairChanceWinner = $fairChanceMatch->winner_id;
        
        if ($fairChanceWinner === $byePlayer->id) {
            // Triple tie scenario - all three players have 1 win each
            return $this->handle3PlayerLosersTripleTie($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer->id);
        } else {
            // DEPRECATED: Positions should be created by handleLosers3PlayerFairChanceComplete instead
            \Log::warning("DEPRECATED: handle3PlayerLosersSubcase1b should not create positions - use progression system instead");
            
            // Standard positions:
            // Position 4: SF winner (D) - won fair chance
            // Position 5: SF loser (E) - won final
            // Position 6: Bye player (F) - lost both final and fair chance
            // DON'T CREATE POSITIONS HERE - let the progression system handle it
            // $this->createWinnerPositions($tournament, $level, $groupId, [
            //     4 => $sfWinner,
            //     5 => $sfLoser,
            //     6 => $byePlayer->id
            // ]);
            
            \Log::info("Losers Subcase 1b standard positions assigned", [
                'position_4' => $sfWinner,
                'position_5' => $sfLoser,
                'position_6' => $byePlayer->id
            ]);
            
            return [
                'position_4' => $sfWinner,
                'position_5' => $sfLoser,
                'position_6' => $byePlayer->id
            ];
        }
    }

    /**
     * Handle losers triple tie scenario using metrics
     */
    public function handle3PlayerLosersTripleTie(Tournament $tournament, string $level, ?int $groupId, $playerD, $playerE, $playerF)
    {
        \Log::info("Handling losers triple tie scenario - using metrics", [
            'players' => [$playerD, $playerE, $playerF]
        ]);
        
        // Calculate metrics for each player (same logic as winners triple tie)
        $playerMetrics = [];
        $players = [$playerD, $playerE, $playerF];
        
        foreach ($players as $playerId) {
            $matches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('group_id', $groupId)
                ->where(function($q) use ($playerId) {
                    $q->where('player_1_id', $playerId)->orWhere('player_2_id', $playerId);
                })
                ->where('status', 'completed')
                ->get();
            
            $totalPoints = 0;
            $wins = 0;
            $totalMatches = $matches->count();
            
            foreach ($matches as $match) {
                if ($match->player_1_id == $playerId) {
                    $totalPoints += $match->player_1_points ?? 0;
                    if ($match->winner_id == $playerId) $wins++;
                } else {
                    $totalPoints += $match->player_2_points ?? 0;
                    if ($match->winner_id == $playerId) $wins++;
                }
            }
            
            $winRate = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;
            
            $playerMetrics[$playerId] = [
                'player_id' => $playerId,
                'total_points' => $totalPoints,
                'wins' => $wins,
                'total_matches' => $totalMatches,
                'win_rate' => $winRate,
                'name' => User::find($playerId)->name ?? 'Unknown'
            ];
        }
        
        // Sort by win rate first, then total points
        uasort($playerMetrics, function($a, $b) {
            if ($a['win_rate'] != $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate']; // Higher win rate first
            }
            return $b['total_points'] <=> $a['total_points']; // Higher points first
        });
        
        $sortedPlayers = array_keys($playerMetrics);
        
        // DEPRECATED: Positions should be created by handleLosers3PlayerFairChanceComplete instead
        \Log::warning("DEPRECATED: handle3PlayerLosersTripleTie should not create positions - use progression system instead");
        
        // Assign positions 4, 5, 6 based on metrics
        // DON'T CREATE POSITIONS HERE - let the progression system handle it
        // $this->createWinnerPositions($tournament, $level, $groupId, [
        //     4 => $sortedPlayers[0],
        //     5 => $sortedPlayers[1],
        //     6 => $sortedPlayers[2]
        // ]);
        
        \Log::info("Losers triple tie positions assigned using metrics", [
            'position_4' => $sortedPlayers[0],
            'position_5' => $sortedPlayers[1],
            'position_6' => $sortedPlayers[2],
            'metrics' => $playerMetrics
        ]);
        
        return [
            'position_4' => $sortedPlayers[0],
            'position_5' => $sortedPlayers[1],
            'position_6' => $sortedPlayers[2],
            'metrics' => $playerMetrics
        ];
    }

    /**
     * Create 3-player losers final match (LEGACY - use create3PlayerMatch instead)
     * @deprecated
     */
    public function create3PlayerLosersFinalMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        $this->create3PlayerMatch($tournament, $level, $groupId, $player1Id, $player2Id, 'losers_3_final', 'losers_3_final_match');
    }

    /**
     * Create 3-player losers tie breaker match (LEGACY - use create3PlayerMatch instead)
     * @deprecated
     */
    public function create3PlayerLosersTieBreakerMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        $this->create3PlayerMatch($tournament, $level, $groupId, $player1Id, $player2Id, 'losers_3_tie_breaker', 'losers_3_tie_breaker_match');
    }

    /**
     * Create 3-player losers fair chance match (LEGACY - use create3PlayerMatch instead)
     * @deprecated
     */
    public function create3PlayerLosersFairChanceMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        $this->create3PlayerMatch($tournament, $level, $groupId, $player1Id, $player2Id, 'losers_3_fair_chance', 'losers_3_fair_chance_match');
    }


    /**
     * Determine winners for 3-player standard tournaments
     */
    public function determineStandard3PlayerWinners(Tournament $tournament, string $level, ?int $groupId, bool $isTargetLevel)
    {
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();
            
        if (!$sfMatch) {
            \Log::warning("No completed 3_SF match found for standard tournament");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        // DO NOT CREATE POSITIONS HERE - Tournament is not complete yet!
        // Positions should only be created after final, tie-breaker, or fair chance matches complete
        
        \Log::info("3-player SF completed - tournament flow will continue automatically", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser,
            'bye_player' => $byePlayer,
            'tournament_id' => $tournament->id,
            'message' => 'Positions will be determined after final tournament flow completes'
        ]);
        
        // The automated flow will handle:
        // 1. Create final match (SF loser vs bye player)
        // 2. Create tie-breaker or fair chance match based on final result
        // 3. Determine final positions only after all matches complete
    }

    /**
     * Generate comprehensive 3-player tournament for 3, 4, 5, or 6 winners
     * Handles both winners tournament (A, B, C) and losers tournament (D, E, F) if needed
     */
    public function generateComprehensive3PlayerTournament(Tournament $tournament, string $level, ?string $levelName, array $winners, int $winnersNeeded)
    {
        \Log::info("=== GENERATE COMPREHENSIVE 3-PLAYER TOURNAMENT START ===", [
            'winners' => $winners,
            'winners_needed' => $winnersNeeded,
            'level' => $level
        ]);
        
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        // Generate winners tournament (A, B, C) - positions 1, 2, 3
        $this->generate3PlayerWinnersTournament($tournament, $level, $levelName, $winners, $groupId);
        
        // Generate losers tournament (D, E, F) - positions 4, 5, 6 (only if we need more than 3 winners)
        if ($winnersNeeded > 3) {
            $this->generate3PlayerLosersTournament($tournament, $level, $levelName, $winnersNeeded, $groupId);
        }
        
        \Log::info("=== GENERATE COMPREHENSIVE 3-PLAYER TOURNAMENT END ===");
    }

    /**
     * Generate 3-player winners tournament (A, B, C) for positions 1, 2, 3
     */
    public function generate3PlayerWinnersTournament(Tournament $tournament, string $level, ?string $levelName, array $winners, $groupId)
    {
        \Log::info("Generating 3-player winners tournament", [
            'winners' => $winners
        ]);
        
        // Create 3_SF: A vs B (C gets bye) - winners semifinal
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            User::find($winners[0]),
            User::find($winners[1]),
            '3_SF',
            $level,
            $groupId,
            $levelName,
            $winners[2],
            '3_SF_match'
        );
        
        // Send notifications to players about the new winners semifinal match
        $this->sendMatchNotifications($tournament, $winners[0], $winners[1], '3_SF', 'Winners Semifinal');
        
        \Log::info("3-player winners tournament matches created", [
            'sf_match' => '3_SF',
            'player_1' => $winners[0],
            'player_2' => $winners[1],
            'bye_player' => $winners[2]
        ]);
    }

    /**
     * Generate 3-player losers tournament (D, E, F) for positions 4, 5, 6
     */
    public function generate3PlayerLosersTournament(Tournament $tournament, string $level, ?string $levelName, int $winnersNeeded, $groupId)
    {
        \Log::info("Generating 3-player losers tournament", [
            'winners_needed' => $winnersNeeded
        ]);
        
        // Get losers from the completed matches
        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->whereNotIn('round_name', ['losers_3_SF', 'losers_3_final', 'losers_3_tie_breaker', 'losers_3_fair_chance'])
            ->get();
        
        $losers = collect();
        foreach ($completedMatches as $match) {
            if ($match->winner_id) {
                $loserId = ($match->player_1_id === $match->winner_id) ? $match->player_2_id : $match->player_1_id;
                $losers->push($loserId);
            }
        }
        
        if ($losers->count() < 3) {
            \Log::warning("Not enough losers for 3-player losers tournament", [
                'losers_count' => $losers->count()
            ]);
            return;
        }
        
        $losersArray = $losers->take(3)->unique()->values()->toArray();
        
        // Create losers_3_SF: D vs E (F gets bye)
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            User::find($losersArray[0]),
            User::find($losersArray[1]),
            'losers_3_SF',
            $level,
            $groupId,
            $levelName,
            $losersArray[2],
            'losers_3_SF_match'
        );
        
        \Log::info("3-player losers tournament matches created", [
            'sf_match' => 'losers_3_SF',
            'player_1' => $losersArray[0],
            'player_2' => $losersArray[1],
            'bye_player' => $losersArray[2]
        ]);
    }

    /**
     * Check 3-player tournament progression and generate next matches
     */
    public function check3PlayerTournamentProgression(Tournament $tournament, string $level, ?string $levelName, string $completedRound): array
    {
        \Log::info("=== CHECK 3-PLAYER TOURNAMENT PROGRESSION START ===", [
            'completed_round' => $completedRound,
            'level' => $level,
            'tournament_id' => $tournament->id
        ]);
        
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
            \Log::info("Using default groupId=1 for special tournament", [
                'tournament_id' => $tournament->id,
                'level' => $level
            ]);
        }
        
        // First check if we need to create initial 3-player semifinal based on winner count
        if ($this->shouldCreate3PlayerSemifinal($tournament, $level, $levelName, $groupId, $completedRound)) {
            \Log::info("Round {$completedRound} completed with 3 winners - creating 3-player semifinal");
            $this->create3PlayerSemifinalFromCompletedRound($tournament, $level, $levelName, $groupId, $completedRound);
            return [
                'status' => 'success',
                'message' => '3-player semifinal created successfully',
                'progression_complete' => true
            ];
        }

        switch ($completedRound) {
            case '3_SF':
                // Handle the semifinal we created from any round with 3 winners
                $this->handle3PlayerSFComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_winners_SF':
                $this->handle3PlayerWinnersSFComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_final':
                // Handle the final we created from 3_SF
                $this->handle3PlayerFinalComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_winners_final':
                $this->handle3PlayerWinnersFinalComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_tie_breaker':
                // Handle tie-breaker completion (CASE 1)
                $this->handle3PlayerTieBreakerComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_fair_chance':
                // Handle fair chance completion (CASE 2)
                $this->handle3PlayerFairChanceComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_winners_tie_breaker':
            case '3_winners_fair_chance':
                $this->handle3PlayerWinnersComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case 'losers_3_SF':
                // Handle losers semifinal completion
                $this->handleLosers3PlayerSFComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case 'losers_3_final':
                // Handle losers final completion
                $this->handleLosers3PlayerFinalComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case 'losers_3_tie_breaker':
                // Handle losers tie-breaker completion
                $this->handleLosers3PlayerTieBreakerComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case 'losers_3_fair_chance':
                // Handle losers fair chance completion
                $this->handleLosers3PlayerFairChanceComplete($tournament, $level, $levelName, $groupId);
                break;
                
            default:
                // For any other round, check if we have exactly 3 winners to create semifinal
                $winners = $this->getWinnersFromCompletedRound($tournament, $level, $groupId, $completedRound);
                
                \Log::info("Default case - checking winner count for progression", [
                    'completed_round' => $completedRound,
                    'winner_count' => $winners->count(),
                    'tournament_id' => $tournament->id
                ]);
                
                if ($winners->count() === 3) {
                    // Check if 3_SF already exists
                    $existing3SF = PoolMatch::where('tournament_id', $tournament->id)
                        ->where('level', $level)
                        ->where('group_id', $groupId)
                        ->where('round_name', '3_SF')
                        ->exists();
                        
                    if (!$existing3SF) {
                        \Log::info("Creating 3-player semifinal from {$completedRound} with 3 winners");
                        $this->create3PlayerSemifinalFromCompletedRound($tournament, $level, $levelName, $groupId, $completedRound);
                        return [
                            'status' => 'success',
                            'message' => '3-player semifinal created from completed round',
                            'progression_complete' => true
                        ];
                    }
                }
                
                \Log::info("No specific progression logic for round: {$completedRound}");
                return [
                    'status' => 'success',
                    'message' => "Round {$completedRound} completed, no further progression needed",
                    'progression_complete' => false
                ];
        }
        
        \Log::info("=== CHECK 3-PLAYER TOURNAMENT PROGRESSION END ===");
        
        return [
            'status' => 'success', 
            'message' => 'Tournament progression handled successfully',
            'progression_complete' => true
        ];
    }

    /**
     * Handle 3-player winners SF completion
     */
    public function handle3PlayerWinnersSFComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3-player winners SF completion");
        
        // Get SF match
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_SF')
            ->where('status', 'completed')
            ->first();
            
        if (!$sfMatch) {
            \Log::warning("No completed 3_winners_SF match found");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        // Create final match: SF loser vs bye player
        $this->create3PlayerFinalMatch($tournament, $level, $groupId, $sfLoser, $byePlayer);
    }

    /**
     * Handle 3-player winners final completion
     */
    public function handle3PlayerWinnersFinalComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3-player winners final completion");
        
        // Get matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_SF')
            ->where('status', 'completed')
            ->first();
            
        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_final')
            ->where('status', 'completed')
            ->first();
            
        if (!$sfMatch || !$finalMatch) {
            \Log::warning("Missing required matches for winners final completion");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        $finalWinner = $finalMatch->winner_id;
        
        // Check if bye player won final - need tie breaker
        if ($finalWinner === $byePlayer) {
            $this->create3PlayerTieBreakerMatch($tournament, $level, $groupId, $byePlayer, $sfWinner);
        } else {
            // SF loser won final - need fair chance match
            $this->create3PlayerFairChanceMatch($tournament, $level, $groupId, $byePlayer, $sfWinner);
        }
    }

    /**
     * Handle 3-player losers SF completion
     */
    public function handle3PlayerLosersSFComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3-player losers SF completion");
        
        // Get SF match
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();
            
        if (!$sfMatch) {
            \Log::warning("No completed losers_3_SF match found");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        // Create losers final match: SF loser vs bye player
        $this->create3PlayerLosersFinalMatch($tournament, $level, $groupId, $sfLoser, $byePlayer);
    }

    /**
     * Handle 3-player losers final completion
     */
    public function handle3PlayerLosersFinalComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3-player losers final completion");
        
        // Get matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();
            
        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_final')
            ->where('status', 'completed')
            ->first();
            
        if (!$sfMatch || !$finalMatch) {
            \Log::warning("Missing required matches for losers final completion");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        $finalWinner = $finalMatch->winner_id;
        
        // Check if bye player won final - need tie breaker
        if ($finalWinner === $byePlayer) {
            $this->create3PlayerLosersTieBreakerMatch($tournament, $level, $groupId, $byePlayer, $sfWinner);
        } else {
            // SF loser won final - need fair chance match
            $this->create3PlayerLosersFairChanceMatch($tournament, $level, $groupId, $byePlayer, $sfWinner);
        }
    }

    /**
     * Handle 3-player winners tournament completion
     */
    public function handle3PlayerWinnersComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("3-player winners tournament complete - determining winners");
        
        // Use the robust winner determination logic
        $this->determine3PlayerWinnersRobust($tournament, $level, $groupId);
    }

    /**
     * Handle 3-player losers tournament completion
     */
    public function handle3PlayerLosersComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("3-player losers tournament complete - determining winners");
        
        // Positions will be determined automatically by the progression system
        // when losers tie-breaker or fair chance matches complete
        \Log::info("Losers tournament positions will be determined automatically");
    }

    /**
     * Generate standard 3-player positions from semifinals (for 3 winners needed)
     */
    public function generateStandard3PlayerPositions(Tournament $tournament, string $level, ?string $levelName, $winnersFinal, $losersSemifinal)
    {
        \Log::info("Generating 3 positions directly from standard semifinals", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_final_id' => $winnersFinal->id,
            'losers_semifinal_id' => $losersSemifinal->id
        ]);
        
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        // Position 1: Winner of winners_final
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $winnersFinal->winner_id,
            'position' => 1,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Position 2: Loser of winners_final
        $winnersLoser = ($winnersFinal->player_1_id === $winnersFinal->winner_id) ? $winnersFinal->player_2_id : $winnersFinal->player_1_id;
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $winnersLoser,
            'position' => 2,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Position 3: Winner of losers_semifinal
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersSemifinal->winner_id,
            'position' => 3,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Send notifications
        $this->sendPositionNotifications($tournament, $level, $levelName, [
            1 => $winnersFinal->winner_id,
            2 => $winnersLoser,
            3 => $losersSemifinal->winner_id
        ]);
        
        \Log::info("Standard 3-player positions created successfully");
    }

    /**
     * Check if tie-breaker is needed for 3-player tournament
     */
    public function checkIfTieBreakerNeeded(int $tournamentId, string $level, ?string $levelName): bool
    {
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && $level === 'special') {
            $groupId = 1;
        }
        
        $sfMatch = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();
            
        $finalMatch = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_final')
            ->where('status', 'completed')
            ->first();
        
        if (!$sfMatch || !$finalMatch) {
            return false;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $finalWinner = $finalMatch->winner_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        // Tie-breaker needed if bye player won the final
        return $finalWinner === $byePlayer;
    }

    /**
     * Check if we should create 3-player semifinal based on winner count
     */
    private function shouldCreate3PlayerSemifinal(Tournament $tournament, string $level, ?string $levelName, $groupId, string $completedRound): bool
    {
        // Don't create if this is already a 3-player specific round
        $threePlayerRounds = ['3_winners_SF', '3_winners_final', '3_winners_tie_breaker', '3_winners_fair_chance', 
                             'losers_3_SF', 'losers_3_final', 'losers_3_tie_breaker', 'losers_3_fair_chance'];
        
        if (in_array($completedRound, $threePlayerRounds)) {
            return false;
        }

        // Get winners from the completed round
        $winners = $this->getWinnersFromCompletedRound($tournament, $level, $groupId, $completedRound);
        
        \Log::info("Checking if should create 3-player semifinal", [
            'completed_round' => $completedRound,
            'winner_count' => $winners->count(),
            'tournament_id' => $tournament->id
        ]);

        // Create 3-player semifinal if exactly 3 winners and no existing 3_SF match
        if ($winners->count() === 3) {
            $existing3SF = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('group_id', $groupId)
                ->where('round_name', '3_SF')
                ->exists();
                
            return !$existing3SF;
        }

        return false;
    }

    /**
     * Get winners from any completed round - SIMPLIFIED: Just get winners from completed round
     */
    private function getWinnersFromCompletedRound(Tournament $tournament, string $level, $groupId, string $completedRound)
    {
        \Log::info("=== GETTING WINNERS FROM COMPLETED ROUND (SIMPLIFIED) ===", [
            'tournament_id' => $tournament->id,
            'completed_round' => $completedRound
        ]);

        // Get ALL completed matches for this tournament and round - no level/group restrictions
        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('round_name', $completedRound)
            ->where('status', 'completed')
            ->get();

        \Log::info("Found completed matches (no level/group filter)", [
            'match_count' => $completedMatches->count(),
            'matches' => $completedMatches->map(function($match) {
                return [
                    'id' => $match->id,
                    'level' => $match->level,
                    'group_id' => $match->group_id,
                    'round_name' => $match->round_name,
                    'status' => $match->status,
                    'winner_id' => $match->winner_id,
                    'player_1_id' => $match->player_1_id,
                    'player_2_id' => $match->player_2_id
                ];
            })->toArray()
        ]);

        $winners = collect();
        foreach ($completedMatches as $match) {
            if ($match->winner_id) {
                $winner = User::find($match->winner_id);
                if ($winner) {
                    $winners->push($winner);
                    \Log::info("Added winner", [
                        'winner_id' => $winner->id,
                        'winner_name' => $winner->name,
                        'from_match' => $match->id
                    ]);
                }
            } else {
                \Log::warning("Match has no winner_id", [
                    'match_id' => $match->id,
                    'round_name' => $match->round_name
                ]);
            }
        }

        \Log::info("Final winners collected (simplified)", [
            'winner_count' => $winners->count(),
            'winner_names' => $winners->pluck('name')->toArray()
        ]);

        return $winners;
    }

    /**
     * Create 3-player semifinal from any completed round with 3 winners
     */
    private function create3PlayerSemifinalFromCompletedRound(Tournament $tournament, string $level, ?string $levelName, $groupId, string $completedRound)
    {
        $winners = $this->getWinnersFromCompletedRound($tournament, $level, $groupId, $completedRound);

        \Log::info("Creating 3-player semifinal from completed round", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'completed_round' => $completedRound,
            'winner_count' => $winners->count(),
            'winners' => $winners->pluck('name')->toArray()
        ]);

        if ($winners->count() === 3) {
            // Create 3-player semifinal match (2 players play, 1 gets bye)
            $shuffledWinners = $winners->shuffle();
            
            $this->create3PlayerMatch(
                $tournament, 
                $level, 
                $groupId, 
                $shuffledWinners[0]->id, 
                $shuffledWinners[1]->id, 
                '3_SF', 
                '3_SF_match',
                $shuffledWinners[2]->id // bye player
            );

            // Send notifications to players about the new semifinal match
            $this->sendMatchNotifications($tournament, $shuffledWinners[0]->id, $shuffledWinners[1]->id, '3_SF', 'Semifinal');

            \Log::info("Created 3-player semifinal match", [
                'player_1' => $shuffledWinners[0]->name,
                'player_2' => $shuffledWinners[1]->name,
                'bye_player' => $shuffledWinners[2]->name,
                'round_name' => '3_SF',
                'from_round' => $completedRound
            ]);
        } else {
            \Log::error("Expected 3 winners but found {$winners->count()}", [
                'tournament_id' => $tournament->id,
                'level' => $level,
                'completed_round' => $completedRound
            ]);
        }
    }

    /**
     * Handle 3_SF completion - create final match
     */
    private function handle3PlayerSFComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3_SF completion - creating final match", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get the SF match
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$sfMatch->winner_id) {
            \Log::error("3_SF match not found or no winner", [
                'tournament_id' => $tournament->id,
                'level' => $level
            ]);
            return;
        }

        $sfWinner = User::find($sfMatch->winner_id);
        $byePlayer = User::find($sfMatch->bye_player_id);

        if (!$sfWinner || !$byePlayer) {
            \Log::error("Could not find SF winner or bye player", [
                'sf_winner_id' => $sfMatch->winner_id,
                'bye_player_id' => $sfMatch->bye_player_id
            ]);
            return;
        }

        // Check if final already exists
        $existingFinal = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_final')
            ->exists();

        if ($existingFinal) {
            \Log::info("3_final match already exists");
            return;
        }

        // Get SF loser for the final match
        $sfLoser = ($sfMatch->player_1_id === $sfMatch->winner_id) 
            ? User::find($sfMatch->player_2_id) 
            : User::find($sfMatch->player_1_id);

        if (!$sfLoser) {
            \Log::error("Could not find SF loser for final match", [
                'sf_match_id' => $sfMatch->id
            ]);
            return;
        }

        // Create final match: SF loser vs bye player (CORRECT FLOW)
        $this->create3PlayerMatch($tournament, $level, $groupId, $sfLoser->id, $byePlayer->id, '3_final', '3_final_match');

        // Send notifications to players about the new final match
        $this->sendMatchNotifications($tournament, $sfLoser->id, $byePlayer->id, '3_final', 'Final');

        \Log::info("Created 3_final match", [
            'sf_loser' => $sfLoser->name,
            'bye_player' => $byePlayer->name,
            'sf_winner' => $sfWinner->name,
            'tournament_id' => $tournament->id
        ]);

        // Check if we need losers tournament for more winners
        $winnersNeeded = $tournament->winners ?? 3;
        if ($winnersNeeded > 3) {
            $this->createLosers3PlayerTournamentIfNeeded($tournament, $level, $levelName, $groupId, $sfMatch);
        }
    }

    /**
     * Create losers tournament if more than 3 winners needed
     */
    private function createLosers3PlayerTournamentIfNeeded(Tournament $tournament, string $level, ?string $levelName, $groupId, $sfMatch)
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        if ($winnersNeeded <= 3) {
            return; // No losers tournament needed
        }

        \Log::info("Creating losers tournament for additional positions", [
            'winners_needed' => $winnersNeeded,
            'tournament_id' => $tournament->id
        ]);

        // Get the 3 losers from the initial round that created the 3 winners
        $losers = $this->getLosersFromInitialRound($tournament, $level, $groupId);
        
        if ($losers->count() !== 3) {
            \Log::error("Expected 3 losers for losers tournament but found {$losers->count()}");
            return;
        }

        // Check if losers SF already exists
        $existingLosersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->exists();

        if ($existingLosersSF) {
            \Log::info("Losers 3_SF already exists");
            return;
        }

        // Create losers semifinal (same structure as winners)
        $shuffledLosers = $losers->shuffle();
        
        $this->create3PlayerMatch(
            $tournament,
            $level,
            $groupId,
            $shuffledLosers[0]->id,
            $shuffledLosers[1]->id,
            'losers_3_SF',
            'losers_3_SF_match',
            $shuffledLosers[2]->id // bye player
        );

        // Send notifications to players about the new losers semifinal match
        $this->sendMatchNotifications($tournament, $shuffledLosers[0]->id, $shuffledLosers[1]->id, 'losers_3_SF', 'Losers Semifinal');

        \Log::info("Created losers 3_SF match for positions 4, 5, 6", [
            'player_1' => $shuffledLosers[0]->name,
            'player_2' => $shuffledLosers[1]->name,
            'bye_player' => $shuffledLosers[2]->name
        ]);
    }

    /**
     * Create losers tournament for additional winners (public method for TournamentProgressionService)
     */
    public function createLosers3PlayerTournamentForProgression(Tournament $tournament, string $level, ?int $groupId, int $winnersNeeded): array
    {
        \Log::info("Creating losers tournament for additional positions", [
            'winners_needed' => $winnersNeeded,
            'tournament_id' => $tournament->id
        ]);

        // Get the 3 losers from the initial round that created the 3 winners
        $losers = $this->getLosersFromInitialRound($tournament, $level, $groupId);
        
        if ($losers->count() !== 3) {
            \Log::error("Expected 3 losers for losers tournament but found {$losers->count()}");
            return ['status' => 'error', 'message' => 'Could not find 3 losers for losers tournament'];
        }

        // Check if losers SF already exists
        $existingLosersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->exists();

        if ($existingLosersSF) {
            \Log::info("Losers 3_SF already exists");
            return ['status' => 'success', 'message' => 'Losers semifinal already exists'];
        }

        // Create losers semifinal (same structure as winners)
        $shuffledLosers = $losers->shuffle();
        
        $this->create3PlayerMatch(
            $tournament,
            $level,
            $groupId,
            $shuffledLosers[0]->id,
            $shuffledLosers[1]->id,
            'losers_3_SF',
            'losers_3_SF_match',
            $shuffledLosers[2]->id // bye player
        );

        // Send notifications to players about the new losers semifinal match
        $this->sendMatchNotifications($tournament, $shuffledLosers[0]->id, $shuffledLosers[1]->id, 'losers_3_SF', 'Losers Semifinal');

        \Log::info("Created losers 3_SF match for positions 4, 5, 6", [
            'player_1' => $shuffledLosers[0]->name,
            'player_2' => $shuffledLosers[1]->name,
            'bye_player' => $shuffledLosers[2]->name
        ]);

        return ['status' => 'success', 'message' => 'Losers semifinal created successfully'];
    }

    /**
     * Get losers from the initial round that produced the 3 winners
     */
    private function getLosersFromInitialRound(Tournament $tournament, string $level, $groupId)
    {
        // Find the round that produced exactly 3 winners
        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->whereNotIn('round_name', ['3_SF', '3_final', '3_tie_breaker', '3_fair_chance', 'losers_3_SF', 'losers_3_final', 'losers_3_tie_breaker', 'losers_3_fair_chance'])
            ->get();

        $losers = collect();
        foreach ($completedMatches as $match) {
            if ($match->winner_id) {
                // Get the loser
                $loserId = ($match->player_1_id === $match->winner_id) 
                    ? $match->player_2_id 
                    : $match->player_1_id;
                
                $loser = User::find($loserId);
                if ($loser) {
                    $losers->push($loser);
                }
            }
        }

        return $losers;
    }

    /**
     * Handle 3_final completion - determine next step based on winner
     */
    private function handle3PlayerFinalComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3_final completion - determining next step", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get both SF and final matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();

        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_final')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$finalMatch || !$finalMatch->winner_id) {
            \Log::error("Required matches not found or no final winner", [
                'sf_match_found' => !!$sfMatch,
                'final_match_found' => !!$finalMatch,
                'final_winner' => $finalMatch->winner_id ?? 'none'
            ]);
            return;
        }

        $finalWinner = $finalMatch->winner_id;
        $finalLoser = ($finalMatch->player_1_id === $finalWinner) 
            ? $finalMatch->player_2_id 
            : $finalMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        $sfWinner = $sfMatch->winner_id;

        // CASE 1: Bye player won the final
        if ($finalWinner === $byePlayer) {
            \Log::info("CASE 1: Bye player won final - creating tie-breaker with SF winner");
            $this->create3PlayerTieBreaker($tournament, $level, $levelName, $groupId, $byePlayer, $sfWinner);
            return;
        }

        // CASE 2: SF loser won the final (bye player lost)
        \Log::info("CASE 2: SF loser won final - creating fair chance match");
        $this->create3PlayerFairChance($tournament, $level, $levelName, $groupId, $byePlayer, $sfWinner);
    }

    /**
     * Create tie-breaker match when bye player wins final (CASE 1)
     */
    private function create3PlayerTieBreaker(Tournament $tournament, string $level, ?string $levelName, $groupId, $byePlayer, $sfWinner)
    {
        // Check if tie-breaker already exists
        $existingTieBreaker = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_tie_breaker')
            ->exists();

        if ($existingTieBreaker) {
            \Log::info("3_tie_breaker match already exists");
            return;
        }

        $this->create3PlayerMatch($tournament, $level, $groupId, $byePlayer, $sfWinner, '3_tie_breaker', '3_tie_breaker_match');

        // Send notifications to players about the new tie-breaker match
        $this->sendMatchNotifications($tournament, $byePlayer, $sfWinner, '3_tie_breaker', 'Tie-Breaker');

        \Log::info("Created 3_tie_breaker match", [
            'bye_player' => User::find($byePlayer)->name ?? 'Unknown',
            'sf_winner' => User::find($sfWinner)->name ?? 'Unknown',
            'tournament_id' => $tournament->id
        ]);
    }

    /**
     * Create fair chance match when bye player loses final (CASE 2)
     */
    private function create3PlayerFairChance(Tournament $tournament, string $level, ?string $levelName, $groupId, $byePlayer, $sfWinner)
    {
        // Check if fair chance already exists
        $existingFairChance = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_fair_chance')
            ->exists();

        if ($existingFairChance) {
            \Log::info("3_fair_chance match already exists");
            return;
        }

        $this->create3PlayerMatch($tournament, $level, $groupId, $byePlayer, $sfWinner, '3_fair_chance', '3_fair_chance_match');

        // Send notifications to players about the new fair chance match
        $this->sendMatchNotifications($tournament, $byePlayer, $sfWinner, '3_fair_chance', 'Fair Chance');

        \Log::info("Created 3_fair_chance match", [
            'bye_player' => User::find($byePlayer)->name ?? 'Unknown',
            'sf_winner' => User::find($sfWinner)->name ?? 'Unknown',
            'tournament_id' => $tournament->id
        ]);
    }

    /**
     * Create final positions for 3-player tournament (LEGACY - use createWinnerPositions instead)
     * @deprecated
     */
    private function create3PlayerPositions(Tournament $tournament, string $level, ?string $levelName, $groupId, $finalWinner, $finalLoser, $byePlayer)
    {
        $positions = [
            1 => $finalWinner,
            2 => $finalLoser,
            3 => $byePlayer
        ];
        
        $this->createWinnerPositions($tournament, $level, $groupId, $positions);
    }

    /**
     * Handle 3_tie_breaker completion - CASE 1 final positions
     */
    private function handle3PlayerTieBreakerComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3_tie_breaker completion - CASE 1", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get all required matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();

        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_final')
            ->where('status', 'completed')
            ->first();

        $tieBreakerMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_tie_breaker')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$finalMatch || !$tieBreakerMatch || !$tieBreakerMatch->winner_id) {
            \Log::error("Required matches not found for tie-breaker completion", [
                'sf_match' => !!$sfMatch,
                'final_match' => !!$finalMatch,
                'tie_breaker_match' => !!$tieBreakerMatch,
                'tie_breaker_winner' => $tieBreakerMatch->winner_id ?? 'none'
            ]);
            return;
        }

        // CASE 1 positions: Tie-breaker winner = pos 1, loser = pos 2, SF loser = pos 3
        $tieBreakerWinner = $tieBreakerMatch->winner_id; // Position 1
        $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) 
            ? $tieBreakerMatch->player_2_id 
            : $tieBreakerMatch->player_1_id; // Position 2
        
        // SF loser is position 3 (the one who lost in final)
        $sfLoser = ($sfMatch->player_1_id === $sfMatch->winner_id) 
            ? $sfMatch->player_2_id 
            : $sfMatch->player_1_id;

        \Log::info("CASE 1 - Tie-breaker positions determined", [
            'position_1' => $tieBreakerWinner,
            'position_2' => $tieBreakerLoser,
            'position_3' => $sfLoser
        ]);

        $this->create3PlayerPositions($tournament, $level, $levelName, $groupId, $tieBreakerWinner, $tieBreakerLoser, $sfLoser);
    }

    /**
     * Handle 3_fair_chance completion - CASE 2 positions
     */
    private function handle3PlayerFairChanceComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3_fair_chance completion - CASE 2", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get all required matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();

        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_final')
            ->where('status', 'completed')
            ->first();

        $fairChanceMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_fair_chance')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$finalMatch || !$fairChanceMatch || !$fairChanceMatch->winner_id) {
            \Log::error("Required matches not found for fair chance completion", [
                'sf_match' => !!$sfMatch,
                'final_match' => !!$finalMatch,
                'fair_chance_match' => !!$fairChanceMatch,
                'fair_chance_winner' => $fairChanceMatch->winner_id ?? 'none'
            ]);
            return;
        }

        $fairChanceWinner = $fairChanceMatch->winner_id;
        $sfWinner = $sfMatch->winner_id;
        $byePlayer = $sfMatch->bye_player_id;

        // CASE 2A: SF winner beats bye player in fair chance
        if ($fairChanceWinner === $sfWinner) {
            \Log::info("CASE 2A: SF winner won fair chance");
            
            $sfLoser = ($sfMatch->player_1_id === $sfWinner) 
                ? $sfMatch->player_2_id 
                : $sfMatch->player_1_id;

            // Positions: SF winner = pos 1, SF loser = pos 2, bye player = pos 3
            $this->create3PlayerPositions($tournament, $level, $levelName, $groupId, $sfWinner, $sfLoser, $byePlayer);
            return;
        }

        // CASE 2B: Bye player wins fair chance - determine by performance metrics
        \Log::info("CASE 2B: Bye player won fair chance - determining by performance metrics");
        $this->determine3PlayerPositionsByMetrics($tournament, $level, $levelName, $groupId, $sfMatch, $finalMatch, $fairChanceMatch);
    }

    /**
     * Determine positions by performance metrics (CASE 2B)
     */
    private function determine3PlayerPositionsByMetrics(Tournament $tournament, string $level, ?string $levelName, $groupId, $sfMatch, $finalMatch, $fairChanceMatch)
    {
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;

        $players = [$sfWinner, $sfLoser, $byePlayer];
        $playerMetrics = [];

        \Log::info("CASE 2B: Calculating performance metrics for 3 players", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser, 
            'bye_player' => $byePlayer
        ]);

        // Calculate metrics for each player
        foreach ($players as $playerId) {
            $playerMetrics[$playerId] = $this->calculatePlayerMetrics($playerId, $tournament);
        }

        // Sort players by performance metrics
        uasort($playerMetrics, function($a, $b) {
            // Primary: Total points (higher is better)
            if ($a['total_points'] != $b['total_points']) {
                return $b['total_points'] - $a['total_points'];
            }
            
            // Secondary: Win rate (higher is better)
            if ($a['win_rate'] != $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate'];
            }
            
            // Tertiary: Total wins (higher is better)
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] - $a['wins'];
            }
            
            // Quaternary: Tournament wins (higher is better)
            return $b['tournament_wins'] - $a['tournament_wins'];
        });

        $sortedPlayerIds = array_keys($playerMetrics);
        
        \Log::info("CASE 2B: Players sorted by performance metrics", [
            'position_1' => $sortedPlayerIds[0],
            'position_2' => $sortedPlayerIds[1],
            'position_3' => $sortedPlayerIds[2],
            'metrics' => $playerMetrics
        ]);

        $this->create3PlayerPositions($tournament, $level, $levelName, $groupId, $sortedPlayerIds[0], $sortedPlayerIds[1], $sortedPlayerIds[2]);
    }

    /**
     * Calculate performance metrics for a player
     */
    private function calculatePlayerMetrics($playerId, Tournament $tournament)
    {
        // Get all matches for this player across all tournaments
        $allMatches = PoolMatch::where(function($query) use ($playerId) {
            $query->where('player_1_id', $playerId)
                  ->orWhere('player_2_id', $playerId);
        })->where('status', 'completed')->get();

        $totalPoints = 0;
        $wins = 0;
        $totalMatches = $allMatches->count();

        foreach ($allMatches as $match) {
            if ($match->player_1_id == $playerId) {
                $totalPoints += $match->player_1_points ?? 0;
                if ($match->winner_id == $playerId) $wins++;
            } else {
                $totalPoints += $match->player_2_points ?? 0;
                if ($match->winner_id == $playerId) $wins++;
            }
        }

        // Get tournament wins
        $tournamentWins = Winner::where('player_id', $playerId)->count();

        $winRate = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;

        return [
            'total_points' => $totalPoints,
            'wins' => $wins,
            'total_matches' => $totalMatches,
            'win_rate' => $winRate,
            'tournament_wins' => $tournamentWins
        ];
    }

    /**
     * Send match notifications to players
     */
    private function sendMatchNotifications(Tournament $tournament, $player1Id, $player2Id, string $roundName, string $matchType)
    {
        $players = [$player1Id, $player2Id];
        
        foreach ($players as $playerId) {
            try {
                $player = User::find($playerId);
                if (!$player) continue;
                
                $message = "New {$matchType} match created in {$tournament->name}";
                
                \App\Models\Notification::create([
                    'player_id' => $playerId,
                    'type' => 'match_created',
                    'message' => $message,
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'match_type' => $matchType,
                        'round_name' => $roundName,
                        'message' => $message
                    ]
                ]);
                
                \Log::info("Sent match notification", [
                    'player_id' => $playerId,
                    'player_name' => $player->name,
                    'match_type' => $matchType,
                    'tournament_id' => $tournament->id
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Failed to send match notification", [
                    'player_id' => $playerId,
                    'match_type' => $matchType,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle losers 3_SF completion - create losers final match
     */
    private function handleLosers3PlayerSFComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling losers 3_SF completion - creating losers final match", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get the losers SF match
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$sfMatch->winner_id) {
            \Log::error("Losers 3_SF match not found or no winner");
            return;
        }

        $sfWinner = User::find($sfMatch->winner_id);
        $byePlayer = User::find($sfMatch->bye_player_id);

        if (!$sfWinner || !$byePlayer) {
            \Log::error("Could not find losers SF winner or bye player");
            return;
        }

        // Get SF loser for the final match
        $sfLoser = ($sfMatch->player_1_id === $sfMatch->winner_id) 
            ? User::find($sfMatch->player_2_id) 
            : User::find($sfMatch->player_1_id);

        if (!$sfLoser) {
            \Log::error("Could not find losers SF loser for final match");
            return;
        }

        // Check if losers final already exists
        $existingFinal = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_final')
            ->exists();

        if ($existingFinal) {
            \Log::info("Losers 3_final match already exists");
            return;
        }

        // Create losers final match: SF loser vs bye player
        $this->create3PlayerMatch($tournament, $level, $groupId, $sfLoser->id, $byePlayer->id, 'losers_3_final', 'losers_3_final_match');

        // Send notifications to players about the new losers final match
        $this->sendMatchNotifications($tournament, $sfLoser->id, $byePlayer->id, 'losers_3_final', 'Losers Final');

        \Log::info("Created losers 3_final match", [
            'sf_loser' => $sfLoser->name,
            'bye_player' => $byePlayer->name,
            'sf_winner' => $sfWinner->name,
            'tournament_id' => $tournament->id
        ]);
    }

    /**
     * Handle losers 3_final completion - determine next step based on winner
     */
    private function handleLosers3PlayerFinalComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling losers 3_final completion - determining next step", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get both losers SF and final matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();

        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_final')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$finalMatch || !$finalMatch->winner_id) {
            \Log::error("Required losers matches not found or no final winner");
            return;
        }

        $finalWinner = $finalMatch->winner_id;
        $finalLoser = ($finalMatch->player_1_id === $finalWinner) 
            ? $finalMatch->player_2_id 
            : $finalMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        $sfWinner = $sfMatch->winner_id;

        // CASE 1: Bye player won the losers final
        if ($finalWinner === $byePlayer) {
            \Log::info("LOSERS CASE 1: Bye player won losers final - creating tie-breaker with SF winner");
            $this->create3PlayerMatch($tournament, $level, $groupId, $byePlayer, $sfWinner, 'losers_3_tie_breaker', 'losers_3_tie_breaker_match');
            
            // Send notifications to players about the new losers tie-breaker match
            $this->sendMatchNotifications($tournament, $byePlayer, $sfWinner, 'losers_3_tie_breaker', 'Losers Tie-Breaker');
            return;
        }

        // CASE 2: SF loser won the losers final (bye player lost)
        \Log::info("LOSERS CASE 2: SF loser won losers final - creating fair chance match");
        $this->create3PlayerMatch($tournament, $level, $groupId, $byePlayer, $sfWinner, 'losers_3_fair_chance', 'losers_3_fair_chance_match');
        
        // Send notifications to players about the new losers fair chance match
        $this->sendMatchNotifications($tournament, $byePlayer, $sfWinner, 'losers_3_fair_chance', 'Losers Fair Chance');
    }

    /**
     * Handle losers 3_tie_breaker completion - CASE 1 final positions (4, 5, 6)
     */
    private function handleLosers3PlayerTieBreakerComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling losers 3_tie_breaker completion - CASE 1", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get all required losers matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();

        $tieBreakerMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_tie_breaker')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$tieBreakerMatch || !$tieBreakerMatch->winner_id) {
            \Log::error("Required losers matches not found for tie-breaker completion");
            return;
        }

        // LOSERS CASE 1 positions: Tie-breaker winner = pos 4, loser = pos 5, SF loser = pos 6
        $tieBreakerWinner = $tieBreakerMatch->winner_id; // Position 4
        $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) 
            ? $tieBreakerMatch->player_2_id 
            : $tieBreakerMatch->player_1_id; // Position 5
        
        // SF loser is position 6 (the one who lost in losers final)
        $sfLoser = ($sfMatch->player_1_id === $sfMatch->winner_id) 
            ? $sfMatch->player_2_id 
            : $sfMatch->player_1_id;

        \Log::info("LOSERS CASE 1 - Tie-breaker positions determined", [
            'position_4' => $tieBreakerWinner,
            'position_5' => $tieBreakerLoser,
            'position_6' => $sfLoser
        ]);

        $this->createWinnerPositions($tournament, $level, $groupId, [
            4 => $tieBreakerWinner,
            5 => $tieBreakerLoser,
            6 => $sfLoser
        ]);
    }

    /**
     * Handle losers 3_fair_chance completion - CASE 2 positions (4, 5, 6)
     */
    private function handleLosers3PlayerFairChanceComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling losers 3_fair_chance completion - CASE 2", [
            'tournament_id' => $tournament->id,
            'level' => $level
        ]);

        // Get all required losers matches
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();

        $fairChanceMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_fair_chance')
            ->where('status', 'completed')
            ->first();

        if (!$sfMatch || !$fairChanceMatch || !$fairChanceMatch->winner_id) {
            \Log::error("Required losers matches not found for fair chance completion");
            return;
        }

        $fairChanceWinner = $fairChanceMatch->winner_id;
        $sfWinner = $sfMatch->winner_id;
        $byePlayer = $sfMatch->bye_player_id;

        // LOSERS CASE 2A: SF winner wins fair chance
        if ($fairChanceWinner === $sfWinner) {
            \Log::info("LOSERS CASE 2A: SF winner won fair chance");
            
            $sfLoser = ($sfMatch->player_1_id === $sfWinner) 
                ? $sfMatch->player_2_id 
                : $sfMatch->player_1_id;

            // Positions: SF winner = pos 4, SF loser = pos 5, bye player = pos 6
            $this->createWinnerPositions($tournament, $level, $groupId, [
                4 => $sfWinner,
                5 => $sfLoser,
                6 => $byePlayer
            ]);
            return;
        }

        // LOSERS CASE 2B: Bye player wins fair chance - determine by performance metrics
        \Log::info("LOSERS CASE 2B: Bye player won fair chance - determining by performance metrics");
        $this->determineLosers3PlayerPositionsByMetrics($tournament, $level, $levelName, $groupId, $sfMatch, $fairChanceMatch);
    }

    /**
     * Determine losers positions by performance metrics (LOSERS CASE 2B)
     */
    private function determineLosers3PlayerPositionsByMetrics(Tournament $tournament, string $level, ?string $levelName, $groupId, $sfMatch, $fairChanceMatch)
    {
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;

        $players = [$sfWinner, $sfLoser, $byePlayer];
        $playerMetrics = [];

        \Log::info("LOSERS CASE 2B: Calculating performance metrics for 3 losers", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser, 
            'bye_player' => $byePlayer
        ]);

        // Calculate metrics for each player
        foreach ($players as $playerId) {
            $playerMetrics[$playerId] = $this->calculatePlayerMetrics($playerId, $tournament);
        }

        // Sort players by performance metrics
        uasort($playerMetrics, function($a, $b) {
            // Primary: Total points (higher is better)
            if ($a['total_points'] != $b['total_points']) {
                return $b['total_points'] - $a['total_points'];
            }
            
            // Secondary: Win rate (higher is better)
            if ($a['win_rate'] != $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate'];
            }
            
            // Tertiary: Total wins (higher is better)
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] - $a['wins'];
            }
            
            // Quaternary: Tournament wins (higher is better)
            return $b['tournament_wins'] - $a['tournament_wins'];
        });

        $sortedPlayerIds = array_keys($playerMetrics);
        
        \Log::info("LOSERS CASE 2B: Players sorted by performance metrics", [
            'position_4' => $sortedPlayerIds[0],
            'position_5' => $sortedPlayerIds[1],
            'position_6' => $sortedPlayerIds[2],
            'metrics' => $playerMetrics
        ]);

        $this->createWinnerPositions($tournament, $level, $groupId, [
            4 => $sortedPlayerIds[0],
            5 => $sortedPlayerIds[1],
            6 => $sortedPlayerIds[2]
        ]);
    }

}
