<?php

namespace App\Services;

use App\Models\PoolMatch;
use App\Models\Tournament;
use App\Models\Winner;
use App\Models\User;
use App\Services\TournamentUtilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FourPlayerTournamentService
{
    /**
     * Create 4-player tournament structure from 4 winners
     */
    public function create4PlayerTournamentFromWinners(Tournament $tournament, Collection $winners, string $level, $groupId, string $levelName)
    {
        $winnersArray = $winners->shuffle()->values();
        
        \Log::info("=== CREATE 4-PLAYER TOURNAMENT FROM WINNERS ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'group_id' => $groupId,
            'round_name_to_use' => '4player_round1',
            'player_1' => $winnersArray[0]->name,
            'player_2' => $winnersArray[1]->name,
            'player_3' => $winnersArray[2]->name,
            'player_4' => $winnersArray[3]->name,
            'logic' => 'Creating new 4-player tournament from larger tournament winners'
        ]);
        
        // Create Round 1 Match 1: A vs B
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $winnersArray[0],
            $winnersArray[1],
            '4player_round1',
            $level,
            $groupId,
            $levelName,
            null,
            '4player_round1_match1'
        );
        
        // Create Round 1 Match 2: C vs D
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $winnersArray[2],
            $winnersArray[3],
            '4player_round1',
            $level,
            $groupId,
            $levelName,
            null,
            '4player_round1_match2'
        );
        
        \Log::info("Created 4-player tournament matches from winners", [
            'match_1' => $winnersArray[0]->name . ' vs ' . $winnersArray[1]->name,
            'match_2' => $winnersArray[2]->name . ' vs ' . $winnersArray[3]->name,
            'round_name' => '4player_round1'
        ]);
    }

    /**
     * Determine winners for 4-player tournament
     */
    public function determine4PlayerWinners(Tournament $tournament, string $level, ?int $groupId)
    {
        // Get all matches for this group
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->get();
            
        // For 4 players with round_1 matches, check if additional matches are needed
        if ($matches->count() == 2 && $matches->every(fn($m) => $m->round_name == 'round_1')) {
            // Two initial matches completed - need to create winners final and losers semifinal
            $match1 = $matches->first();
            $match2 = $matches->last();
            
            $winner1 = $match1->winner_id;
            $winner2 = $match2->winner_id;
            $loser1 = $match1->winner_id == $match1->player_1_id ? $match1->player_2_id : $match1->player_1_id;
            $loser2 = $match2->winner_id == $match2->player_1_id ? $match2->player_2_id : $match2->player_1_id;
            
            // Check if winners final exists
            $winnersFinal = $matches->where('round_name', 'winners_final')->first();
            $losersSemifinal = $matches->where('round_name', 'losers_semifinal')->first();
            
            if (!$winnersFinal) {
                // Create winners final match
                \Log::info("Creating 4-player winners final match", [
                    'winner1' => $winner1,
                    'winner2' => $winner2,
                    'tournament_id' => $tournament->id
                ]);
                
                \App\Services\MatchCreationService::createMatch(
                    $tournament,
                    User::find($winner1),
                    User::find($winner2),
                    'winners_final',
                    $level,
                    $groupId,
                    $this->getLevelName($level, $groupId),
                    null,
                    'winners_final_match'
                );
            }
            
            if (!$losersSemifinal) {
                // Create losers semifinal match
                \Log::info("Creating 4-player losers semifinal match", [
                    'loser1' => $loser1,
                    'loser2' => $loser2,
                    'tournament_id' => $tournament->id
                ]);
                
                \App\Services\MatchCreationService::createMatch(
                    $tournament,
                    User::find($loser1),
                    User::find($loser2),
                    'losers_semifinal',
                    $level,
                    $groupId,
                    $this->getLevelName($level, $groupId),
                    null,
                    'losers_semifinal_match'
                );
            }
            
            return; // Wait for additional matches to complete
        }
        
        // Check for completed simplified 4-player tournament (3 matches total)
        $round1Matches = $matches->where('round_name', 'round_1');
        $winnersFinal = $matches->where('round_name', 'winners_final')->first();
        $losersSemifinal = $matches->where('round_name', 'losers_semifinal')->first();
        
        if ($round1Matches->count() == 2 && $winnersFinal && $losersSemifinal) {
            // All 3 matches completed - determine final positions
            \Log::info("Determining 4-player simplified tournament positions", [
                'tournament_id' => $tournament->id,
                'winners_final_winner' => $winnersFinal->winner_id,
                'losers_semifinal_winner' => $losersSemifinal->winner_id
            ]);
            
            // Position 1: Winners final winner
            Winner::create([
                'player_id' => $winnersFinal->winner_id,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 2: Winners final loser
            $winnersLoser = ($winnersFinal->player_1_id === $winnersFinal->winner_id) ? $winnersFinal->player_2_id : $winnersFinal->player_1_id;
            Winner::create([
                'player_id' => $winnersLoser,
                'position' => 2,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 3: Losers semifinal winner
            Winner::create([
                'player_id' => $losersSemifinal->winner_id,
                'position' => 3,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 4: Losers semifinal loser
            $losersLoser = ($losersSemifinal->player_1_id === $losersSemifinal->winner_id) ? $losersSemifinal->player_2_id : $losersSemifinal->player_1_id;
            Winner::create([
                'player_id' => $losersLoser,
                'position' => 4,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            \Log::info("4-player tournament positions determined", [
                'position_1' => $winnersFinal->winner_id,
                'position_2' => $winnersLoser,
                'position_3' => $losersSemifinal->winner_id,
                'position_4' => $losersLoser
            ]);
        }
    }

    /**
     * Determine winners for 4-player standard tournaments
     */
    public function determineStandard4PlayerWinners(Tournament $tournament, string $level, ?int $groupId, bool $isTargetLevel)
    {
        // Simple 4-player logic: A vs B, C vs D → Winners final, Losers semifinal
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->get();
        
        $winnersMatch = $matches->where('round_name', 'winners_final')->first();
        $losersMatch = $matches->where('round_name', 'losers_semifinal')->first();
        
        if ($winnersMatch && $losersMatch) {
            // Position 1: Winners final winner
            Winner::create([
                'player_id' => $winnersMatch->winner_id,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 2: Winners final loser
            $winnersLoser = ($winnersMatch->player_1_id === $winnersMatch->winner_id) ? $winnersMatch->player_2_id : $winnersMatch->player_1_id;
            Winner::create([
                'player_id' => $winnersLoser,
                'position' => 2,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 3: Losers semifinal winner
            Winner::create([
                'player_id' => $losersMatch->winner_id,
                'position' => 3,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 4: Losers semifinal loser
            $losersLoser = ($losersMatch->player_1_id === $losersMatch->winner_id) ? $losersMatch->player_2_id : $losersMatch->player_1_id;
            Winner::create([
                'player_id' => $losersLoser,
                'position' => 4,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            \Log::info("4-player standard winners determined", [
                'winners_final_winner' => $winnersMatch->winner_id,
                'winners_final_loser' => $winnersLoser,
                'losers_semifinal_winner' => $losersMatch->winner_id,
                'losers_semifinal_loser' => $losersLoser,
                'tournament_id' => $tournament->id,
            ]);
            
            // Send notifications to winners
            $this->sendPositionNotifications($tournament, $level, $this->getLevelName($level, $groupId), [
                ['player_id' => $winnersMatch->winner_id, 'position' => 1],
                ['player_id' => $winnersLoser, 'position' => 2],
                ['player_id' => $losersMatch->winner_id, 'position' => 3],
                ['player_id' => $losersLoser, 'position' => 4]
            ]);
        }
    }

    /**
     * Create 4-player losers tournament (simplified bracket)
     */
    public function createLosers4PlayerTournament(Tournament $tournament, string $level, ?int $groupId, $losers, int $winnersNeeded)
    {
        // Only create if we need 5 or 6 winners (positions 5-6)
        if ($winnersNeeded < 5) {
            return;
        }
        
        \Log::info("Creating 4-player losers tournament", [
            'tournament_id' => $tournament->id,
            'losers' => $losers->pluck('id')->toArray(),
            'winners_needed' => $winnersNeeded
        ]);
        
        $shuffledLosers = $losers->shuffle()->values();
        
        // Create losers Round 1 Match 1: D vs E
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledLosers[0],
            $shuffledLosers[1],
            'losers_round1',
            $level,
            $groupId,
            $this->getLevelName($level, $groupId),
            null,
            'losers_round1_match1'
        );
        
        // Create losers Round 1 Match 2: F vs G
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledLosers[2],
            $shuffledLosers[3],
            'losers_round1',
            $level,
            $groupId,
            $this->getLevelName($level, $groupId),
            null,
            'losers_round1_match2'
        );
        
        \Log::info("Created 4-player losers initial matches", [
            'match_1' => $shuffledLosers[0]->name . ' vs ' . $shuffledLosers[1]->name,
            'match_2' => $shuffledLosers[2]->name . ' vs ' . $shuffledLosers[3]->name
        ]);
    }

    /**
     * Determine positions 5-6 from 4-player losers tournament
     */
    public function determineLosers4PlayerPositions(Tournament $tournament, string $level, ?int $groupId, $losersRound1, $losersWinnersFinal, int $winnersNeeded)
    {
        if ($losersRound1->count() === 2 && !$losersWinnersFinal) {
            // Create winners final for 4-player losers
            $match1 = $losersRound1->first();
            $match2 = $losersRound1->last();
            
            $winner1 = $match1->winner_id;
            $winner2 = $match2->winner_id;
            
            \Log::info("Creating 4-player losers winners final", [
                'winner1' => $winner1,
                'winner2' => $winner2,
                'tournament_id' => $tournament->id
            ]);
            
            \App\Services\MatchCreationService::createMatch(
                $tournament,
                User::find($winner1),
                User::find($winner2),
                'losers_winners_final',
                $level,
                $groupId,
                $this->getLevelName($level, $groupId),
                null,
                'losers_winners_final_match'
            );
            
        } elseif ($losersRound1->count() === 2 && $losersWinnersFinal) {
            // Determine positions 5-6
            \Log::info("Determining positions 5-6 from 4-player losers tournament", [
                'losers_final_winner' => $losersWinnersFinal->winner_id,
                'tournament_id' => $tournament->id
            ]);
            
            // Position 5: Losers winners final winner
            Winner::create([
                'player_id' => $losersWinnersFinal->winner_id,
                'position' => 5,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 6: Losers winners final loser
            $losersLoser = ($losersWinnersFinal->player_1_id === $losersWinnersFinal->winner_id) ? 
                $losersWinnersFinal->player_2_id : $losersWinnersFinal->player_1_id;
            Winner::create([
                'player_id' => $losersLoser,
                'position' => 6,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            \Log::info("4-player losers positions determined", [
                'position_5' => $losersWinnersFinal->winner_id,
                'position_6' => $losersLoser
            ]);
        }
    }

    /**
     * Generate 4-player tournament matches
     */
    public function generate4PlayerMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $levelName, ?string $roundName = null)
    {
        $r1RoundName = $roundName ?? 'round_1';
        $shuffledPlayers = $players->shuffle();
        
        \Log::info("Creating 4-player matches", [
            'round_name' => $r1RoundName,
            'level_name' => $levelName,
            'players' => $shuffledPlayers->map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->toArray()
        ]);
        
        // Create Match 1: A vs B
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledPlayers[0],
            $shuffledPlayers[1],
            $r1RoundName,
            $level,
            $groupId,
            $levelName,
            null,
            $r1RoundName . '_match1'
        );
        
        // Create Match 2: C vs D
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledPlayers[2],
            $shuffledPlayers[3],
            $r1RoundName,
            $level,
            $groupId,
            $levelName,
            null,
            $r1RoundName . '_match2'
        );
        
        \Log::info("Created 4-player tournament matches", [
            'match_1' => $shuffledPlayers[0]->name . ' vs ' . $shuffledPlayers[1]->name,
            'match_2' => $shuffledPlayers[2]->name . ' vs ' . $shuffledPlayers[3]->name,
            'round_name' => $r1RoundName
        ]);
    }

    /**
     * Smart pairing for 4 players
     */
    public function pair4Players(Collection $players, string $level): array
    {
        // 4-player tournament: Use unique round name to avoid conflicts
        $matches[] = [
            'player1' => $players[0],
            'player2' => $players[1],
            'round_name' => 'round_1'
        ];
        $matches[] = [
            'player1' => $players[2],
            'player2' => $players[3],
            'round_name' => 'round_1'
        ];
        
        return $matches;
    }

    /**
     * Send position notifications to players
     */
    private function sendPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $positions)
    {
        foreach ($positions as $positionData) {
            try {
                $player = User::find($positionData['player_id']);
                if (!$player) {
                    \Log::warning("Player not found for position notification", [
                        'player_id' => $positionData['player_id']
                    ]);
                    continue;
                }
                
                $position = $positionData['position'];
                $message = "Congratulations! You finished in position {$position} in {$tournament->name}";
                
                \App\Models\Notification::create([
                    'player_id' => $positionData['player_id'],
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
                    'player_id' => $positionData['player_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Generate comprehensive 4-player tournament with winners and losers brackets
     * Handles 4, 5, and 6 winners scenarios
     */
    public function generateComprehensive4PlayerTournament(Tournament $tournament, string $level, ?string $levelName, $matches, int $winnersNeeded = 4)
    {
        // Get the 4 winners from the completed matches
        $winners = collect();
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $winner = User::find($match->winner_id);
                if ($winner) {
                    $winners->push($winner);
                }
            }
        }
        
        // Get the 4 losers from the completed matches
        $losers = collect();
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $loserId = ($match->player_1_id === $match->winner_id) ? $match->player_2_id : $match->player_1_id;
                $loser = User::find($loserId);
                if ($loser) {
                    $losers->push($loser);
                }
            }
        }
        
        $shuffledWinners = $winners->shuffle()->values();
        $shuffledLosers = $losers->shuffle()->values();
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        \Log::info("=== CREATING COMPREHENSIVE 4-PLAYER TOURNAMENT ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_needed' => $winnersNeeded,
            'winners_count' => $shuffledWinners->count(),
            'losers_count' => $shuffledLosers->count(),
            'winners' => $shuffledWinners->pluck('name')->toArray(),
            'losers' => $shuffledLosers->pluck('name')->toArray()
        ]);
        
        // Create winners bracket matches (for positions 1-4)
        $this->createWinnersBracket($tournament, $level, $levelName, $groupId, $shuffledWinners);
        
        // Create losers bracket matches only if we need 5 or 6 winners
        if ($winnersNeeded > 4) {
            $this->createLosersBracket($tournament, $level, $levelName, $groupId, $shuffledLosers, $winnersNeeded);
        }
    }

    /**
     * Create winners bracket for 4-player tournament
     */
    private function createWinnersBracket(Tournament $tournament, string $level, ?string $levelName, $groupId, $shuffledWinners)
    {
        // Create 4player_round1_match1: Winner A vs Winner B
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledWinners[0],
            $shuffledWinners[1],
            '4player_round1',
            $level,
            $groupId,
            $levelName,
            null,
            '4player_round1_match1'
        );
        
        // Create 4player_round1_match2: Winner C vs Winner D
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledWinners[2],
            $shuffledWinners[3],
            '4player_round1',
            $level,
            $groupId,
            $levelName,
            null,
            '4player_round1_match2'
        );
    }

    /**
     * Create losers bracket for 4-player tournament (positions 5-6)
     */
    private function createLosersBracket(Tournament $tournament, string $level, ?string $levelName, $groupId, $shuffledLosers, int $winnersNeeded)
    {
        if ($winnersNeeded >= 5) {
            // Create 4player_losers_round1_match1: Loser A vs Loser B
            \App\Services\MatchCreationService::createMatch(
                $tournament,
                $shuffledLosers[0],
                $shuffledLosers[1],
                '4player_losers_round1',
                $level,
                $groupId,
                $levelName,
                null,
                '4player_losers_round1_match1'
            );
        }
        
        if ($winnersNeeded >= 6) {
            // Create 4player_losers_round1_match2: Loser C vs Loser D
            \App\Services\MatchCreationService::createMatch(
                $tournament,
                $shuffledLosers[2],
                $shuffledLosers[3],
                '4player_losers_round1',
                $level,
                $groupId,
                $levelName,
                null,
                '4player_losers_round1_match2'
            );
        }
    }

    /**
     * Generate 4-player round 1 matches from winners of larger tournament
     */
    public function generate4PlayerRound1(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        // Get the 4 winners from the completed matches
        $winners = collect();
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $winner = User::find($match->winner_id);
                if ($winner) {
                    $winners->push($winner);
                }
            }
        }
        
        if ($winners->count() < 4) {
            \Log::warning("Not enough winners for 4-player round 1", [
                'winner_count' => $winners->count()
            ]);
            return;
        }
        
        $shuffledWinners = $winners->shuffle()->values();
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        \Log::info("=== GENERATING 4-PLAYER ROUND 1 ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners' => $shuffledWinners->pluck('name')->toArray()
        ]);
        
        // Create Round 1 Match 1: A vs B
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledWinners[0],
            $shuffledWinners[1],
            '4player_round1',
            $level,
            $groupId,
            $levelName,
            null,
            '4player_round1_match1'
        );
        
        // Create Round 1 Match 2: C vs D
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            $shuffledWinners[2],
            $shuffledWinners[3],
            '4player_round1',
            $level,
            $groupId,
            $levelName,
            null,
            '4player_round1_match2'
        );
    }

    /**
     * Check if should create comprehensive 4-player tournament
     */
    public function shouldCreateComprehensive4PlayerTournament($matches, $winnerCount, $tournament): bool
    {
        $winnersNeeded = $tournament->winners ?? 4;
        
        // Use comprehensive tournament if we need 5 or 6 winners
        if ($winnersNeeded >= 5 && $winnersNeeded <= 6) {
            \Log::info("Should create comprehensive 4-player tournament", [
                'winners_needed' => $winnersNeeded,
                'winner_count' => $winnerCount
            ]);
            return true;
        }
        
        return false;
    }

    /**
     * Generate 4 positions directly from standard semifinals (no final needed)
     */
    public function generateStandard4PlayerPositions(Tournament $tournament, string $level, ?string $levelName, $winnersFinal, $losersSemifinal)
    {
        \Log::info("Generating 4 positions directly from standard semifinals", [
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
        
        // Position 4: Loser of losers_semifinal
        $losersLoser = ($losersSemifinal->player_1_id === $losersSemifinal->winner_id) ? $losersSemifinal->player_2_id : $losersSemifinal->player_1_id;
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersLoser,
            'position' => 4,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Send notifications
        $this->sendPositionNotifications($tournament, $level, $levelName, [
            1 => $winnersFinal->winner_id,
            2 => $winnersLoser,
            3 => $losersSemifinal->winner_id,
            4 => $losersLoser
        ]);
        
        \Log::info("Standard 4-player positions created successfully");
    }

    /**
     * Generate 4-player semifinal matches
     */
    public function generate4PlayerSemifinals(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        $sortedMatches = $matches->sortBy('match_name');
        $match1 = $sortedMatches->first();
        $match2 = $sortedMatches->last();
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        \Log::info("=== GENERATING 4-PLAYER SEMIFINALS ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'match1_winner' => $match1->winner_id,
            'match2_winner' => $match2->winner_id
        ]);
        
        // Create winners final: Winner of match1 vs Winner of match2
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            User::find($match1->winner_id),
            User::find($match2->winner_id),
            'winners_final',
            $level,
            $groupId,
            $levelName,
            null,
            'winners_final'
        );
        
        // Create losers semifinal: Loser of match1 vs Loser of match2
        $loser1 = ($match1->player_1_id === $match1->winner_id) ? $match1->player_2_id : $match1->player_1_id;
        $loser2 = ($match2->player_1_id === $match2->winner_id) ? $match2->player_2_id : $match2->player_1_id;
        
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            User::find($loser1),
            User::find($loser2),
            'losers_semifinal',
            $level,
            $groupId,
            $levelName,
            null,
            'losers_semifinal'
        );
    }

    /**
     * Generate 4-player final match
     */
    public function generate4PlayerFinal(Tournament $tournament, string $level, ?string $levelName)
    {
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        $winnersSFQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'winners_final')
            ->where('status', 'completed');
            
        if ($levelName) {
            $winnersSFQuery->where('level_name', $levelName);
        } else {
            $winnersSFQuery->whereNull('level_name');
        }
        
        $winnersSF = $winnersSFQuery->first();
        
        if (!$winnersSF) {
            \Log::warning("No completed winners final found for 4-player final generation");
            return;
        }
        
        \Log::info("=== GENERATING 4-PLAYER FINAL ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_sf_winner' => $winnersSF->winner_id
        ]);
        
        // Get the loser of winners final for the final match
        $winnersLoser = ($winnersSF->player_1_id === $winnersSF->winner_id) ? $winnersSF->player_2_id : $winnersSF->player_1_id;
        
        // Get winner of losers semifinal
        $losersSFQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'losers_semifinal')
            ->where('status', 'completed');
            
        if ($levelName) {
            $losersSFQuery->where('level_name', $levelName);
        } else {
            $losersSFQuery->whereNull('level_name');
        }
        
        $losersSF = $losersSFQuery->first();
        
        if (!$losersSF) {
            \Log::warning("No completed losers semifinal found for 4-player final generation");
            return;
        }
        
        // Create final match: Loser of winners final vs Winner of losers semifinal
        \App\Services\MatchCreationService::createMatch(
            $tournament,
            User::find($winnersLoser),
            User::find($losersSF->winner_id),
            '4_final',
            $level,
            $groupId,
            $levelName,
            null,
            '4_final'
        );
    }

    /**
     * Create positions for 4-player tournament
     */
    public function create4PlayerPositions(Tournament $tournament, string $level, ?string $levelName)
    {
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        // Get all required matches
        $winnersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'winners_final')
            ->where('status', 'completed')
            ->first();
            
        $losersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'losers_semifinal')
            ->where('status', 'completed')
            ->first();
            
        $final = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', '4_final')
            ->where('status', 'completed')
            ->first();
        
        if (!$winnersSF || !$losersSF || !$final) {
            \Log::warning("Missing required matches for 4-player position creation");
            return;
        }
        
        // Position 1: Winner of winners final
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $winnersSF->winner_id,
            'position' => 1,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Position 2: Winner of final
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $final->winner_id,
            'position' => 2,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Position 3: Loser of final
        $finalLoser = ($final->player_1_id === $final->winner_id) ? $final->player_2_id : $final->player_1_id;
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $finalLoser,
            'position' => 3,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Position 4: Loser of losers semifinal
        $losersSFLoser = ($losersSF->player_1_id === $losersSF->winner_id) ? $losersSF->player_2_id : $losersSF->player_1_id;
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersSFLoser,
            'position' => 4,
            'level' => $level,
            'level_id' => $groupId,
        ]);
        
        // Send notifications
        $this->sendPositionNotifications($tournament, $level, $levelName, [
            1 => $winnersSF->winner_id,
            2 => $final->winner_id,
            3 => $finalLoser,
            4 => $losersSFLoser
        ]);
        
        \Log::info("4-player positions created successfully");
    }

    /**
     * Check 4-player tournament progression and generate next matches
     */
    public function check4PlayerTournamentProgression(Tournament $tournament, string $level, ?string $levelName, string $completedRound): array
    {
        \Log::info("=== CHECK 4-PLAYER TOURNAMENT PROGRESSION START ===", [
            'completed_round' => $completedRound,
            'level' => $level,
            'tournament_id' => $tournament->id
        ]);
        
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // For special tournaments, use a default groupId of 1 if null
        if ($groupId === null && ($level === 'special' || $tournament->special)) {
            $groupId = 1;
        }
        
        // Get winners from the completed round
        $winners = $this->getWinnersFromCompletedRound($tournament, $level, $groupId, $completedRound);
        
        \Log::info("4-player progression - winner count check", [
            'completed_round' => $completedRound,
            'winner_count' => $winners->count(),
            'tournament_id' => $tournament->id
        ]);
        
        // If exactly 4 winners, create 4-player tournament
        if ($winners->count() === 4) {
            // Check if 4player_round1 already exists
            $existing4PlayerRound1 = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('group_id', $groupId)
                ->where('round_name', '4player_round1')
                ->exists();
                
            if (!$existing4PlayerRound1) {
                \Log::info("Creating 4-player tournament from {$completedRound} with 4 winners");
                $this->generate4PlayerRound1($tournament, $level, $levelName, $winners->toArray());
                return [
                    'status' => 'success',
                    'message' => '4-player tournament created from completed round',
                    'progression_complete' => true
                ];
            }
        }
        
        // Handle specific 4-player rounds
        switch ($completedRound) {
            case '4player_round1':
                \Log::info("4-player round 1 completed - generating semifinals");
                $matches = PoolMatch::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('group_id', $groupId)
                    ->where('round_name', '4player_round1')
                    ->where('status', 'completed')
                    ->get();
                    
                $this->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
                return [
                    'status' => 'success',
                    'message' => '4-player semifinals created',
                    'progression_complete' => true
                ];
                
            case 'winners_final':
            case 'losers_semifinal':
                \Log::info("4-player semifinals completed - creating positions");
                $this->create4PlayerPositions($tournament, $level, $levelName);
                return [
                    'status' => 'success',
                    'message' => '4-player positions determined',
                    'progression_complete' => true
                ];
                
            default:
                \Log::info("No specific 4-player progression for round: {$completedRound}");
                return [
                    'status' => 'success',
                    'message' => "Round {$completedRound} completed, no 4-player progression needed",
                    'progression_complete' => false
                ];
        }
    }
    
    /**
     * Get winners from any completed round
     */
    private function getWinnersFromCompletedRound(Tournament $tournament, string $level, $groupId, string $completedRound)
    {
        \Log::info("=== GETTING 4-PLAYER WINNERS FROM COMPLETED ROUND ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'group_id' => $groupId,
            'completed_round' => $completedRound
        ]);

        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', $completedRound)
            ->where('status', 'completed')
            ->get();

        \Log::info("Found 4-player completed matches", [
            'match_count' => $completedMatches->count(),
            'matches' => $completedMatches->map(function($match) {
                return [
                    'id' => $match->id,
                    'round_name' => $match->round_name,
                    'status' => $match->status,
                    'winner_id' => $match->winner_id
                ];
            })->toArray()
        ]);

        $winners = collect();
        foreach ($completedMatches as $match) {
            if ($match->winner_id) {
                $winner = User::find($match->winner_id);
                if ($winner) {
                    $winners->push($winner);
                }
            }
        }

        \Log::info("Final 4-player winners collected", [
            'winner_count' => $winners->count(),
            'winner_names' => $winners->pluck('name')->toArray()
        ]);

        return $winners;
    }

}
