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
        
        // If we need more than 3 winners, handle losers tournament (D, E, F)
        if ($winnersNeeded > 3) {
            \Log::info("Handling losers tournament for positions 4-6", [
                'winners_needed' => $winnersNeeded
            ]);
            $losersPositions = $this->handle3PlayerLosersTournament($tournament, $level, $groupId, $winnersNeeded);
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
     * Create 3-player final match
     */
    public function create3PlayerFinalMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        \Log::info("Creating 3-player final match", [
            'player_1' => $player1Id,
            'player_2' => $player2Id
        ]);
        
        PoolMatch::create([
            'match_name' => '3_winners_final_match',
            'player_1_id' => $player1Id,
            'player_2_id' => $player2Id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => '3_winners_final',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Create 3-player tie breaker match
     */
    public function create3PlayerTieBreakerMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        \Log::info("Creating 3-player tie breaker match", [
            'player_1' => $player1Id,
            'player_2' => $player2Id
        ]);
        
        PoolMatch::create([
            'match_name' => '3_winners_tie_breaker_match',
            'player_1_id' => $player1Id,
            'player_2_id' => $player2Id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => '3_winners_tie_breaker',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Create 3-player fair chance match
     */
    public function create3PlayerFairChanceMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        \Log::info("Creating 3-player fair chance match", [
            'player_1' => $player1Id,
            'player_2' => $player2Id
        ]);
        
        PoolMatch::create([
            'match_name' => '3_winners_fair_chance_match',
            'player_1_id' => $player1Id,
            'player_2_id' => $player2Id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => '3_winners_fair_chance',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
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
        PoolMatch::create([
            'match_name' => '3_SF_match',
            'player_1_id' => $pairedPlayers[0]->id,
            'player_2_id' => $pairedPlayers[1]->id,
            'bye_player_id' => $pairedPlayers[2]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '3_SF',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
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
        
        // Send notifications
        $this->sendPositionNotifications($tournament, $level, TournamentUtilityService::getLevelName($level, $groupId), $positions);
    }

    /**
     * Send position notifications to players
     */
    private function sendPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $positions)
    {
        foreach ($positions as $position => $playerId) {
            try {
                $player = User::find($playerId);
                if (!$player) {
                    \Log::warning("Player not found for position notification", [
                        'player_id' => $playerId
                    ]);
                    continue;
                }
                
                $message = "Congratulations! You finished in position {$position} in {$tournament->name}";
                
                \App\Models\Notification::create([
                    'player_id' => $playerId,
                    'type' => 'tournament_position',
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'level' => $level,
                        'position' => $position,
                        'message' => $message
                    ]
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Failed to send position notification", [
                    'player_id' => $playerId,
                    'error' => $e->getMessage()
                ]);
            }
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
        
        // Tie breaker completed - assign positions 4, 5, 6
        $tieBreakerWinner = $tieBreakerMatch->winner_id;
        $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) ? $tieBreakerMatch->player_2_id : $tieBreakerMatch->player_1_id;
        
        // Position 4: Tie breaker winner
        // Position 5: Tie breaker loser  
        // Position 6: SF loser (E)
        $this->createWinnerPositions($tournament, $level, $groupId, [
            4 => $tieBreakerWinner,
            5 => $tieBreakerLoser,
            6 => $sfLoser
        ]);
        
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
            // Standard positions:
            // Position 4: SF winner (D) - won fair chance
            // Position 5: SF loser (E) - won final
            // Position 6: Bye player (F) - lost both final and fair chance
            $this->createWinnerPositions($tournament, $level, $groupId, [
                4 => $sfWinner,
                5 => $sfLoser,
                6 => $byePlayer->id
            ]);
            
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
        
        // Assign positions 4, 5, 6 based on metrics
        $this->createWinnerPositions($tournament, $level, $groupId, [
            4 => $sortedPlayers[0],
            5 => $sortedPlayers[1],
            6 => $sortedPlayers[2]
        ]);
        
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
     * Create 3-player losers final match
     */
    public function create3PlayerLosersFinalMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        \Log::info("Creating 3-player losers final match", [
            'player_1' => $player1Id,
            'player_2' => $player2Id
        ]);
        
        PoolMatch::create([
            'match_name' => 'losers_3_final_match',
            'player_1_id' => $player1Id,
            'player_2_id' => $player2Id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => 'losers_3_final',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Create 3-player losers tie breaker match
     */
    public function create3PlayerLosersTieBreakerMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        \Log::info("Creating 3-player losers tie breaker match", [
            'player_1' => $player1Id,
            'player_2' => $player2Id
        ]);
        
        PoolMatch::create([
            'match_name' => 'losers_3_tie_breaker_match',
            'player_1_id' => $player1Id,
            'player_2_id' => $player2Id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => 'losers_3_tie_breaker',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Create 3-player losers fair chance match
     */
    public function create3PlayerLosersFairChanceMatch(Tournament $tournament, string $level, ?int $groupId, $player1Id, $player2Id)
    {
        \Log::info("Creating 3-player losers fair chance match", [
            'player_1' => $player1Id,
            'player_2' => $player2Id
        ]);
        
        PoolMatch::create([
            'match_name' => 'losers_3_fair_chance_match',
            'player_1_id' => $player1Id,
            'player_2_id' => $player2Id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => 'losers_3_fair_chance',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Create 3-player losers tournament (mirrors winners tournament)
     */
    public function createLosers3PlayerTournament(Tournament $tournament, string $level, ?int $groupId, $losers, int $winnersNeeded)
    {
        \Log::info("Creating 3-player losers tournament", [
            'tournament_id' => $tournament->id,
            'losers' => $losers->pluck('id')->toArray(),
            'winners_needed' => $winnersNeeded
        ]);
        
        // Create losers semifinal (2 players, 1 bye)
        $shuffledLosers = $losers->shuffle();
        
        PoolMatch::create([
            'match_name' => 'losers_3_SF_match',
            'player_1_id' => $shuffledLosers[0]->id,
            'player_2_id' => $shuffledLosers[1]->id,
            'bye_player_id' => $shuffledLosers[2]->id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => 'losers_3_SF',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        \Log::info("Created losers semifinal match with bye player", [
            'player_1' => $shuffledLosers[0]->name,
            'player_2' => $shuffledLosers[1]->name,
            'bye_player' => $shuffledLosers[2]->name
        ]);
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
        
        // Position 1: SF winner
        Winner::create([
            'player_id' => $sfWinner,
            'position' => 1,
            'level' => $level,
            'level_id' => $groupId,
            'tournament_id' => $tournament->id,
        ]);
        
        // Position 2: SF loser
        Winner::create([
            'player_id' => $sfLoser,
            'position' => 2,
            'level' => $level,
            'level_id' => $groupId,
            'tournament_id' => $tournament->id,
        ]);
        
        // Position 3: Bye player
        if ($byePlayer) {
            Winner::create([
                'player_id' => $byePlayer,
                'position' => 3,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
        }
        
        \Log::info("3-player standard winners determined", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser,
            'bye_player' => $byePlayer,
            'tournament_id' => $tournament->id,
        ]);
        
        // Send notifications
        $positions = [
            1 => $sfWinner,
            2 => $sfLoser,
            3 => $byePlayer
        ];
        $this->sendPositionNotifications($tournament, $level, TournamentUtilityService::getLevelName($level, $groupId), $positions);
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
        
        // Create 3_winners_SF: A vs B (C gets bye) - using robust naming convention
        PoolMatch::create([
            'match_name' => '3_winners_SF_match',
            'player_1_id' => $winners[0],
            'player_2_id' => $winners[1],
            'bye_player_id' => $winners[2],
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '3_winners_SF',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        \Log::info("3-player winners tournament matches created", [
            'sf_match' => '3_winners_SF',
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
        PoolMatch::create([
            'match_name' => 'losers_3_SF_match',
            'player_1_id' => $losersArray[0],
            'player_2_id' => $losersArray[1],
            'bye_player_id' => $losersArray[2],
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => 'losers_3_SF',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
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
        
        switch ($completedRound) {
            case '3_winners_SF':
                $this->handle3PlayerWinnersSFComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_winners_final':
                $this->handle3PlayerWinnersFinalComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case '3_winners_tie_breaker':
            case '3_winners_fair_chance':
                $this->handle3PlayerWinnersComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case 'losers_3_SF':
                $this->handle3PlayerLosersSFComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case 'losers_3_final':
                $this->handle3PlayerLosersFinalComplete($tournament, $level, $levelName, $groupId);
                break;
                
            case 'losers_3_tie_breaker':
            case 'losers_3_fair_chance':
                $this->handle3PlayerLosersComplete($tournament, $level, $levelName, $groupId);
                break;
                
            default:
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
        
        // Use the robust losers tournament logic
        $this->handle3PlayerLosersTournament($tournament, $level, $groupId, $tournament->winners ?? 6);
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

}
