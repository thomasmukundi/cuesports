<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Winner;
use App\Models\Notification;
use App\Events\MatchPairingCreated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchAlgorithmService
{
    /**
     * Initialize tournament matches for a given level
     */
    public function initialize(int $tournamentId, string $level)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        // Handle special tournaments
        if ($level === 'special' || $tournament->special) {
            return $this->initializeSpecialTournament($tournament);
        }
        
        // Check if tournament is already initialized for this level
        $existingMatches = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->exists();
            
        if ($existingMatches) {
            throw new \Exception("Tournament already initialized");
        }
        
        DB::beginTransaction();
        try {
            // For community level, use simple demographic grouping
            if ($level === 'community') {
                $players = $this->getEligiblePlayers($tournament, $level);
                
                if ($players->isEmpty()) {
                    throw new \Exception("No eligible players found for {$level} level");
                }

                // Group players by their demographics
                $groups = $this->groupPlayersByDemographics($players, $level);
                
                foreach ($groups as $groupId => $groupPlayers) {
                    $this->createMatchesForGroup($tournament, $groupPlayers, $level, $groupId);
                }
            } else {
                // For county, regional, national levels, use smart grouping with previous group tracking
                DB::rollBack(); // Rollback the transaction started here
                $result = $this->initializeLevel($tournament->id, $level);
                
                // Send notifications to all players - get players for notifications
                $players = $this->getEligiblePlayers($tournament, $level);
                $this->sendPairingNotifications($tournament, $level);
                
                return $result;
            }
            
            DB::commit();
            
            // Send notifications to all players
            $this->sendPairingNotifications($tournament, $level);
            
            return ['status' => 'success', 'message' => "Tournament initialized for {$level} level"];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Tournament initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize tournament level with position-based grouping
     */
    public function initializeTournamentLevelWithPositions(int $tournamentId, string $level, array $positionGroups)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        // Check if tournament is already initialized for this level
        $existingMatches = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->exists();
            
        if ($existingMatches) {
            throw new \Exception("Tournament already initialized for {$level} level");
        }
        
        DB::beginTransaction();
        try {
            // Combine all position groups into one pool, prioritizing position 1 players
            $allPlayers = collect();
            
            // Add position 1 players first
            if (isset($positionGroups['position_1'])) {
                $allPlayers = $allPlayers->merge($positionGroups['position_1']);
            }
            
            // Add position 2 players
            if (isset($positionGroups['position_2'])) {
                $allPlayers = $allPlayers->merge($positionGroups['position_2']);
            }
            
            // Add position 3 players
            if (isset($positionGroups['position_3'])) {
                $allPlayers = $allPlayers->merge($positionGroups['position_3']);
            }
            
            if ($allPlayers->isEmpty()) {
                throw new \Exception("No eligible players found for {$level} level");
            }

            // Group players by their current level demographics while avoiding same previous level groupings
            $groups = $this->groupPlayersForNextLevel($allPlayers, $level);
            
            foreach ($groups as $groupId => $groupPlayers) {
                $this->createMatchesForGroup($tournament, $groupPlayers, $level, $groupId);
            }
            
            DB::commit();
            
            // Send notifications to all players
            $this->sendLevelInitializationNotifications($tournament, $level, $allPlayers);
            
            return ['status' => 'success', 'message' => "Tournament initialized for {$level} level with {$allPlayers->count()} players"];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Tournament level initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate next round matches
     */
    public function generateNextRound(Tournament $tournament, string $level, ?int $groupId = null): array
    {
        DB::beginTransaction();
        try {
            \Log::info("=== GENERATE NEXT ROUND START ===");
            
            // For special tournaments, use simplified logic
            \Log::info("=== TOURNAMENT TYPE CHECK ===", [
                'level' => $level,
                'tournament_special' => $tournament->special,
                'is_special_level' => ($level === 'special'),
                'is_special_tournament' => $tournament->special,
                'will_use_special_logic' => ($level === 'special' || $tournament->special)
            ]);
            
            if ($level === 'special' || $tournament->special) {
                \Log::info("USING SPECIAL TOURNAMENT LOGIC");
                return $this->generateSpecialTournamentNextRound($tournament, $level, $groupId);
            }
            
            // Get current round matches and winners
            $currentRoundMatches = $this->getCurrentRoundMatches($tournament, $level, $groupId);
            $winners = $this->getWinnersFromMatches($currentRoundMatches);
            
            \Log::info("=== ROUND COMPLETION CHECK START ===", [
                'tournament_id' => $tournament->id,
                'level' => $level,
                'group_id' => $groupId,
                'current_matches' => $currentRoundMatches->count(),
                'winners_found' => $winners->count(),
                'current_round_names' => $currentRoundMatches->pluck('round_name')->unique()->toArray(),
                'match_details' => $currentRoundMatches->map(function($match) {
                    return [
                        'id' => $match->id,
                        'round_name' => $match->round_name,
                        'match_name' => $match->match_name,
                        'status' => $match->status,
                        'winner_id' => $match->winner_id
                    ];
                })->toArray()
            ]);
            
            $originalPlayerCount = $this->getTotalPlayersInTournament($tournament, $level, $groupId);
            
            \Log::info("Progression decision analysis", [
                'winners_count' => $winners->count(),
                'original_player_count' => $originalPlayerCount,
                'current_round' => $currentRoundMatches->first()->round_name ?? 'unknown',
                'will_use_special_progression' => ($winners->count() <= 4 && $originalPlayerCount <= 4),
                'tournament_id' => $tournament->id
            ]);
            
            if ($winners->count() <= 4 && $originalPlayerCount <= 4) {
                // Handle special progression cases (original 2, 3, 4 player tournaments)
                $levelName = $this->getLevelName($level, $groupId);
                \Log::info("Using SPECIAL progression for small original tournament");
                $this->handleSpecialProgression($tournament, $winners, $level, $groupId, $currentRoundMatches, $levelName);
            } else {
                // Handle large group progression (>4 original players or continuation from larger tournament)
                \Log::info("Using LARGE GROUP progression for large tournament or continuation");
                $this->handleLargeGroupProgression($tournament, $winners, $level, $groupId, $currentRoundMatches);
            }
            
            DB::commit();
            
            // Send notifications
            $this->sendPairingNotifications($tournament, $level);
            
            return ['status' => 'success', 'message' => "Next round generated for {$level} level"];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Next round generation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate next round for special tournaments (simplified logic)
     */
    private function generateSpecialTournamentNextRound(Tournament $tournament, string $level, ?int $groupId = null): array
    {
        \Log::info("=== SPECIAL TOURNAMENT NEXT ROUND START ===", [
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name,
            'level' => $level,
            'group_id' => $groupId
        ]);
        
        // Get matches from the latest completed round
        $allMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->whereNotNull('winner_id');
            
        // Get all distinct round names and find the highest round number
        $allRounds = $allMatches->distinct('round_name')->pluck('round_name');
        
        $latestRoundName = $allRounds->sortByDesc(function($roundName) {
            if (str_contains($roundName, 'round_')) {
                return (int) str_replace('round_', '', $roundName);
            }
            return 0;
        })->first();
        
        \Log::info("Special tournament - determined current round", [
            'tournament_id' => $tournament->id,
            'all_rounds' => $allRounds->toArray(),
            'selected_round' => $latestRoundName
        ]);
        
        $currentRoundMatches = $allMatches->where('round_name', $latestRoundName)->get();
            
        if ($currentRoundMatches->isEmpty()) {
            throw new \Exception("No completed matches found for latest round: {$latestRoundName}");
        }
        
        // Get current round name
        $currentRoundName = $latestRoundName;
        $nextRoundName = $this->getNextRoundName($currentRoundName);
        
        // Get all winners from current round
        $winners = collect();
        foreach ($currentRoundMatches as $match) {
            if ($match->winner_id) {
                $winner = User::find($match->winner_id);
                if ($winner) {
                    $winners->push($winner);
                }
            }
        }
        
        $winners = $winners->unique('id'); // Remove duplicates (players who played twice)
        
        \Log::info("Special tournament progression analysis", [
            'current_round' => $currentRoundName,
            'next_round' => $nextRoundName,
            'completed_matches' => $currentRoundMatches->count(),
            'unique_winners' => $winners->count(),
            'winner_ids' => $winners->pluck('id')->toArray()
        ]);
        
        // Apply special tournament progression logic
        $finalPlayers = $this->applySpecialTournamentLogic($winners, $currentRoundMatches);
        
        \Log::info("Final players for next round", [
            'final_count' => $finalPlayers->count(),
            'players' => $finalPlayers->map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->toArray()
        ]);
        
        // Create matches for next round
        $this->createSpecialTournamentMatches($tournament, $finalPlayers, $level, $groupId, $nextRoundName);
        
        DB::commit();
        
        // Send notifications
        $this->sendPairingNotifications($tournament, $level);
        
        \Log::info("=== SPECIAL TOURNAMENT NEXT ROUND END ===");
        
        return [
            'status' => 'success', 
            'message' => "Next round generated for special tournament",
            'note' => "Used simplified special tournament logic"
        ];
    }

    /**
     * Apply special tournament progression logic
     */
    private function applySpecialTournamentLogic(Collection $winners, Collection $currentRoundMatches): Collection
    {
        $winnerCount = $winners->count();
        
        \Log::info("Applying special tournament logic", [
            'winner_count' => $winnerCount,
            'is_odd' => $winnerCount % 2 === 1,
            'is_greater_than_3' => $winnerCount > 3
        ]);
        
        // If odd number of winners > 3, add one random loser
        if ($winnerCount > 3 && $winnerCount % 2 === 1) {
            $losers = $this->getLosersFromMatches($currentRoundMatches);
            
            \Log::info("Adding best performing loser for odd winner count", [
                'winner_count' => $winnerCount,
                'available_losers' => $losers->count()
            ]);
            
            if ($losers->count() > 0) {
                $selectedLoser = $this->selectBestLoser($losers, $tournament, $level, $groupId, $currentRoundMatches);
                $winners->push($selectedLoser);
                
                \Log::info("Added best performing loser", [
                    'loser_id' => $selectedLoser->id,
                    'loser_name' => $selectedLoser->name,
                    'final_count' => $winners->count(),
                    'selection_method' => 'performance_based'
                ]);
            }
        }
        
        return $winners;
    }

    /**
     * Create matches for special tournament next round
     */
    private function createSpecialTournamentMatches(Tournament $tournament, Collection $players, string $level, ?int $groupId, string $roundName)
    {
        $levelName = $this->getLevelName($level, $groupId);
        $playerCount = $players->count();
        
        \Log::info("Creating special tournament matches", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'round_name' => $roundName,
            'player_count' => $playerCount
        ]);
        
        if ($playerCount <= 4) {
            // Handle special cases (4→2, 3→2, 2→1)
            $this->handleSpecialCases($tournament, $players, $level, $groupId, $levelName, $roundName);
        } else {
            // Standard pairing for >4 players
            $this->createStandardMatchesWithAvoidance($tournament, $players, $level, $groupId, $roundName, $levelName);
        }
    }

    /**
     * Handle special progression cases for 2, 3, and 4 player tournaments
     */
    private function handleSpecialProgression(Tournament $tournament, Collection $winners, string $level, $groupId, Collection $currentRoundMatches, $levelName)
    {
        $currentRound = $currentRoundMatches->first()->round_name;
        $originalPlayerCount = $this->getTotalPlayersInTournament($tournament, $level, $groupId);
        $currentWinnerCount = $winners->count();
        
        \Log::info("Special progression analysis", [
            'current_round' => $currentRound,
            'original_player_count' => $originalPlayerCount,
            'current_winner_count' => $currentWinnerCount,
            'tournament_id' => $tournament->id
        ]);
        
        // Use current winner count for progression logic, not original player count
        switch ($currentWinnerCount) {
            case 2:
                // 2-player tournament ends after 2_final - no progression needed
                return;
                
            case 3:
                if ($currentRound === '3_SF') {
                    // Create 3_final: loser vs bye player
                    $sfMatch = $currentRoundMatches->first();
                    $sfWinner = $sfMatch->winner_id;
                    $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
                    
                    // Find the bye player (player not in SF match)
                    $allPlayers = $this->getOriginalPlayersForTournament($tournament, $level, $groupId);
                    $byePlayer = $allPlayers->whereNotIn('id', [$sfMatch->player_1_id, $sfMatch->player_2_id])->first();
                    
                    PoolMatch::create([
                        'match_name' => '3_final_match',
                        'player_1_id' => $sfLoser,
                        'player_2_id' => $byePlayer->id,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => '3_final',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                    ]);
                } elseif ($currentRound === '3_final') {
                    // Check if we need a tie-breaker round
                    $finalMatch = $currentRoundMatches->first();
                    $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
                        ->where('level', $level)
                        ->where('group_id', $groupId)
                        ->where('round_name', '3_SF')
                        ->where('status', 'completed')
                        ->first();
                    
                    if ($sfMatch && $finalMatch) {
                        $sfWinner = $sfMatch->winner_id;
                        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
                        $finalWinner = $finalMatch->winner_id;
                        
                        // If SF loser won the final, no tie-breaker needed - they already played SF winner
                        if ($finalWinner === $sfLoser) {
                            // SF loser beat bye player - standard positioning applies
                            return;
                        }
                        
                        // If bye player won the final, create tie-breaker between SF winner and bye player
                        if ($finalWinner !== $sfLoser) {
                            PoolMatch::create([
                                'match_name' => '3_break_tie_final_match',
                                'player_1_id' => $sfWinner,
                                'player_2_id' => $finalWinner, // This is the bye player who won
                                'level' => $level,
                                'level_name' => $levelName,
                                'round_name' => '3_break_tie_final',
                                'tournament_id' => $tournament->id,
                                'group_id' => $groupId,
                                'status' => 'pending',
                            ]);
                        }
                    }
                }
                break;
                
            case 4:
                if ($currentRound === 'round_1' && $originalPlayerCount > 4) {
                    // 4 winners from larger tournament - create new Round 1 for these 4 players
                    \Log::info("Creating 4-player tournament from winners", [
                        'winners' => $winners->pluck('id')->toArray(),
                        'original_player_count' => $originalPlayerCount
                    ]);
                    
                    $this->create4PlayerTournamentFromWinners($tournament, $winners, $level, $groupId, $levelName);
                    return;
                    
                } elseif ($currentRound === 'round_1' && $originalPlayerCount === 4) {
                    // Original 4-player tournament - create winners final and losers semifinal
                    $matches = $currentRoundMatches->sortBy('match_name');
                    $match1 = $matches->first();
                    $match2 = $matches->last();
                    
                    $winner1 = $match1->winner_id;
                    $loser1 = ($match1->player_1_id === $winner1) ? $match1->player_2_id : $match1->player_1_id;
                    
                    $winner2 = $match2->winner_id;
                    $loser2 = ($match2->player_1_id === $winner2) ? $match2->player_2_id : $match2->player_1_id;
                    
                    // Create winners final (SF winners)
                    PoolMatch::create([
                        'match_name' => 'winners_final_match',
                        'player_1_id' => $winner1,
                        'player_2_id' => $winner2,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => 'winners_final',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    
                    // Create losers semifinal (SF losers)
                    PoolMatch::create([
                        'match_name' => 'losers_semifinal_match',
                        'player_1_id' => $loser1,
                        'player_2_id' => $loser2,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => 'losers_semifinal',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    
                } elseif ($currentRound === '4player_round1') {
                    // 4-player Round 1 completed - create winners final and losers semifinal
                    $matches = $currentRoundMatches->sortBy('match_name');
                    $match1 = $matches->first();
                    $match2 = $matches->last();
                    
                    $winner1 = $match1->winner_id;
                    $loser1 = ($match1->player_1_id === $winner1) ? $match1->player_2_id : $match1->player_1_id;
                    
                    $winner2 = $match2->winner_id;
                    $loser2 = ($match2->player_1_id === $winner2) ? $match2->player_2_id : $match2->player_1_id;
                    
                    // Create winners final (SF winners)
                    PoolMatch::create([
                        'match_name' => 'winners_final_match',
                        'player_1_id' => $winner1,
                        'player_2_id' => $winner2,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => 'winners_final',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    
                    // Create losers semifinal (SF losers)
                    PoolMatch::create([
                        'match_name' => 'losers_semifinal_match',
                        'player_1_id' => $loser1,
                        'player_2_id' => $loser2,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => 'losers_semifinal',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    
                } elseif ($currentRound === 'winners_final' || $currentRound === 'losers_semifinal') {
                    // Check if both winners final and losers semifinal matches are complete
                    $winnersFinal = PoolMatch::where('tournament_id', $tournament->id)
                        ->where('level', $level)
                        ->where('level_name', $levelName)
                        ->where('round_name', 'winners_final')
                        ->where('status', 'completed')
                        ->first();
                        
                    $losersSemifinal = PoolMatch::where('tournament_id', $tournament->id)
                        ->where('level', $level)
                        ->where('level_name', $levelName)
                        ->where('round_name', 'losers_semifinal')
                        ->where('status', 'completed')
                        ->first();
                    
                    if ($winnersFinal && $losersSemifinal) {
                        // Both matches complete - determine positions directly
                        // No additional matches needed - positions determined from these two matches
                        \Log::info("4-player tournament complete - determining final positions", [
                            'winners_final_winner' => $winnersFinal->winner_id,
                            'winners_final_loser' => ($winnersFinal->player_1_id === $winnersFinal->winner_id) ? $winnersFinal->player_2_id : $winnersFinal->player_1_id,
                            'losers_semifinal_winner' => $losersSemifinal->winner_id,
                            'losers_semifinal_loser' => ($losersSemifinal->player_1_id === $losersSemifinal->winner_id) ? $losersSemifinal->player_2_id : $losersSemifinal->player_1_id
                        ]);
                    }
                }
                break;
        }
    }

    /**
     * Get total players in tournament for a specific level and group
     */
    private function getTotalPlayersInTournament(Tournament $tournament, string $level, $groupId)
    {
        return $this->getOriginalPlayersForTournament($tournament, $level, $groupId)->count();
    }

    /**
     * Get original players for a tournament level/group
     */
    private function getOriginalPlayersForTournament(Tournament $tournament, string $level, ?int $groupId)
    {
        if ($level === 'community') {
            return $tournament->approvedPlayers->where('community_id', $groupId);
        } elseif ($level === 'special') {
            // For special tournaments, get all approved players (no previous level)
            return $tournament->approvedPlayers;
        } else {
            // For higher levels, get winners from previous level
            return Winner::where('tournament_id', $tournament->id)
                ->where('level', $this->getPreviousLevel($level))
                ->with('player')
                ->get()
                ->pluck('player')
                ->where($this->getLevelColumn($level), $groupId);
        }
    }

    /**
     * Get the column name for filtering players by level
     */
    private function getLevelColumn(string $level): string
    {
        switch ($level) {
            case 'county':
                return 'county_id';
            case 'regional':
                return 'region_id';
            case 'national':
                return 'country_id';
            default:
                return 'community_id';
        }
    }

    /**
     * Initialize a specific level of the tournament
     */
    public function initializeLevel(int $tournamentId, string $level, ?int $groupId = null, ?array $playerIds = null): array
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        if ($level === 'community') {
            return $this->initializeCommunityLevel($tournamentId, $groupId, $playerIds);
        } elseif ($level === 'special') {
            // For special tournaments, use approved players directly
            return $this->initializeSpecialLevel($tournamentId, $playerIds);
        }

        // Get winners from previous level
        $previousLevel = $this->getPreviousLevel($level);
        $winners = Winner::where('tournament_id', $tournamentId)
            ->where('level', $previousLevel)
            ->with('player')
            ->get();

        \Log::info("Retrieved {$winners->count()} winners from {$previousLevel} level for {$level} level");
        foreach ($winners as $winner) {
            \Log::info("Winner: player_id={$winner->player_id}, position={$winner->position}, community_id={$winner->player->community_id}");
        }

        if ($winners->isEmpty()) {
            throw new \Exception("No winners found from {$previousLevel} level to advance to {$level}");
        }

        $groups = $this->getGroupsForLevel($tournament, $level, $winners);
        $totalMatches = 0;

        foreach ($groups as $groupId => $players) {
            $matches = $this->createMatchesForGroup($tournament, $players, $level, $groupId);
            $totalMatches += count($matches ?? []);
        }

        return [
            'level' => $level,
            'groups_created' => count($groups),
            'total_matches' => $totalMatches,
            'message' => "Successfully initialized {$level} level with {$totalMatches} matches across " . count($groups) . " groups"
        ];
    }

    /**
     * Initialize community level with specific players
     */
    public function initializeCommunityLevel(int $tournamentId, ?int $groupId = null, ?array $playerIds = null): array
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        DB::beginTransaction();
        try {
            if ($playerIds) {
                // Use provided player IDs
                $players = User::whereIn('id', $playerIds)->with('community')->get();
            } else {
                // Get all registered players
                $players = DB::table('registered_users')
                    ->join('users', 'registered_users.player_id', '=', 'users.id')
                    ->where('registered_users.tournament_id', $tournamentId)
                    ->where('registered_users.payment_status', 'paid')
                    ->where('registered_users.status', 'approved')
                    ->select('users.*')
                    ->get()
                    ->map(function($user) {
                        return User::find($user->id);
                    });
            }

            if ($players->isEmpty()) {
                throw new \Exception("No eligible players found for community level");
            }

            // Group players by community
            $groups = $players->groupBy('community_id');
            $totalMatches = 0;
            $totalGroups = 0;

            foreach ($groups as $communityId => $communityPlayers) {
                if ($communityPlayers->count() >= 2) {
                    $this->createMatchesForGroup($tournament, $communityPlayers, 'community', $communityId);
                    $totalMatches += $this->calculateMatchesCreated($communityPlayers->count());
                    $totalGroups++;
                } elseif ($communityPlayers->count() == 1) {
                    // Single player automatically wins community level
                    Winner::create([
                        'player_id' => $communityPlayers->first()->id,
                        'position' => 1,
                        'level' => 'community',
                        'level_id' => $communityId,
                        'tournament_id' => $tournament->id,
                        'prize_amount' => $tournament->community_prize ?? 0,
                    ]);
                }
            }

            DB::commit();
            
            // Send notifications to all players with matches
            $this->sendPairingNotifications($tournament, 'community');
            
            return [
                'level' => 'community',
                'groups_created' => $totalGroups,
                'matches_created' => $totalMatches,
                'message' => "Successfully initialized community level with {$totalMatches} matches across {$totalGroups} communities"
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Community level initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize special level tournament
     */
    public function initializeSpecialLevel(int $tournamentId, ?array $playerIds = null): array
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        DB::beginTransaction();
        try {
            if ($playerIds) {
                // Use provided player IDs
                $players = User::whereIn('id', $playerIds)->get();
            } else {
                // Get all approved players for special tournament
                $players = $tournament->approvedPlayers;
            }

            if ($players->isEmpty()) {
                throw new \Exception("No eligible players found for special tournament");
            }

            \Log::info("Initializing special tournament", [
                'tournament_id' => $tournamentId,
                'player_count' => $players->count(),
                'tournament_name' => $tournament->name
            ]);

            // Create matches for all players in one group (no grouping for special tournaments)
            $this->createMatchesForGroup($tournament, $players, 'special', null);
            $totalMatches = $this->calculateMatchesCreated($players->count());

            DB::commit();
            
            // Send notifications to all players
            $this->sendPairingNotifications($tournament, 'special');
            
            return [
                'level' => 'special',
                'groups_created' => 1,
                'matches_created' => $totalMatches,
                'message' => "Successfully initialized special tournament with {$totalMatches} matches for {$players->count()} players"
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Special level initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate number of matches created for a given number of players
     */
    private function calculateMatchesCreated(int $playerCount): int
    {
        if ($playerCount <= 1) return 0;
        if ($playerCount == 2) return 1;
        if ($playerCount == 3) return 2; // One player plays twice = 2 matches
        if ($playerCount == 4) return 2; // Two R1 matches
        if ($playerCount == 5) return 3; // One player plays twice = 3 matches
        
        // For more players, calculate based on allowing one player to play twice if odd
        return ($playerCount % 2 == 0) ? intval($playerCount / 2) : intval($playerCount / 2) + 1;
    }

    /**
     * Group players for next level with previous group tracking
     */
    private function groupPlayersForNextLevel(Collection $allPlayers, string $level): array
    {
        $groups = [];
        
        foreach ($allPlayers as $player) {
            // Determine group ID based on level
            switch ($level) {
                case 'county':
                    $groupId = $player->county_id;
                    break;
                case 'regional':
                    $groupId = $player->region_id;
                    break;
                case 'national':
                    $groupId = 1; // Single national group
                    break;
                default:
                    continue 2;
            }

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = collect();
            }
            
            // Create enhanced player object with previous group info
            $previousGroup = $this->getPreviousGroupIdFromPlayer($player, $level);
            \Log::info("Grouping player for {$level}: player_id={$player->id}, group_id={$groupId}, previous_group={$previousGroup}");
            
            $groups[$groupId]->push((object)[
                'player' => $player,
                'winner' => (object)['position' => 1], // Default position since we don't have winner data here
                'previous_group' => $previousGroup
            ]);
        }
        
        return $groups;
    }
    
    /**
     * Get previous group ID from player based on current level
     */
    private function getPreviousGroupIdFromPlayer($player, string $level): ?int
    {
        switch ($level) {
            case 'county':
                return $player->community_id;
            case 'regional':
                return $player->county_id;
            case 'national':
                return $player->region_id;
            default:
                return null;
        }
    }

    /**
     * Get groups for a specific level based on winners from previous level
     */
    private function getGroupsForLevel(Tournament $tournament, string $level, $winners): array
    {
        $groups = [];

        foreach ($winners as $winner) {
            $player = $winner->player;
            
            switch ($level) {
                case 'county':
                    $groupId = $player->county_id;
                    break;
                case 'regional':
                    $groupId = $player->region_id;
                    break;
                case 'national':
                    $groupId = 1; // Single national group
                    break;
                default:
                    continue 2;
            }

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = collect();
            }
            
            $previousGroup = $this->getPreviousGroupId($winner, $level);
            \Log::info("Setting up player for {$level}: player_id={$player->id}, group_id={$groupId}, previous_group={$previousGroup}, position={$winner->position}");
            
            // Add winner object with player and previous group info
            $groups[$groupId]->push((object)[
                'player' => $player,
                'winner' => $winner,
                'previous_group' => $previousGroup
            ]);
        }

        return $groups;
    }

    /**
     * Get previous group ID for a winner based on current level
     */
    private function getPreviousGroupId($winner, string $level): ?int
    {
        switch ($level) {
            case 'county':
                // Previous level was community, get community_id from level_name
                return $winner->player->community_id;
            case 'regional':
                // Previous level was county, get county_id
                return $winner->player->county_id;
            case 'national':
                // Previous level was regional, get region_id
                return $winner->player->region_id;
            default:
                return null;
        }
    }

    /**
     * Get eligible players based on tournament level
     */
    private function getEligiblePlayers(Tournament $tournament, string $level): Collection
    {
        if ($level === 'community' || $tournament->special) {
            // Get approved registered users
            return $tournament->approvedPlayers;
        } else {
            // Get winners from previous level
            $previousLevel = $this->getPreviousLevel($level);
            return Winner::where('tournament_id', $tournament->id)
                ->where('level', $previousLevel)
                ->whereIn('position', [1, 2, 3])
                ->with('player')
                ->get()
                ->pluck('player');
        }
    }

    /**
     * Group players by demographics
     */
    private function groupPlayersByDemographics(Collection $players, string $level): Collection
    {
        switch ($level) {
            case 'community':
                return $players->groupBy('community_id');
            case 'county':
                return $players->groupBy('county_id');
            case 'regional':
                return $players->groupBy('region_id');
            case 'special':
            case 'national':
                // No grouping for special or national tournaments
                return collect([0 => $players]);
            default:
                throw new \Exception("Invalid level: {$level}");
        }
    }

    /**
     * Create matches for a group of players
     */
    private function createMatchesForGroup(Tournament $tournament, Collection $players, string $level, $groupId)
    {
        // Extract actual players if we have winner objects with previous group info
        $actualPlayers = $players->map(function ($item) {
            return is_object($item) && isset($item->player) ? $item->player : $item;
        });
        
        $playerCount = $actualPlayers->count();
        $levelName = $this->getLevelName($level, $groupId);
        
        // Debug: Log the level name being generated
        \Log::info("Creating matches for group {$groupId}: level_name = '{$levelName}'");
        
        if ($playerCount === 1) {
            // Single player automatically wins
            $player = $actualPlayers->first();
            Winner::create([
                'player_id' => $player->id,
                'position' => 1,
                'level' => $level,
                'level_name' => $levelName,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
                'points' => 3,
            ]);
            return [];
        }
        
        // Use smart pairing for county/regional/national levels
        if (in_array($level, ['county', 'regional', 'national'])) {
            return $this->createSmartPairingForLevel($tournament, $players, $level, $groupId);
        }
        
        // Use original logic for community level
        if ($playerCount <= 4) {
            return $this->handleSpecialCases($tournament, $actualPlayers, $level, $groupId, $levelName);
        } else {
            return $this->createStandardMatches($tournament, $actualPlayers, $level, $groupId, 'round_1', $levelName);
        }
    }

    /**
     * Create smart pairing for county/regional/national levels avoiding same previous group
     */
    private function createSmartPairingForLevel(Tournament $tournament, Collection $players, string $level, int $groupId): array
    {
        $levelName = $this->getLevelName($level, $groupId);
        $playerCount = $players->count();
        
        \Log::info("=== SMART PAIRING DECISION POINT ===", [
            'level' => $level,
            'player_count' => $playerCount,
            'group_id' => $groupId,
            'tournament_id' => $tournament->id,
            'will_use_special_cases' => ($playerCount <= 4),
            'will_use_standard_progression' => ($playerCount > 4),
            'players' => $players->map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->toArray()
        ]);
        
        // Only handle special cases for 1-4 players
        if ($playerCount <= 4) {
            \Log::info("USING handleSpecialCasesWithSmartPairing for {$playerCount} players");
            return $this->handleSpecialCasesWithSmartPairing($tournament, $players, $level, $groupId, $levelName);
        }
        
        // For 5+ players (including 6,7,8,9,10+), use standard round-based progression
        // This creates round_1 matches and lets normal progression handle subsequent rounds
        return $this->createStandardRound1WithSmartPairing($tournament, $players, $level, $groupId, $levelName);
    }

    /**
     * Handle special cases (1-4 players) with smart pairing to avoid same previous group
     */
    private function handleSpecialCasesWithSmartPairing(Tournament $tournament, Collection $players, string $level, int $groupId, string $levelName): array
    {
        $playerCount = $players->count();
        $matches = [];
        
        if ($playerCount === 1) {
            // Single player automatically wins
            $playerData = $players->first();
            $player = is_object($playerData) && isset($playerData->player) ? $playerData->player : $playerData;
            
            Winner::create([
                'player_id' => $player->id,
                'position' => 1,
                'level' => $level,
                'level_name' => $levelName,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
                'points' => 3,
            ]);
            return $matches;
        }
        
        if ($playerCount === 2) {
            // Create direct final with smart pairing
            $pairedPlayers = $this->smartPairPlayers($players, $level);
            $match = $this->createMatch($tournament, $pairedPlayers[0], $pairedPlayers[1], '2_final', $level, $groupId, $levelName);
            $matches[] = $match;
        }
        
        if ($playerCount === 3) {
            // Create 3-player tournament: SF + bye, then final
            $pairedPlayers = $this->smartPairPlayers($players, $level);
            
            // Semifinal match (2 players)
            $sfMatch = $this->createMatch($tournament, $pairedPlayers[0], $pairedPlayers[1], '3_SF', $level, $groupId, $levelName, $pairedPlayers[2]->id);
            $matches[] = $sfMatch;
        }
        
        if ($playerCount === 4) {
            // Create 4-player tournament: Use unique round name to avoid conflicts
            $pairedPlayers = $this->smartPairPlayers($players, $level);
            
            \Log::info("=== CREATING 4-PLAYER SPECIAL CASE MATCHES ===", [
                'tournament_id' => $tournament->id,
                'level' => $level,
                'group_id' => $groupId,
                'round_name_to_use' => '4player_round1',
                'players' => array_map(function($p) {
                    return ['id' => $p->id ?? $p['id'], 'name' => $p->name ?? $p['name']];
                }, $pairedPlayers)
            ]);
            
            // 4-Player Round 1 Match 1: A vs B
            $match1 = $this->createMatch($tournament, $pairedPlayers[0], $pairedPlayers[1], '4player_round1', $level, $groupId, $levelName);
            $matches[] = $match1;
            
            // 4-Player Round 1 Match 2: C vs D
            $match2 = $this->createMatch($tournament, $pairedPlayers[2], $pairedPlayers[3], '4player_round1', $level, $groupId, $levelName);
            $matches[] = $match2;
        }
        
        return $matches;
    }

    /**
     * Smart pair players avoiding same previous group when possible
     */
    private function smartPairPlayers(Collection $players, string $level): array
    {
        $playerArray = $players->all();
        
        // If we have player objects with previous group info, use smart pairing
        if (count($playerArray) > 0 && is_object($playerArray[0]) && isset($playerArray[0]->previous_group)) {
            \Log::info("Smart pairing triggered for {$level} with " . count($playerArray) . " players");
            return $this->pairAvoidingSamePreviousGroup($playerArray, $level);
        }
        
        // Fallback to simple pairing for community level or when no previous group info
        \Log::info("Fallback to simple pairing for {$level} - no previous group info detected");
        return $playerArray;
    }

    /**
     * Pair players avoiding same previous group with position-based matching
     */
    private function pairAvoidingSamePreviousGroup(array $players, string $level): array
    {
        $paired = [];
        $used = [];
        
        // Group players by their previous group AND position
        $groupedByPreviousAndPosition = [];
        foreach ($players as $index => $playerData) {
            $prevGroup = $playerData->previous_group;
            $position = $playerData->winner->position ?? 1; // Default to position 1 if not set
            
            \Log::info("Processing player {$index}: previous_group={$prevGroup}, position={$position}");
            
            if (!isset($groupedByPreviousAndPosition[$prevGroup])) {
                $groupedByPreviousAndPosition[$prevGroup] = [];
            }
            if (!isset($groupedByPreviousAndPosition[$prevGroup][$position])) {
                $groupedByPreviousAndPosition[$prevGroup][$position] = [];
            }
            $groupedByPreviousAndPosition[$prevGroup][$position][] = ['index' => $index, 'data' => $playerData];
        }
        
        $previousGroups = array_keys($groupedByPreviousAndPosition);
        
        // Enhanced position-based pairing with cross-group priority
        if (count($previousGroups) >= 2) {
            \Log::info("Smart pairing for {$level}: Starting position-based cross-group pairing with groups: " . implode(', ', $previousGroups));
            
            // Debug: Log the grouped data
            foreach ($previousGroups as $groupId) {
                foreach ($groupedByPreviousAndPosition[$groupId] as $position => $players) {
                    \Log::info("Group {$groupId} Position {$position}: " . count($players) . " players");
                }
            }
            
            // Priority order: Position 1 -> Position 2 -> Position 3
            for ($position = 1; $position <= 3; $position++) {
                $this->pairPlayersAtPosition($groupedByPreviousAndPosition, $previousGroups, $position, $paired, $used, $level);
            }
            
            // Handle any remaining players from different groups (fallback pairing)
            $this->pairRemainingCrossGroup($groupedByPreviousAndPosition, $previousGroups, $paired, $used, $level);
        } else {
            \Log::info("Smart pairing for {$level}: Only " . count($previousGroups) . " previous groups found, using fallback pairing");
        }
        
        // Add remaining players (same previous group pairing as last resort)
        foreach ($players as $index => $playerData) {
            if (!in_array($index, $used)) {
                $paired[] = $playerData->player;
            }
        }
        
        \Log::info("Smart pairing for {$level}: " . count($paired) . " players paired with position-based cross-group priority");
        
        return $paired;
    }

    /**
     * Pair players at specific position from different previous groups
     */
    private function pairPlayersAtPosition($groupedByPreviousAndPosition, $previousGroups, $position, &$paired, &$used, $level)
    {
        $playersAtPosition = [];
        
        // Collect all players at this position from different groups
        foreach ($previousGroups as $groupId) {
            if (isset($groupedByPreviousAndPosition[$groupId][$position])) {
                foreach ($groupedByPreviousAndPosition[$groupId][$position] as $player) {
                    if (!in_array($player['index'], $used)) {
                        $playersAtPosition[] = ['group' => $groupId, 'player' => $player];
                    }
                }
            }
        }
        
        // If we have players from different groups at this position, pair them
        if (count($playersAtPosition) >= 2) {
            $groupedByGroup = [];
            foreach ($playersAtPosition as $item) {
                $groupedByGroup[$item['group']][] = $item['player'];
            }
            
            $groups = array_keys($groupedByGroup);
            
            // Pair across different groups
            for ($i = 0; $i < count($groups); $i++) {
                for ($j = $i + 1; $j < count($groups); $j++) {
                    $group1Players = $groupedByGroup[$groups[$i]];
                    $group2Players = $groupedByGroup[$groups[$j]];
                    
                    $maxPairs = min(count($group1Players), count($group2Players));
                    
                    for ($k = 0; $k < $maxPairs; $k++) {
                        if (!in_array($group1Players[$k]['index'], $used) && 
                            !in_array($group2Players[$k]['index'], $used)) {
                            
                            $paired[] = $group1Players[$k]['data']->player;
                            $paired[] = $group2Players[$k]['data']->player;
                            $used[] = $group1Players[$k]['index'];
                            $used[] = $group2Players[$k]['index'];
                            
                            \Log::info("Paired position {$position} players from different groups: {$groups[$i]} vs {$groups[$j]}");
                        }
                    }
                }
            }
        }
        
        // Handle odd numbers: pair with next available position from different group
        if ($position < 3) {
            $this->handleOddPositionPairing($groupedByPreviousAndPosition, $previousGroups, $position, $paired, $used, $level);
        }
    }
    
    /**
     * Handle odd position pairing (e.g., 3 position 1s -> pair with position 2)
     */
    private function handleOddPositionPairing($groupedByPreviousAndPosition, $previousGroups, $currentPosition, &$paired, &$used, $level)
    {
        $nextPosition = $currentPosition + 1;
        
        // Find unpaired players at current position
        $unpairedAtCurrent = [];
        foreach ($previousGroups as $groupId) {
            if (isset($groupedByPreviousAndPosition[$groupId][$currentPosition])) {
                foreach ($groupedByPreviousAndPosition[$groupId][$currentPosition] as $player) {
                    if (!in_array($player['index'], $used)) {
                        $unpairedAtCurrent[] = ['group' => $groupId, 'player' => $player];
                    }
                }
            }
        }
        
        // Find players at next position from different groups
        foreach ($unpairedAtCurrent as $currentPlayer) {
            foreach ($previousGroups as $groupId) {
                if ($groupId !== $currentPlayer['group'] && isset($groupedByPreviousAndPosition[$groupId][$nextPosition])) {
                    foreach ($groupedByPreviousAndPosition[$groupId][$nextPosition] as $nextPlayer) {
                        if (!in_array($nextPlayer['index'], $used) && !in_array($currentPlayer['player']['index'], $used)) {
                            
                            $paired[] = $currentPlayer['player']['data']->player;
                            $paired[] = $nextPlayer['data']->player;
                            $used[] = $currentPlayer['player']['index'];
                            $used[] = $nextPlayer['index'];
                            
                            \Log::info("Paired position {$currentPosition} with position {$nextPosition} from different groups");
                            break 2;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Pair any remaining players from different groups (fallback)
     */
    private function pairRemainingCrossGroup($groupedByPreviousAndPosition, $previousGroups, &$paired, &$used, $level)
    {
        $remainingPlayers = [];
        
        // Collect all remaining unpaired players
        foreach ($previousGroups as $groupId) {
            foreach ($groupedByPreviousAndPosition[$groupId] as $position => $players) {
                foreach ($players as $player) {
                    if (!in_array($player['index'], $used)) {
                        $remainingPlayers[] = ['group' => $groupId, 'player' => $player];
                    }
                }
            }
        }
        
        // Pair remaining players from different groups
        $groupedRemaining = [];
        foreach ($remainingPlayers as $item) {
            $groupedRemaining[$item['group']][] = $item['player'];
        }
        
        $groups = array_keys($groupedRemaining);
        
        for ($i = 0; $i < count($groups); $i++) {
            for ($j = $i + 1; $j < count($groups); $j++) {
                $group1Players = $groupedRemaining[$groups[$i]];
                $group2Players = $groupedRemaining[$groups[$j]];
                
                $maxPairs = min(count($group1Players), count($group2Players));
                
                for ($k = 0; $k < $maxPairs; $k++) {
                    if (!in_array($group1Players[$k]['index'], $used) && 
                        !in_array($group2Players[$k]['index'], $used)) {
                        
                        $paired[] = $group1Players[$k]['data']->player;
                        $paired[] = $group2Players[$k]['data']->player;
                        $used[] = $group1Players[$k]['index'];
                        $used[] = $group2Players[$k]['index'];
                        
                        \Log::info("Fallback pairing from different groups: {$groups[$i]} vs {$groups[$j]}");
                    }
                }
            }
        }
    }

    /**
     * Create a single match with proper structure
     */
    private function createMatch(Tournament $tournament, $player1, $player2, string $roundName, string $level, int $groupId, string $levelName, ?int $byePlayerId = null, ?string $matchName = null): \App\Models\PoolMatch
    {
        $matchName = $matchName ?? "{$roundName}_match";
        
        return \App\Models\PoolMatch::create([
            'match_name' => $matchName,
            'player_1_id' => $player1->id,
            'player_2_id' => $player2->id,
            'bye_player_id' => $byePlayerId,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => $roundName,
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Create standard round 1 matches for 5+ players with smart pairing
     * This creates round_1 matches and lets normal progression handle subsequent rounds (3-player, 2-player, etc.)
     */
    private function createStandardRound1WithSmartPairing(Tournament $tournament, Collection $players, string $level, int $groupId, string $levelName): array
    {
        $matches = [];
        $pairedPlayers = $this->smartPairPlayers($players, $level);
        $playerCount = count($pairedPlayers);
        
        \Log::info("Creating round 1 matches for {$playerCount} players with smart pairing");
        
        // Create round_1 matches - pair players and create matches
        $matchNumber = 1;
        for ($i = 0; $i < $playerCount - 1; $i += 2) {
            if ($i + 1 < $playerCount) {
                $matchName = "round_1__match{$matchNumber}";
                $match = $this->createMatch(
                    $tournament, 
                    $pairedPlayers[$i], 
                    $pairedPlayers[$i + 1], 
                    'round_1', 
                    $level, 
                    $groupId, 
                    $levelName,
                    null, // no bye player
                    $matchName
                );
                $matches[] = $match;
                $matchNumber++;
            }
        }
        
        // Handle odd player - create additional match with one player playing twice
        if ($playerCount % 2 === 1) {
            $oddPlayer = $pairedPlayers[$playerCount - 1];
            
            // Pair odd player with first player (who plays twice)
            if (count($pairedPlayers) >= 3) {
                $matchName = "round_1__match{$matchNumber}";
                $match = $this->createMatch(
                    $tournament, 
                    $oddPlayer, 
                    $pairedPlayers[0], 
                    'round_1', 
                    $level, 
                    $groupId, 
                    $levelName,
                    null, // no bye player
                    $matchName
                );
                $matches[] = $match;
            }
        }
        
        \Log::info("Created " . count($matches) . " round_1 matches for {$playerCount} players at {$level} level");
        
        return $matches;
    }

    /**
     * Send notifications for level initialization
     */
    private function sendLevelInitializationNotifications(Tournament $tournament, string $level, Collection $players)
    {
        foreach ($players as $player) {
            Notification::create([
                'player_id' => $player->id,
                'type' => 'tournament_started',
                'message' => "You have qualified for the {$level} level tournament. Check your matches to see your opponents.",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'level' => $level
                ]
            ]);
        }
    }

    /**
     * Group by county while avoiding same community matches
     */
    private function groupByCountyAvoidingCommunities(Collection $players)
    {
        return $players->groupBy('county_id')->map(function ($countyPlayers, $countyId) {
            // Within each county, try to pair players from different communities
            return $this->diversifyByCommunity($countyPlayers);
        });
    }

    /**
     * Group by region while avoiding same county matches
     */
    private function groupByRegionAvoidingCounties(Collection $players)
    {
        return $players->groupBy('region_id')->map(function ($regionPlayers, $regionId) {
            // Within each region, try to pair players from different counties
            return $this->diversifyByCounty($regionPlayers);
        });
    }

    /**
     * Group nationally while avoiding same region matches
     */
    private function groupNationallyAvoidingRegions(Collection $players)
    {
        // For national level, create groups that mix players from different regions
        return collect([1 => $this->diversifyByRegion($players)]);
    }

    /**
     * Diversify players by community within county matches
     */
    private function diversifyByCommunity(Collection $players)
    {
        return $players->shuffle(); // Simple shuffle for now, can be enhanced
    }

    /**
     * Diversify players by county within regional matches
     */
    private function diversifyByCounty(Collection $players)
    {
        return $players->shuffle(); // Simple shuffle for now, can be enhanced
    }

    /**
     * Diversify players by region for national matches
     */
    private function diversifyByRegion(Collection $players)
    {
        return $players->shuffle(); // Simple shuffle for now, can be enhanced
    }

    /**
     * Create matches with position-based grouping and community avoidance
     */
    private function createPositionBasedMatches(Tournament $tournament, Collection $players, string $level, string $position)
    {
        // Group by geographic area to avoid same-origin matches
        $groupedPlayers = $this->groupPlayersByGeography($players, $level);
        
        foreach ($groupedPlayers as $groupId => $groupPlayers) {
            $levelName = $this->getLevelName($level, $groupId);
            
            // Create matches with community/county avoidance
            $this->createCommunityAwareMatches($groupPlayers, $tournament, $level, $groupId, 'round_1', $position, $levelName);
        }
    }

    /**
     * Group players by geography based on level
     */
    private function groupPlayersByGeography(Collection $players, string $level)
    {
        switch ($level) {
            case 'county':
                return $players->groupBy('county_id');
            case 'regional':
                return $players->groupBy('region_id');
            case 'national':
                // For national, group by region to avoid same-region matches initially
                return $players->groupBy('region_id');
            default:
                return $players->groupBy('community_id');
        }
    }

    /**
     * Create matches with community/county avoidance
     */
    private function createCommunityAwareMatches(Collection $players, Tournament $tournament, string $level, $groupId, string $roundName, string $position, string $levelName)
    {
        $playerCount = $players->count();
        
        \Log::info("=== CREATE COMMUNITY AWARE MATCHES START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'group_id' => $groupId,
            'round_name' => $roundName,
            'position' => $position,
            'player_count' => $playerCount,
            'players' => $players->map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->toArray()
        ]);
        
        if ($playerCount === 1) {
            \Log::info("Single player - creating winner automatically", [
                'player_id' => $players->first()->id,
                'player_name' => $players->first()->name
            ]);
            // Single player automatically wins
            Winner::create([
                'player_id' => $players->first()->id,
                'position' => 1,
                'level' => $level,
                'level_name' => $levelName,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            return;
        }
        
        \Log::info("Shuffling players with avoidance for level: {$level}");
        // Shuffle players to randomize pairings while avoiding same community/county
        $shuffledPlayers = $this->shuffleWithAvoidance($players, $level);
        
        if ($playerCount <= 4) {
            \Log::info("Using handleSpecialCases for {$playerCount} players");
            $this->handleSpecialCases($tournament, $shuffledPlayers, $level, $groupId, $levelName);
        } else {
            \Log::info("Using createStandardMatchesWithAvoidance for {$playerCount} players");
            $this->createStandardMatchesWithAvoidance($tournament, $shuffledPlayers, $level, $groupId, $roundName, $levelName);
        }
        
        \Log::info("=== CREATE COMMUNITY AWARE MATCHES END ===");
    }

    /**
     * Shuffle players while avoiding same-origin pairings
     */
    private function shuffleWithAvoidance(Collection $players, string $level)
    {
        $originField = $this->getOriginField($level);
        
        // Group by origin (community/county)
        $grouped = $players->groupBy($originField);
        
        // If only one origin or can't avoid, return shuffled
        if ($grouped->count() <= 1) {
            return $players->shuffle();
        }
        
        // Try to pair players from different origins
        $paired = collect();
        $remaining = collect();
        
        foreach ($grouped as $originId => $originPlayers) {
            $remaining = $remaining->merge($originPlayers);
        }
        
        return $remaining->shuffle();
    }

    /**
     * Get origin field based on level
     */
    private function getOriginField(string $level)
    {
        switch ($level) {
            case 'county':
            case 'regional':
            case 'national':
                return 'community_id'; // Avoid same community
            default:
                return 'community_id';
        }
    }

    /**
     * Create standard matches with community avoidance and proper odd number handling
     */
    private function createStandardMatchesWithAvoidance(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, string $levelName)
    {
        // Convert Collection to simple array with proper indexing
        $playerArray = $players->values()->all();
        $playerCount = count($playerArray);
        $matchNumber = 1;
        
        \Log::info("=== CREATE STANDARD MATCHES WITH AVOIDANCE START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'group_id' => $groupId,
            'round_name' => $roundName,
            'player_count' => $playerCount,
            'players' => array_map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            }, $playerArray)
        ]);
        
        // Handle odd number of players - one player must play twice
        if ($playerCount % 2 == 1) {
            // Pick a random player to play twice
            $doublePlayerIndex = array_rand($playerArray);
            $doublePlayer = $playerArray[$doublePlayerIndex];
            
            \Log::info("Odd number ({$playerCount}) players - Player {$doublePlayer->id} will play twice");
            
            // Create first match with the double player
            $firstOpponentIndex = ($doublePlayerIndex + 1) % $playerCount;
            if ($firstOpponentIndex == $doublePlayerIndex) {
                $firstOpponentIndex = ($doublePlayerIndex + 2) % $playerCount;
            }
            $firstOpponent = $playerArray[$firstOpponentIndex];
            
            $match1 = PoolMatch::create([
                'match_name' => "{$roundName}_M{$matchNumber}",
                'player_1_id' => $doublePlayer->id,
                'player_2_id' => $firstOpponent->id,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
            ]);
            
            \Log::info("Created match #{$matchNumber}", [
                'match_id' => $match1->id,
                'match_name' => $match1->match_name,
                'player_1_id' => $match1->player_1_id,
                'player_2_id' => $match1->player_2_id,
                'level' => $match1->level,
                'round_name' => $match1->round_name
            ]);
            
            $matchNumber++;
            
            // Remove both players from the array for remaining pairings
            $remainingPlayers = [];
            for ($i = 0; $i < $playerCount; $i++) {
                if ($i != $doublePlayerIndex && $i != $firstOpponentIndex) {
                    $remainingPlayers[] = $playerArray[$i];
                }
            }
            
            // Create matches for remaining players
            \Log::info("Creating matches for remaining players", [
                'remaining_count' => count($remainingPlayers)
            ]);
            
            for ($i = 0; $i < count($remainingPlayers); $i += 2) {
                if (isset($remainingPlayers[$i + 1])) {
                    $match = PoolMatch::create([
                        'match_name' => "{$roundName}_M{$matchNumber}",
                        'player_1_id' => $remainingPlayers[$i]->id,
                        'player_2_id' => $remainingPlayers[$i + 1]->id,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => $roundName,
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    
                    \Log::info("Created match #{$matchNumber}", [
                        'match_id' => $match->id,
                        'match_name' => $match->match_name,
                        'player_1_id' => $match->player_1_id,
                        'player_2_id' => $match->player_2_id
                    ]);
                    
                    $matchNumber++;
                }
            }
            
            // Create second match for the double player with the remaining unpaired player
            if (count($remainingPlayers) % 2 == 1) {
                $lastPlayer = end($remainingPlayers);
                \Log::info("Creating second match for double player with last remaining player", [
                    'double_player_id' => $doublePlayer->id,
                    'last_player_id' => $lastPlayer->id
                ]);
                
                $finalMatch = PoolMatch::create([
                    'match_name' => "{$roundName}_M{$matchNumber}",
                    'player_1_id' => $doublePlayer->id,
                    'player_2_id' => $lastPlayer->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $roundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                
                \Log::info("Created final match #{$matchNumber}", [
                    'match_id' => $finalMatch->id,
                    'match_name' => $finalMatch->match_name,
                    'player_1_id' => $finalMatch->player_1_id,
                    'player_2_id' => $finalMatch->player_2_id
                ]);
                
                $matchNumber++;
            }
        } else {
            // Even number - normal pairing
            \Log::info("Even number of players - creating normal pairs", [
                'player_count' => $playerCount,
                'expected_matches' => $playerCount / 2
            ]);
            
            for ($i = 0; $i < $playerCount; $i += 2) {
                if (isset($playerArray[$i + 1])) {
                    $match = PoolMatch::create([
                        'match_name' => "{$roundName}_M{$matchNumber}",
                        'player_1_id' => $playerArray[$i]->id,
                        'player_2_id' => $playerArray[$i + 1]->id,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => $roundName,
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    
                    \Log::info("Created even-pairing match #{$matchNumber}", [
                        'match_id' => $match->id,
                        'match_name' => $match->match_name,
                        'player_1_id' => $match->player_1_id,
                        'player_1_name' => $playerArray[$i]->name,
                        'player_2_id' => $match->player_2_id,
                        'player_2_name' => $playerArray[$i + 1]->name
                    ]);
                    
                    $matchNumber++;
                } else {
                    \Log::warning("Missing player for pairing", [
                        'index' => $i,
                        'player_1' => $playerArray[$i]->name ?? 'Unknown',
                        'player_2_missing' => true
                    ]);
                }
            }
        }
        
        $totalMatches = $matchNumber - 1;
        \Log::info("=== CREATE STANDARD MATCHES WITH AVOIDANCE END ===", [
            'total_matches_created' => $totalMatches,
            'player_count' => $playerCount,
            'tournament_id' => $tournament->id,
            'level' => $level,
            'round_name' => $roundName
        ]);
        
        // Verify matches were actually created in database
        $createdMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->get();
            
        \Log::info("Database verification - matches found", [
            'matches_in_db' => $createdMatches->count(),
            'match_ids' => $createdMatches->pluck('id')->toArray()
        ]);
    }

    /**
     * Handle large group progression (>4 players) with odd number handling
     */
    private function handleLargeGroupProgression(Tournament $tournament, Collection $winners, string $level, $groupId, Collection $currentRoundMatches)
    {
        $levelName = $this->getLevelName($level, $groupId);
        $currentRoundName = $currentRoundMatches->first()->round_name;
        $nextRoundName = $this->getNextRoundName($currentRoundName);
        
        \Log::info("=== LARGE GROUP PROGRESSION START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'current_round_name' => $currentRoundName,
            'next_round_name' => $nextRoundName,
            'initial_winner_count' => $winners->count(),
            'current_round_matches' => $currentRoundMatches->count(),
            'winner_ids' => $winners->pluck('id')->toArray(),
            'winner_names' => $winners->pluck('name')->toArray()
        ]);
        
        // If odd number of winners > 3, add a loser to make even pairs
        if ($winners->count() > 3 && $winners->count() % 2 === 1) {
            $losers = $this->getLosersFromMatches($currentRoundMatches);
            \Log::info("Processing odd winner count", [
                'winner_count' => $winners->count(),
                'losers_available' => $losers->count(),
                'loser_ids' => $losers->pluck('id')->toArray()
            ]);
            
            if ($losers->count() > 0) {
                // Select best performing loser based on metrics
                $selectedLoser = $this->selectBestLoser($losers, $tournament, $level, $groupId, $currentRoundMatches);
                $winners->push($selectedLoser);
                \Log::info("Added BEST PERFORMING loser player {$selectedLoser->id} ({$selectedLoser->name}) to make even number", [
                    'total_losers_available' => $losers->count(),
                    'selected_loser_id' => $selectedLoser->id,
                    'selected_loser_name' => $selectedLoser->name,
                    'final_player_count' => $winners->count(),
                    'logic' => 'PROGRESSION: Best performing loser added for odd winner count'
                ]);
            } else {
                \Log::warning("No losers available to add for odd winner count", [
                    'winner_count' => $winners->count(),
                    'matches_checked' => $currentRoundMatches->count()
                ]);
            }
        }
        
        \Log::info("About to create standard matches", [
            'final_player_count' => $winners->count(),
            'players' => $winners->map(function($w) {
                return ['id' => $w->id, 'name' => $w->name];
            })->toArray()
        ]);
        
        // For odd numbers ≤ 3, let one player play twice (handled in createStandardMatches)
        $this->createStandardMatches($tournament, $winners, $level, $groupId, $nextRoundName, $levelName);
        
        \Log::info("=== LARGE GROUP PROGRESSION END ===");
    }

    /**
     * Get losers from matches
     */
    private function getLosersFromMatches(Collection $matches)
    {
        $loserIds = collect();
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $loserId = ($match->player_1_id === $match->winner_id) ? $match->player_2_id : $match->player_1_id;
                $loserIds->push($loserId);
            }
        }
        return User::whereIn('id', $loserIds)->get();
    }
    
    /**
     * Select the best performing loser based on performance metrics
     */
    private function selectBestLoser($losers, Tournament $tournament, string $level, ?int $groupId, $roundMatches)
    {
        if ($losers->count() === 1) {
            return $losers->first();
        }
        
        \Log::info("Selecting best loser from candidates (using all-time stats)", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'candidate_count' => $losers->count(),
            'candidate_ids' => $losers->pluck('id')->toArray(),
            'criteria' => 'current_round_points > total_all_time_points > all_time_win_rate'
        ]);
        
        $loserMetrics = [];
        
        foreach ($losers as $loser) {
            // Get points from the current round match where they lost
            $currentRoundPoints = 0;
            foreach ($roundMatches as $match) {
                if ($match->player_1_id == $loser->id) {
                    $currentRoundPoints = $match->player_1_points ?? 0;
                    break;
                } elseif ($match->player_2_id == $loser->id) {
                    $currentRoundPoints = $match->player_2_points ?? 0;
                    break;
                }
            }
            
            // Get all-time stats (across all tournaments)
            $allMatches = PoolMatch::where(function($q) use ($loser) {
                    $q->where('player_1_id', $loser->id)->orWhere('player_2_id', $loser->id);
                })
                ->where('status', 'completed')
                ->get();
            
            $totalAllTimePoints = 0;
            $wins = 0;
            $totalMatches = $allMatches->count();
            
            foreach ($allMatches as $match) {
                if ($match->player_1_id == $loser->id) {
                    $totalAllTimePoints += $match->player_1_points ?? 0;
                    if ($match->winner_id == $loser->id) $wins++;
                } else {
                    $totalAllTimePoints += $match->player_2_points ?? 0;
                    if ($match->winner_id == $loser->id) $wins++;
                }
            }
            
            $winRate = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;
            
            $loserMetrics[$loser->id] = [
                'player' => $loser,
                'current_round_points' => $currentRoundPoints,
                'total_all_time_points' => $totalAllTimePoints,
                'wins' => $wins,
                'total_matches' => $totalMatches,
                'win_rate' => $winRate,
                'name' => $loser->name
            ];
        }
        
        // Sort by: 1) Current round points (desc), 2) Total all-time points (desc), 3) All-time win rate (desc)
        $sortedLosers = collect($loserMetrics)->sortByDesc(function($metrics) {
            return [
                $metrics['current_round_points'],
                $metrics['total_all_time_points'], 
                $metrics['win_rate']
            ];
        })->values();
        
        // Check for ties at the top
        $topMetrics = $sortedLosers->first();
        $tiedLosers = $sortedLosers->filter(function($metrics) use ($topMetrics) {
            return $metrics['current_round_points'] == $topMetrics['current_round_points'] &&
                   $metrics['total_all_time_points'] == $topMetrics['total_all_time_points'] &&
                   $metrics['win_rate'] == $topMetrics['win_rate'];
        });
        
        if ($tiedLosers->count() > 1) {
            // Multiple losers tied - random selection from tied group
            $selectedMetrics = $tiedLosers->random();
            \Log::info("Multiple losers tied for best performance - random selection from tied group", [
                'tied_count' => $tiedLosers->count(),
                'tied_players' => $tiedLosers->pluck('name')->toArray(),
                'selected_player' => $selectedMetrics['name'],
                'tie_metrics' => [
                    'current_round_points' => $topMetrics['current_round_points'],
                    'total_all_time_points' => $topMetrics['total_all_time_points'],
                    'win_rate' => $topMetrics['win_rate']
                ]
            ]);
        } else {
            // Clear best performer
            $selectedMetrics = $topMetrics;
            \Log::info("Clear best performing loser selected", [
                'selected_player' => $selectedMetrics['name'],
                'metrics' => [
                    'current_round_points' => $selectedMetrics['current_round_points'],
                    'total_all_time_points' => $selectedMetrics['total_all_time_points'],
                    'win_rate' => $selectedMetrics['win_rate']
                ],
                'beat_count' => $sortedLosers->count() - 1
            ]);
        }
        
        return $selectedMetrics['player'];
    }

    /**
     * Get level name based on level and group ID
     */
    private function getLevelName(string $level, $groupId)
    {
        switch ($level) {
            case 'community':
                $community = \App\Models\Community::find($groupId);
                return $community ? $community->name : "Community {$groupId}";
            case 'county':
                $county = \App\Models\County::find($groupId);
                return $county ? $county->name : "County {$groupId}";
            case 'regional':
                $region = \App\Models\Region::find($groupId);
                return $region ? $region->name : "Region {$groupId}";
            case 'national':
                return 'National';
            case 'special':
                return 'Special Tournament';
            default:
                return $level ?? 'Unknown Level';
        }
    }

    /**
     * Handle special cases for 2, 3, and 4 player tournaments
     */
    private function handleSpecialCases(Tournament $tournament, Collection $players, string $level, $groupId, $levelName, ?string $roundName = null)
    {
        $playerCount = $players->count();
        
        // Debug: Log the level name received in handleSpecialCases
        \Log::info("handleSpecialCases received level_name = '{$levelName}' for group {$groupId}");
        
        switch ($playerCount) {
            case 1:
                // Single player - automatic winner
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $players->first()->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'position' => 1,
                    'points' => 1,
                ]);
                break;
                
            case 2:
                // Create final match
                $finalRoundName = $roundName ?? '2_final';
                PoolMatch::create([
                    'match_name' => $finalRoundName . '_match',
                    'player_1_id' => $players->first()->id,
                    'player_2_id' => $players->last()->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $finalRoundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                break;
                
            case 3:
                // Create semifinal with one bye
                $sfRoundName = $roundName ?? '3_SF';
                $shuffledPlayers = $players->shuffle();
                PoolMatch::create([
                    'match_name' => $sfRoundName . '_match',
                    'player_1_id' => $shuffledPlayers[0]->id,
                    'player_2_id' => $shuffledPlayers[1]->id,
                    'bye_player_id' => $shuffledPlayers[2]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $sfRoundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                break;
                
            case 4:
                // Create two first round matches
                $r1RoundName = $roundName ?? 'round_1';
                $shuffledPlayers = $players->shuffle();
                
                \Log::info("Creating 4-player matches", [
                    'round_name' => $r1RoundName,
                    'level_name' => $levelName,
                    'players' => $shuffledPlayers->map(function($p) {
                        return ['id' => $p->id, 'name' => $p->name];
                    })->toArray()
                ]);
                
                PoolMatch::create([
                    'match_name' => $r1RoundName . '_M1',
                    'player_1_id' => $shuffledPlayers[0]->id,
                    'player_2_id' => $shuffledPlayers[1]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $r1RoundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                
                PoolMatch::create([
                    'match_name' => $r1RoundName . '_M2',
                    'player_1_id' => $shuffledPlayers[2]->id,
                    'player_2_id' => $shuffledPlayers[3]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $r1RoundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                break;
        }
    }

    /**
     * Create standard matches for more than 4 players
     */
    private function createStandardMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, string $levelName)
    {
        \Log::info("=== CREATE STANDARD MATCHES START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'group_id' => $groupId,
            'round_name' => $roundName,
            'player_count' => $players->count(),
            'players' => $players->map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->toArray(),
            'will_use_smart_pairing' => ($level !== 'community' && $level !== 'special'),
            'will_use_random_pairing' => ($level === 'community' || $level === 'special')
        ]);
        
        if ($level !== 'community' && $level !== 'special') {
            \Log::info("Using smart pairing matches for level: {$level}");
            // For county/regional/national ALL rounds, use smart pairing with previous group tracking
            $this->createSmartPairingMatches($tournament, $players, $level, $groupId, $roundName, $levelName);
        } else {
            \Log::info("Using random matches for level: {$level}");
            // Random pairing with same-origin avoidance for community level
            $this->createRandomMatches($tournament, $players, $level, $groupId, $roundName, $levelName);
        }
        
        \Log::info("=== CREATE STANDARD MATCHES END ===");
    }

    /**
     * Create matches using smart pairing algorithm for next rounds
     */
    private function createSmartPairingMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, string $levelName)
    {
        \Log::info("=== CREATE SMART PAIRING MATCHES START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'round_name' => $roundName,
            'player_count' => $players->count(),
            'players' => $players->map(function($p) {
                return ['id' => $p->id, 'name' => $p->name];
            })->toArray()
        ]);
        
        // Create enhanced player objects with previous group info for smart pairing
        $enhancedPlayers = collect();
        foreach ($players as $player) {
            $previousGroup = $this->getPreviousGroupIdFromPlayer($player, $level);
            $enhancedPlayers->push((object)[
                'player' => $player,
                'winner' => (object)['position' => 1], // Default position for next rounds
                'previous_group' => $previousGroup
            ]);
        }
        
        // Use our smart pairing algorithm
        $pairedPlayers = $this->smartPairPlayers($enhancedPlayers, $level);
        $playerCount = count($pairedPlayers);
        
        // Handle odd number of players by having one player play twice
        $matchNumber = 1;
        if ($playerCount % 2 == 1 && $playerCount > 3) {
            \Log::info("INITIALIZATION: Odd number of players ({$playerCount}), one player will play twice", [
                'player_count' => $playerCount,
                'logic' => 'INITIALIZATION: One player plays twice for odd counts'
            ]);
            
            // Pick the first player to play twice (smart pairing already optimized the order)
            $doublePlayer = $pairedPlayers[0];
            
            // Create first match with double player
            $opponent1 = $pairedPlayers[1];
            $matchName = $roundName . '__match' . $matchNumber;
            $this->createMatch(
                $tournament,
                $doublePlayer,
                $opponent1,
                $roundName,
                $level,
                $groupId,
                $levelName,
                null,
                $matchName
            );
            $matchNumber++;
            
            // Create second match with double player and another opponent
            $opponent2 = $pairedPlayers[2];
            $matchName = $roundName . '__match' . $matchNumber;
            $this->createMatch(
                $tournament,
                $doublePlayer,
                $opponent2,
                $roundName,
                $level,
                $groupId,
                $levelName,
                null,
                $matchName
            );
            $matchNumber++;
            
            // Create matches for remaining paired players (skip first 3 players)
            for ($i = 3; $i < $playerCount - 1; $i += 2) {
                if (isset($pairedPlayers[$i + 1])) {
                    $matchName = $roundName . '__match' . $matchNumber;
                    $this->createMatch(
                        $tournament,
                        $pairedPlayers[$i],
                        $pairedPlayers[$i + 1],
                        $roundName,
                        $level,
                        $groupId,
                        $levelName,
                        null,
                        $matchName
                    );
                    $matchNumber++;
                }
            }
        } else {
            // Even number or special cases (≤3), create normal pairs
            for ($i = 0; $i < $playerCount - 1; $i += 2) {
                if (isset($pairedPlayers[$i + 1])) {
                    $matchName = $roundName . '__match' . $matchNumber;
                    $this->createMatch(
                        $tournament,
                        $pairedPlayers[$i],
                        $pairedPlayers[$i + 1],
                        $roundName,
                        $level,
                        $groupId,
                        $levelName,
                        null,
                        $matchName
                    );
                    $matchNumber++;
                }
            }
        }
        
        \Log::info("Created " . ($matchNumber - 1) . " smart pairing matches for {$level} level");
    }

    /**
     * Create matches based on player positions with cross-community avoidance
     */
    private function createPositionBasedMatchesForLevel(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName)
    {
        // Get winner records to know positions
        if ($level === 'special') {
            // For special tournaments, no previous level - all players start equal
            $winnerRecords = collect();
        } else {
            $winnerRecords = Winner::whereIn('player_id', $players->pluck('id'))
                ->where('tournament_id', $tournament->id)
                ->where('level', $this->getPreviousLevel($level))
                ->get()
                ->keyBy('player_id');
        }
        
        // Group by position and avoid same community pairings
        $position1Players = collect();
        $position2Players = collect();
        $position3Players = collect();
        
        foreach ($players as $player) {
            $position = $winnerRecords[$player->id]->position ?? 3;
            switch ($position) {
                case 1:
                    $position1Players->push($player);
                    break;
                case 2:
                    $position2Players->push($player);
                    break;
                case 3:
                    $position3Players->push($player);
                    break;
            }
        }
        
        // Create matches for each position group with community avoidance
        $levelName = $this->getLevelName($level, $groupId);
        $this->createCommunityAwareMatches($position1Players, $tournament, $level, $groupId, $roundName, 'pos1', $levelName);
        $this->createCommunityAwareMatches($position2Players, $tournament, $level, $groupId, $roundName, 'pos2', $levelName);
        $this->createCommunityAwareMatches($position3Players, $tournament, $level, $groupId, $roundName, 'pos3', $levelName);
        
        // Handle unpaired players across positions
        $unpaired = collect();
        if ($position1Players->count() % 2 == 1) $unpaired->push($position1Players->last());
        if ($position2Players->count() % 2 == 1) $unpaired->push($position2Players->last());
        if ($position3Players->count() % 2 == 1) $unpaired->push($position3Players->last());
        
        if ($unpaired->isNotEmpty()) {
            $levelName = $this->getLevelName($level, $groupId);
            $this->createCommunityAwareMatches($unpaired, $tournament, $level, $groupId, $roundName, 'cross', $levelName);
        }
    }

    /**
     * Create random matches with same-origin avoidance
     */
    private function createRandomMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, ?string $levelName = null)
    {
        \Log::info("=== CREATE RANDOM MATCHES START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'group_id' => $groupId,
            'round_name' => $roundName,
            'player_count' => $players->count()
        ]);
        
        if ($level === 'community') {
            \Log::info("Using pairPlayers for community level");
            // For community level, just pair randomly
            $levelName = $levelName ?? $this->getLevelName($level, $groupId);
            $this->pairPlayers($players, $tournament, $level, $groupId, $roundName, '', $levelName);
        } else {
            \Log::info("Using createCommunityAwareMatches for level: {$level}");
            // For higher levels, avoid same community pairings
            $levelName = $this->getLevelName($level, $groupId);
            $this->createCommunityAwareMatches($players, $tournament, $level, $groupId, $roundName, '', $levelName);
        }
        
        \Log::info("=== CREATE RANDOM MATCHES END ===");
    }

    /**
     * Pair players and create matches with proper bye handling
     */
    private function pairPlayers(Collection $players, Tournament $tournament, string $level, $groupId, string $roundName, string $suffix = '', ?string $levelName = null)
    {
        $shuffled = $players->shuffle();
        $matchNumber = 1;
        
        // Handle odd number of players by allowing one player to play twice
        if ($players->count() % 2 == 1) {
            // Pick a random player to play twice
            $doublePlayer = $shuffled->random();
            
            // Create first match with double player
            $opponent1 = $shuffled->where('id', '!=', $doublePlayer->id)->first();
            $matchName = $roundName . '_' . $suffix . '_match' . $matchNumber;
            PoolMatch::create([
                'match_name' => $matchName,
                'player_1_id' => $doublePlayer->id,
                'player_2_id' => $opponent1->id,
                'level' => $level,
                'level_name' => $levelName ?? $this->getLevelName($level, $groupId),
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
            ]);
            $matchNumber++;
            
            // Remove the first opponent from remaining players
            $remainingPlayers = $shuffled->where('id', '!=', $opponent1->id);
            
            // Create second match with double player and another opponent
            $opponent2 = $remainingPlayers->where('id', '!=', $doublePlayer->id)->first();
            $matchName = $roundName . '_' . $suffix . '_match' . $matchNumber;
            PoolMatch::create([
                'match_name' => $matchName,
                'player_1_id' => $doublePlayer->id,
                'player_2_id' => $opponent2->id,
                'level' => $level,
                'level_name' => $levelName ?? $this->getLevelName($level, $groupId),
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
            ]);
            $matchNumber++;
            
            // Remove double player and second opponent from remaining players
            $remainingPlayers = $remainingPlayers->where('id', '!=', $doublePlayer->id)->where('id', '!=', $opponent2->id);
        } else {
            $remainingPlayers = $shuffled;
        }
        
        // Create matches for remaining paired players
        $remainingArray = $remainingPlayers->values();
        for ($i = 0; $i < $remainingArray->count() - 1; $i += 2) {
            $matchName = $roundName . '_' . $suffix . '_match' . $matchNumber;
            PoolMatch::create([
                'match_name' => $matchName,
                'player_1_id' => $remainingArray[$i]->id,
                'player_2_id' => $remainingArray[$i + 1]->id,
                'level' => $level,
                'level_name' => $levelName ?? $this->getLevelName($level, $groupId),
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
            ]);
            $matchNumber++;
        }
    }
    
    /**
     * Handle odd number of players by promoting a loser or random pairing
     */
    private function handleOddPlayers(Collection $players, Tournament $tournament, string $level, $groupId, string $roundName)
    {
        if ($roundName === 'round_1') {
            // Initial round: random pairing with unpaired player
            return $this->handleInitialOddPlayers($players, $tournament, $level, $groupId);
        } else {
            // Progression round: promote a random loser
            return $this->promoteRandomLoser($players, $tournament, $level, $groupId, $roundName);
        }
    }
    
    /**
     * Handle odd players at initialization by random pairing
     */
    private function handleInitialOddPlayers(Collection $players, Tournament $tournament, string $level, $groupId)
    {
        // Example: 21 players -> pair 20, then pick random from paired to play with unpaired
        $playerCount = $players->count();
        $unpairedPlayer = $players->pop(); // Remove last player
        
        // Pair the remaining even number of players
        $pairedPlayers = $players->chunk(2);
        
        // Pick a random pair and add the unpaired player to one of them
        $randomPair = $pairedPlayers->random();
        $randomPlayerFromPair = $randomPair->random();
        
        // Remove the selected player from their pair and add unpaired player
        $updatedPlayers = collect();
        
        foreach ($pairedPlayers as $pair) {
            if ($pair->contains($randomPlayerFromPair)) {
                // Replace the selected player with unpaired player
                $updatedPlayers = $updatedPlayers->merge($pair->reject(function($p) use ($randomPlayerFromPair) {
                    return $p->id === $randomPlayerFromPair->id;
                }));
                $updatedPlayers->push($unpairedPlayer);
            } else {
                $updatedPlayers = $updatedPlayers->merge($pair);
            }
        }
        
        // Add the displaced player back
        $updatedPlayers->push($randomPlayerFromPair);
        
        return $updatedPlayers;
    }
    
    /**
     * Promote a random loser to make even number of winners
     */
    private function promoteRandomLoser(Collection $winners, Tournament $tournament, string $level, $groupId, string $roundName)
    {
        // Get previous round matches to find losers
        $previousRoundName = $this->getPreviousRoundName($roundName);
        $previousMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', $previousRoundName)
            ->where('status', 'completed')
            ->get();
        
        // Collect losers
        $losers = collect();
        foreach ($previousMatches as $match) {
            if ($match->winner_id) {
                $loserId = ($match->player_1_id === $match->winner_id) ? $match->player_2_id : $match->player_1_id;
                if ($loserId) {
                    $loser = User::find($loserId);
                    if ($loser) $losers->push($loser);
                }
            }
        }
        
        // Pick best performing loser and add to winners
        if ($losers->isNotEmpty()) {
            $promotedLoser = $this->selectBestLoser($losers, $tournament, $level, $groupId, $previousMatches);
            $winners->push($promotedLoser);
        }
        
        return $winners;
    }

    /**
     * Get previous round name for progression
     */
    private function getPreviousRoundName(string $currentRound): string
    {
        // Handle special round names
        if (str_contains($currentRound, '_')) {
            $parts = explode('_', $currentRound);
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                $roundNumber = (int)$parts[1];
                if ($roundNumber > 1) {
                    return $parts[0] . '_' . ($roundNumber - 1);
                }
            }
        }
        
        // Default fallback
        return 'round_1';
    }

    /**
     * Sort players to avoid same origin pairing
     */
    private function sortPlayersToAvoidSameOrigin(Collection $players, string $level): Collection
    {
        $originField = match($level) {
            'county' => 'community_id',
            'regional' => 'county_id',
            'national' => 'region_id',
            default => null
        };
        
        if (!$originField) {
            return $players;
        }
        
        // Sort by origin, then shuffle within each origin group
        return $players->sortBy($originField)
            ->groupBy($originField)
            ->map(function ($group) {
                return $group->shuffle();
            })
            ->flatten();
    }

    /**
     * Check if round is completed
     */
    private function isRoundCompleted(Tournament $tournament, string $level, ?int $groupId): bool
    {
        $query = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level);
            
        if ($groupId !== null) {
            $query->where('group_id', $groupId);
        }
        
        // Get the latest round
        $latestRound = $query->orderBy('created_at', 'desc')->first();
        if (!$latestRound) {
            return true; // No matches exist, can start new round
        }
        
        // Check if all matches in this round are completed
        return $query->where('round_name', $latestRound->round_name)
            ->whereNotIn('status', ['completed', 'forfeit'])
            ->count() === 0;
    }

    /**
     * Get current round matches
     */
    private function getCurrentRoundMatches(Tournament $tournament, string $level, ?int $groupId): Collection
    {
        $query = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level);
            
        if ($groupId !== null) {
            $query->where('group_id', $groupId);
        }
        
        // Get all distinct round names and find the highest round number
        $allRounds = $query->distinct('round_name')->pluck('round_name');
        
        $latestRoundName = $allRounds->sortByDesc(function($roundName) {
            if (str_contains($roundName, 'round_')) {
                return (int) str_replace('round_', '', $roundName);
            }
            // Handle other round naming patterns
            return match($roundName) {
                'quarter_final' => 100,
                'semi_final' => 200,
                'final' => 300,
                default => 0
            };
        })->first();
        
        \Log::info("Determined current round for progression", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'group_id' => $groupId,
            'all_rounds' => $allRounds->toArray(),
            'selected_round' => $latestRoundName
        ]);
        
        return $query->where('round_name', $latestRoundName)->get();
    }

    /**
     * Get winners from matches
     */
    private function getWinnersFromMatches(Collection $matches): Collection
    {
        return User::whereIn('id', $matches->pluck('winner_id')->filter())->get();
    }

    /**
     * Get next round name
     */
    private function getNextRoundName(string $currentRound): string
    {
        if (str_contains($currentRound, 'round_')) {
            $number = (int) str_replace('round_', '', $currentRound);
            return 'round_' . ($number + 1);
        }
        
        return match($currentRound) {
            'quarter_final' => 'semi_final',
            'semi_final' => 'final',
            default => 'round_2'
        };
    }

    /**
     * Get previous level
     */
    private function getPreviousLevel(string $level): string
    {
        return match($level) {
            'county' => 'community',
            'regional' => 'county',
            'national' => 'regional',
            'special' => throw new \Exception("Special tournaments don't have previous levels - use approved players directly"),
            default => throw new \Exception("No previous level for {$level}")
        };
    }

    /**
     * Send pairing notifications - consolidated approach to prevent multiple notifications per player
     */
    private function sendPairingNotifications(Tournament $tournament, string $level)
    {
        Log::info('sendPairingNotifications called - CONSOLIDATED VERSION', [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'timestamp' => now()
        ]);
        
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('status', 'pending')
            ->with(['player1', 'player2'])
            ->get();
            
        Log::info('Found matches for pairing notifications', [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'match_count' => $matches->count()
        ]);
        
        // Collect all players and their matches to send consolidated notifications
        $playerMatches = [];
        
        foreach ($matches as $match) {
            // Propose available days for the match
            $this->proposeMatchDays($match);
            
            // Collect matches for each player
            if ($match->player1) {
                if (!isset($playerMatches[$match->player1->id])) {
                    $playerMatches[$match->player1->id] = [
                        'player' => $match->player1,
                        'matches' => []
                    ];
                }
                $playerMatches[$match->player1->id]['matches'][] = $match;
            }
            
            if ($match->player2) {
                if (!isset($playerMatches[$match->player2->id])) {
                    $playerMatches[$match->player2->id] = [
                        'player' => $match->player2,
                        'matches' => []
                    ];
                }
                $playerMatches[$match->player2->id]['matches'][] = $match;
            }
            
            // Fire event for real-time notifications
            event(new MatchPairingCreated($match, "New match pairing created"));
        }
        
        // Send one consolidated notification per player
        foreach ($playerMatches as $playerId => $playerData) {
            $player = $playerData['player'];
            $playerMatchesData = $playerData['matches'];
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
                
                Log::info('Creating consolidated pairing notification', [
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
                        'level' => $level,
                        'match_count' => $matchCount,
                        'match_ids' => collect($playerMatchesData)->pluck('id')->toArray()
                    ]
                ]);
            } else {
                Log::info('Skipping duplicate pairing notification (player already notified for this tournament level)', [
                    'player_id' => $playerId,
                    'tournament_id' => $tournament->id,
                    'level' => $level
                ]);
            }
        }
    }

    /**
     * Propose available days for a match
     */
    private function proposeMatchDays(PoolMatch $match)
    {
        // Propose next 7 days (excluding today) as available options
        $proposedDates = [];
        for ($i = 1; $i <= 7; $i++) {
            $date = now()->addDays($i);
            $proposedDates[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'available' => true
            ];
        }
        
        $match->update(['proposed_dates' => $proposedDates]);
    }

    /**
     * Process player availability selection
     */
    public function selectPlayerAvailability(int $matchId, int $playerId, array $selectedDates): array
    {
        $match = PoolMatch::findOrFail($matchId);
        
        // Validate player is part of this match
        if ($match->player_1_id !== $playerId && $match->player_2_id !== $playerId) {
            throw new \Exception("Player not part of this match");
        }
        
        // Update player's preferred dates (Laravel will auto-cast to JSON)
        if ($match->player_1_id === $playerId) {
            $match->update(['player_1_preferred_dates' => $selectedDates]);
        } else {
            $match->update(['player_2_preferred_dates' => $selectedDates]);
        }
        
        // Check if both players have selected their dates
        if ($match->player_1_preferred_dates && $match->player_2_preferred_dates) {
            return $this->findMatchingDate($match);
        }
        
        return ['status' => 'waiting', 'message' => 'Waiting for other player to select dates'];
    }

    /**
     * Find matching date between players
     */
    private function findMatchingDate(PoolMatch $match): array
    {
        $player1Dates = $match->player_1_preferred_dates;
        $player2Dates = $match->player_2_preferred_dates;
        
        // Find common dates
        $commonDates = array_intersect($player1Dates, $player2Dates);
        
        if (!empty($commonDates)) {
            // Schedule match on first common date
            $scheduledDate = reset($commonDates);
            $match->update([
                'scheduled_date' => $scheduledDate,
                'status' => 'scheduled'
            ]);
            
            // Notify both players
            $this->notifyPlayersOfScheduledMatch($match, $scheduledDate);
            
            return [
                'status' => 'scheduled',
                'scheduled_date' => $scheduledDate,
                'message' => 'Match scheduled successfully'
            ];
        } else {
            // No common dates, need admin intervention or rescheduling
            $match->update(['status' => 'scheduling_conflict']);
            
            return [
                'status' => 'conflict',
                'message' => 'No matching dates found. Admin intervention required.'
            ];
        }
    }

    /**
     * Notify players of scheduled match
     */
    private function notifyPlayersOfScheduledMatch(PoolMatch $match, string $scheduledDate)
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
    }

    /**
     * Check if level is completed and notify admin
     */
    public function checkLevelCompletion(int $tournamentId, string $level, ?int $groupId = null): array
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        // Check if all matches are completed
        $query = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level);
            
        if ($groupId !== null) {
            $query->where('group_id', $groupId);
        }
        
        $pendingCount = $query->whereNotIn('status', ['completed', 'forfeit'])->count();
        
        if ($pendingCount === 0) {
            // All matches completed, determine winners
            $this->determineWinners($tournament, $level, $groupId);
            
            // Create admin notification
            $groupName = $this->getGroupName($level, $groupId);
            $message = "All matches for {$groupName} at {$level} level have been completed in {$tournament->name}";
            
            // Here you would send notification to admin
            // For now, return the status
            return [
                'completed' => true,
                'message' => $message,
                'level' => $level,
                'group_id' => $groupId,
                'pending_matches' => 0
            ];
        }
        
        return [
            'completed' => false,
            'pending_matches' => $pendingCount,
            'level' => $level,
            'group_id' => $groupId
        ];
    }

    /**
     * Determine and record winners
     */
    private function determineWinners(Tournament $tournament, string $level, ?int $groupId)
    {
        $playerCount = $this->getTotalPlayersInTournament($tournament, $level, $groupId);
        
        // Debug output
        Log::info("Determining winners for tournament {$tournament->id}, level {$level}, group {$groupId}, player count: {$playerCount}");
        
        // Clear existing winners for this group
        Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->delete();
        
        switch ($playerCount) {
            case 1:
                // Single player automatically wins
                $player = $this->getOriginalPlayersForTournament($tournament, $level, $groupId)->first();
                Winner::create([
                    'player_id' => $player->id,
                    'position' => 1,
                    'level' => $level,
                    'level_id' => $groupId,
                    'tournament_id' => $tournament->id,
                ]);
                break;
                
            case 2:
                $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('group_id', $groupId)
                    ->where('round_name', '2_final')
                    ->where('status', 'completed')
                    ->first();
                
                if ($finalMatch) {
                    // Position 1: winner, Position 2: loser
                    Winner::create([
                        'player_id' => $finalMatch->winner_id,
                        'position' => 1,
                        'level' => $level,
                        'level_id' => $groupId,
                        'tournament_id' => $tournament->id,
                    ]);
                    
                    $loser = $finalMatch->winner_id == $finalMatch->player_1_id 
                        ? $finalMatch->player_2_id 
                        : $finalMatch->player_1_id;
                        
                    Winner::create([
                        'player_id' => $loser,
                        'position' => 2,
                        'level' => $level,
                        'level_id' => $groupId,
                        'tournament_id' => $tournament->id,
                    ]);
                }
                break;
                
            case 3:
                $this->determine3PlayerWinnersRobust($tournament, $level, $groupId);
                break;
                
            case 4:
                $this->determine4PlayerWinners($tournament, $level, $groupId);
                break;
                
            default:
                // Standard tournament - determine winners from final matches
                $this->determineStandardWinners($tournament, $level, $groupId);
                break;
        }
        
        // Check for losers tournament completion
        $this->checkLosersTournamentCompletion($tournament, $level, $groupId);
    }
    
    /**
     * Determine winners for 3-player tournament - LEGACY METHOD (keeping for compatibility)
     */
    private function determine3PlayerWinners_Legacy(Tournament $tournament, string $level, ?int $groupId)
    {
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
        
        // Handle case with only SF match (no final match created)
        if ($sfMatch && !$finalMatch) {
            // Position 1: SF winner (automatically champion)
            Winner::create([
                'player_id' => $sfMatch->winner_id,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 2: SF loser
            $sfLoser = $sfMatch->winner_id == $sfMatch->player_1_id 
                ? $sfMatch->player_2_id 
                : $sfMatch->player_1_id;
                
            Winner::create([
                'player_id' => $sfLoser,
                'position' => 2,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 3: The bye player (not in SF match)
            $allPlayers = $this->getOriginalPlayersForTournament($tournament, $level, $groupId);
            $byePlayer = $allPlayers->whereNotIn('id', [$sfMatch->player_1_id, $sfMatch->player_2_id])->first();
            
            if ($byePlayer) {
                Winner::create([
                    'player_id' => $byePlayer->id,
                    'position' => 3,
                    'level' => $level,
                    'level_id' => $groupId,
                    'tournament_id' => $tournament->id,
                ]);
            }
            
            return;
        }
        
        // Check for tie-breaker match
        $tieBreakerMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_break_tie_final')
            ->where('status', 'completed')
            ->first();
        
        if ($sfMatch && $finalMatch && $tieBreakerMatch) {
            // Tie-breaker scenario: Bye player beat SF loser, then played SF winner
            $sfWinner = $sfMatch->winner_id;
            $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
            $finalWinner = $finalMatch->winner_id; // This is the bye player who won
            $tieBreakerWinner = $tieBreakerMatch->winner_id;
            
            // Position 1: Tie-breaker winner
            Winner::create([
                'player_id' => $tieBreakerWinner,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 2: Tie-breaker loser
            $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) ? $tieBreakerMatch->player_2_id : $tieBreakerMatch->player_1_id;
            Winner::create([
                'player_id' => $tieBreakerLoser,
                'position' => 2,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 3: SF loser (who lost the final and is now in 3rd place)
            Winner::create([
                'player_id' => $sfLoser,
                'position' => 3,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
        } elseif ($sfMatch && $finalMatch) {
            // Standard scenario: Check who won the final
            $sfWinner = $sfMatch->winner_id;
            $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
            $finalWinner = $finalMatch->winner_id;
            $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;
            
            if ($finalWinner !== $sfLoser) {
                // Bye player won the final - this should trigger tie-breaker, but if not completed yet, don't assign positions
                return;
            } else {
                // SF loser won the final - Check for Fair_Chance match
                $fairChanceMatch = PoolMatch::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('group_id', $groupId)
                    ->where('round_name', 'Fair_Chance')
                    ->where('status', 'completed')
                    ->first();
                
                if ($fairChanceMatch) {
                    // Fair chance match completed - determine positions based on result
                    $this->handleFairChanceResult($tournament, $level, $groupId, $sfMatch, $finalMatch, $fairChanceMatch);
                } else {
                    // No fair chance match yet - create one between SF winner and bye player (final loser)
                    \Log::info("Creating Fair_Chance match between SF winner and bye player", [
                        'sf_winner' => $sfWinner,
                        'bye_player' => $finalLoser,
                        'tournament_id' => $tournament->id
                    ]);
                    
                    PoolMatch::create([
                        'match_name' => 'Fair_Chance_match',
                        'player_1_id' => $sfWinner,
                        'player_2_id' => $finalLoser, // This is the bye player who lost final
                        'level' => $level,
                        'level_name' => $this->getLevelName($level, $groupId),
                        'round_name' => 'Fair_Chance',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    
                    return; // Wait for fair chance match to complete
                }
            }
        }
        
        // Check if we need losers tournament for additional positions (4-6)
        $this->checkAndCreateLosersTournament($tournament, $level, $groupId);
    }

    /**
     * Robust 3-player tournament handler with comprehensive subcase logic
     */
    private function determine3PlayerWinnersRobust(Tournament $tournament, string $level, ?int $groupId)
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
            $losersPositions = $this->handle3PlayerLosersTournament($tournament, $level, $groupId, $winnersNeeded);
            
            // Assign positions systematically: losers first (4-6), then winners (1-3)
            $this->assign3PlayerPositionsSystematic($tournament, $level, $groupId, $winnersPositions, $losersPositions, $winnersNeeded);
        } else {
            // Only need 3 winners - assign positions 1-3 from winners tournament
            $this->assign3PlayerWinnersOnly($tournament, $level, $groupId, $winnersPositions);
        }
        
        \Log::info("=== ROBUST 3-PLAYER TOURNAMENT HANDLER COMPLETE ===");
    }

    /**
     * Handle 3-player winners tournament (A, B, C) with all subcases
     */
    private function handle3PlayerWinnersTournament(Tournament $tournament, string $level, ?int $groupId)
    {
        \Log::info("Handling 3-player winners tournament", [
            'tournament_id' => $tournament->id
        ]);
        
        // Get matches - use new naming convention
        $sfMatch = $this->getMatch($tournament, $level, $groupId, '3_winners_SF');
        $finalMatch = $this->getMatch($tournament, $level, $groupId, '3_winners_final');
        $tieBreakerMatch = $this->getMatch($tournament, $level, $groupId, '3_winners_tie_breaker');
        $fairChanceMatch = $this->getMatch($tournament, $level, $groupId, '3_winners_fair_chance');
        
        // Determine current state and handle accordingly
        if (!$sfMatch || $sfMatch->status !== 'completed') {
            \Log::info("Semifinal not completed yet");
            return null;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $this->getByePlayer($tournament, $level, $groupId, [$sfMatch->player_1_id, $sfMatch->player_2_id]);
        
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
    private function handle3PlayerSubcases(Tournament $tournament, string $level, ?int $groupId, 
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
        
        $finalWinner = $finalMatch->winner_id;
        $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;
        
        \Log::info("Final match completed", [
            'final_winner' => $finalWinner,
            'final_loser' => $finalLoser,
            'bye_player_won' => $finalWinner === $byePlayer->id
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
    private function handle3PlayerSubcase1a(Tournament $tournament, string $level, ?int $groupId, 
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
        
        $tieBreakerWinner = $tieBreakerMatch->winner_id;
        $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) ? 
                          $tieBreakerMatch->player_2_id : $tieBreakerMatch->player_1_id;
        
        // Create Winner records for positions 1, 2, 3
        $positions = [
            1 => $tieBreakerWinner,
            2 => $tieBreakerLoser, 
            3 => $sfLoser
        ];
        
        $this->createWinnerRecords($tournament, $level, $positions, 'winners');
        
        \Log::info("Subcase 1a completed - positions assigned", [
            'position_1' => $tieBreakerWinner,
            'position_2' => $tieBreakerLoser,
            'position_3' => $sfLoser
        ]);
        
        return $positions;
    }

    /**
     * Handle subcase 1b: Bye player (C) loses final - need fair chance with SF winner (A)
     */
    private function handle3PlayerSubcase1b(Tournament $tournament, string $level, ?int $groupId,
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
        
        $fairChanceWinner = $fairChanceMatch->winner_id;
        $fairChanceLoser = ($fairChanceMatch->player_1_id === $fairChanceWinner) ? 
                          $fairChanceMatch->player_2_id : $fairChanceMatch->player_1_id;
        
        // If C loses fair chance: A pos 1, B pos 2, C pos 3
        if ($fairChanceLoser === $byePlayer->id) {
            $positions = [
                1 => $sfWinner,
                2 => $sfLoser,
                3 => $byePlayer->id
            ];
            
            $this->createWinnerRecords($tournament, $level, $positions, 'winners');
            
            \Log::info("Subcase 1b completed - bye player lost fair chance", [
                'position_1' => $sfWinner,
                'position_2' => $sfLoser,
                'position_3' => $byePlayer->id
            ]);
            
            return $positions;
        }
        
        // If C wins fair chance: A, B, C all have 1 win 1 loss - need tie breaking
        return $this->handle3PlayerTripleTie($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer->id);
    }

    /**
     * Handle triple tie scenario using metrics
     */
    private function handle3PlayerTripleTie(Tournament $tournament, string $level, ?int $groupId, $playerA, $playerB, $playerC)
    {
        \Log::info("Handling triple tie scenario - using metrics", [
            'players' => [$playerA, $playerB, $playerC]
        ]);
        
        // Calculate metrics for tie breaking
        $players = [$playerA, $playerB, $playerC];
        $playerMetrics = [];
        
        foreach ($players as $playerId) {
            $metrics = $this->calculatePlayerMetrics($tournament, $level, $groupId, $playerId);
            $playerMetrics[$playerId] = $metrics;
        }
        
        // Sort by win rate first, then total points
        uasort($playerMetrics, function($a, $b) {
            if ($a['win_rate'] != $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate']; // Higher win rate first
            }
            return $b['total_points'] <=> $a['total_points']; // Higher points first
        });
        
        $sortedPlayers = array_keys($playerMetrics);
        
        // Check for ties in rankings
        $tieInfo = $this->analyzeTieBreaking($playerMetrics);
        
        $positions = [
            1 => $sortedPlayers[0],
            2 => $sortedPlayers[1],
            3 => $sortedPlayers[2]
        ];
        
        $this->createWinnerRecords($tournament, $level, $positions, 'winners', $tieInfo);
        
        \Log::info("Triple tie resolved using metrics", [
            'positions' => $positions,
            'tie_info' => $tieInfo
        ]);
        
        return $positions;
    }

    /**
     * Calculate player metrics for tie breaking using global statistics
     */
    private function calculatePlayerMetrics(Tournament $tournament, string $level, ?int $groupId, $playerId)
    {
        // Get ALL matches this player has ever played (global statistics)
        $matches = PoolMatch::where(function($q) use ($playerId) {
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
        
        \Log::info("Global player metrics calculated", [
            'player_id' => $playerId,
            'win_rate' => $winRate,
            'total_points' => $totalPoints,
            'wins' => $wins,
            'total_matches' => $totalMatches
        ]);
        
        return [
            'player_id' => $playerId,
            'total_points' => $totalPoints,
            'wins' => $wins,
            'total_matches' => $totalMatches,
            'win_rate' => $winRate
        ];
    }

    /**
     * Analyze tie breaking to determine tie information - simplified approach
     */
    private function analyzeTieBreaking($playerMetrics)
    {
        $players = array_keys($playerMetrics);
        $metrics = array_values($playerMetrics);
        $tieGroups = [];
        
        // Check if all three have same metrics
        if ($metrics[0]['win_rate'] == $metrics[1]['win_rate'] && 
            $metrics[0]['total_points'] == $metrics[1]['total_points'] &&
            $metrics[1]['win_rate'] == $metrics[2]['win_rate'] && 
            $metrics[1]['total_points'] == $metrics[2]['total_points']) {
            // All three tied - they all get the same position
            $tieGroups[] = [
                'players' => [$players[0], $players[1], $players[2]],
                'type' => 'triple_tie'
            ];
        } elseif ($metrics[0]['win_rate'] == $metrics[1]['win_rate'] && 
                  $metrics[0]['total_points'] == $metrics[1]['total_points']) {
            // First two tied
            $tieGroups[] = [
                'players' => [$players[0], $players[1]],
                'type' => 'tie'
            ];
        } elseif ($metrics[1]['win_rate'] == $metrics[2]['win_rate'] && 
                  $metrics[1]['total_points'] == $metrics[2]['total_points']) {
            // Last two tied
            $tieGroups[] = [
                'players' => [$players[1], $players[2]],
                'type' => 'tie'
            ];
        }
        
        return [
            'groups' => $tieGroups,
            'has_ties' => !empty($tieGroups)
        ];
    }

    /**
     * Create Winner records in database with proper tie handling
     */
    private function createWinnerRecords(Tournament $tournament, string $level, array $positions, string $type = 'winners', array $tieInfo = [])
    {
        $basePosition = $type === 'winners' ? 1 : 4;
        
        // Calculate actual positions with tie handling
        $actualPositions = $this->calculateActualPositions($positions, $basePosition, $tieInfo);
        
        foreach ($actualPositions as $playerId => $actualPosition) {
            // Check if winner record already exists
            $existingWinner = Winner::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('player_id', $playerId)
                ->first();
                
            if (!$existingWinner) {
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'player_id' => $playerId,
                    'position' => $actualPosition,
                    'prize_amount' => $this->calculatePrizeAmount($tournament, $actualPosition),
                    'tie_info' => !empty($tieInfo) ? json_encode($tieInfo) : null
                ]);
                
                \Log::info("Created winner record", [
                    'player_id' => $playerId,
                    'position' => $actualPosition,
                    'type' => $type,
                    'tie_info' => $tieInfo
                ]);
            }
        }
        
        // Send notifications to winners
        $this->sendWinnerNotifications($tournament, $level, $actualPositions, $tieInfo);
    }

    /**
     * Calculate actual positions with proper tie handling
     */
    private function calculateActualPositions(array $positions, int $basePosition, array $tieInfo)
    {
        $actualPositions = [];
        
        if (empty($tieInfo)) {
            // No ties - simple sequential positions
            foreach ($positions as $pos => $playerId) {
                $actualPositions[$playerId] = $basePosition + $pos - 1;
            }
        } else {
            // Handle ties - same position for tied players
            $currentPosition = $basePosition;
            $positionGroups = $this->groupPlayersByTie($positions, $tieInfo);
            
            foreach ($positionGroups as $group) {
                foreach ($group['players'] as $playerId) {
                    $actualPositions[$playerId] = $currentPosition;
                }
                // Next position skips by the number of tied players
                $currentPosition += count($group['players']);
            }
        }
        
        \Log::info("Calculated actual positions with ties", [
            'original_positions' => $positions,
            'actual_positions' => $actualPositions,
            'tie_info' => $tieInfo
        ]);
        
        return $actualPositions;
    }

    /**
     * Group players by their tie status
     */
    private function groupPlayersByTie(array $positions, array $tieInfo)
    {
        $groups = [];
        $processedPlayers = [];
        
        // Handle tied groups first
        if (isset($tieInfo['groups'])) {
            foreach ($tieInfo['groups'] as $group) {
                $groups[] = [
                    'players' => $group['players'],
                    'tied' => true
                ];
                $processedPlayers = array_merge($processedPlayers, $group['players']);
            }
        }
        
        // Add non-tied players
        foreach ($positions as $pos => $playerId) {
            if (!in_array($playerId, $processedPlayers)) {
                $groups[] = [
                    'players' => [$playerId],
                    'tied' => false
                ];
            }
        }
        
        return $groups;
    }

    /**
     * Calculate prize amount for position
     */
    private function calculatePrizeAmount(Tournament $tournament, int $position)
    {
        // Basic prize calculation - can be enhanced based on tournament rules
        $basePrize = $tournament->prize_pool ?? 0;
        
        switch ($position) {
            case 1: return $basePrize * 0.5;
            case 2: return $basePrize * 0.3;
            case 3: return $basePrize * 0.2;
            default: return 0;
        }
    }

    /**
     * Send notifications to winners with proper tie messaging
     */
    private function sendWinnerNotifications(Tournament $tournament, string $level, array $actualPositions, array $tieInfo)
    {
        foreach ($actualPositions as $playerId => $actualPosition) {
            $message = "Congratulations! You finished in position {$actualPosition}";
            
            // Add tie information to message
            if (!empty($tieInfo) && isset($tieInfo['groups'])) {
                foreach ($tieInfo['groups'] as $group) {
                    if (in_array($playerId, $group['players'])) {
                        $tiedPlayerCount = count($group['players']);
                        if ($group['type'] === 'triple_tie') {
                            $message .= " (tied with " . ($tiedPlayerCount - 1) . " other players)";
                        } else {
                            $message .= " (tied with " . ($tiedPlayerCount - 1) . " other player" . ($tiedPlayerCount > 2 ? 's' : '') . ")";
                        }
                        break;
                    }
                }
            }
            
            $message .= " in {$tournament->name} ({$level} level)";
            
            // Create notification
            Notification::create([
                'user_id' => $playerId,
                'type' => 'tournament_position',
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'level' => $level,
                    'position' => $actualPosition,
                    'tie_info' => $tieInfo,
                    'message' => $message
                ]
            ]);
            
            \Log::info("Sent winner notification", [
                'player_id' => $playerId,
                'position' => $actualPosition,
                'message' => $message,
                'tie_info' => $tieInfo
            ]);
        }
    }

    /**
     * Handle 3-player losers tournament (D, E, F) with all subcases
     */
    private function handle3PlayerLosersTournament(Tournament $tournament, string $level, ?int $groupId, int $winnersNeeded)
    {
        \Log::info("Handling 3-player losers tournament", [
            'tournament_id' => $tournament->id,
            'winners_needed' => $winnersNeeded
        ]);
        
        // Get matches - use losers naming convention
        $sfMatch = $this->getMatch($tournament, $level, $groupId, 'losers_3_SF');
        $finalMatch = $this->getMatch($tournament, $level, $groupId, 'losers_3_final');
        $tieBreakerMatch = $this->getMatch($tournament, $level, $groupId, 'losers_3_tie_breaker');
        $fairChanceMatch = $this->getMatch($tournament, $level, $groupId, 'losers_3_fair_chance');
        
        // Determine current state and handle accordingly
        if (!$sfMatch || $sfMatch->status !== 'completed') {
            \Log::info("Losers semifinal not completed yet");
            return null;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        \Log::info("3-player losers tournament state", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser,
            'bye_player' => $byePlayer
        ]);
        
        // Handle different subcases based on match completion
        return $this->handle3PlayerLosersSubcases($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, 
                                                $finalMatch, $tieBreakerMatch, $fairChanceMatch, $winnersNeeded);
    }

    /**
     * Handle all 3-player losers tournament subcases
     */
    private function handle3PlayerLosersSubcases(Tournament $tournament, string $level, ?int $groupId, 
                                               $sfWinner, $sfLoser, $byePlayer, $finalMatch, $tieBreakerMatch, $fairChanceMatch, $winnersNeeded)
    {
        // Case 1: D plays E, F gets bye. Loser of SF (E) plays with F in final
        if (!$finalMatch || $finalMatch->status !== 'completed') {
            return null; // Wait for final to complete
        }
        
        $finalWinner = $finalMatch->winner_id;
        $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;
        
        \Log::info("Losers final match completed", [
            'final_winner' => $finalWinner,
            'final_loser' => $finalLoser,
            'bye_player_won' => $finalWinner === $byePlayer
        ]);
        
        // Subcase 1a: F (bye player) wins final
        if ($finalWinner === $byePlayer) {
            return $this->handle3PlayerLosersSubcase1a($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, $tieBreakerMatch, $winnersNeeded);
        }
        
        // Subcase 1b: F (bye player) loses final
        return $this->handle3PlayerLosersSubcase1b($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer, $fairChanceMatch, $winnersNeeded);
    }

    /**
     * Handle losers subcase 1a: Bye player (F) wins final - need tie breaker with SF winner (D)
     */
    private function handle3PlayerLosersSubcase1a(Tournament $tournament, string $level, ?int $groupId, 
                                                $sfWinner, $sfLoser, $byePlayer, $tieBreakerMatch, $winnersNeeded)
    {
        \Log::info("Losers Subcase 1a: Bye player won final - tie breaker needed");
        
        // F wins final, F plays with D in tie breaker
        if (!$tieBreakerMatch || $tieBreakerMatch->status !== 'completed') {
            return null; // Wait for tie breaker to complete
        }
        
        $tieBreakerWinner = $tieBreakerMatch->winner_id;
        $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) ? 
                          $tieBreakerMatch->player_2_id : $tieBreakerMatch->player_1_id;
        
        // Create Winner records for positions 4, 5, 6
        $positions = [
            1 => $tieBreakerWinner,  // Position 4 (base + 1 - 1 = 4)
            2 => $tieBreakerLoser,   // Position 5
            3 => $sfLoser           // Position 6
        ];
        
        $this->createWinnerRecords($tournament, $level, $positions, 'losers');
        
        \Log::info("Losers Subcase 1a completed - positions assigned", [
            'position_4' => $tieBreakerWinner,
            'position_5' => $tieBreakerLoser,
            'position_6' => $sfLoser
        ]);
        
        return $positions;
    }

    /**
     * Handle losers subcase 1b: Bye player (F) loses final - need fair chance with SF winner (D)
     */
    private function handle3PlayerLosersSubcase1b(Tournament $tournament, string $level, ?int $groupId,
                                                $sfWinner, $sfLoser, $byePlayer, $fairChanceMatch, $winnersNeeded)
    {
        \Log::info("Losers Subcase 1b: Bye player lost final - fair chance needed");
        
        // F loses final, F plays with D in fair chance
        if (!$fairChanceMatch || $fairChanceMatch->status !== 'completed') {
            return null; // Wait for fair chance to complete
        }
        
        $fairChanceWinner = $fairChanceMatch->winner_id;
        $fairChanceLoser = ($fairChanceMatch->player_1_id === $fairChanceWinner) ? 
                          $fairChanceMatch->player_2_id : $fairChanceMatch->player_1_id;
        
        // If F loses fair chance: D pos 4, E pos 5, F pos 6
        if ($fairChanceLoser === $byePlayer) {
            $positions = [
                1 => $sfWinner,      // Position 4
                2 => $sfLoser,       // Position 5
                3 => $byePlayer      // Position 6
            ];
            
            $this->createWinnerRecords($tournament, $level, $positions, 'losers');
            
            \Log::info("Losers Subcase 1b completed - bye player lost fair chance", [
                'position_4' => $sfWinner,
                'position_5' => $sfLoser,
                'position_6' => $byePlayer
            ]);
            
            return $positions;
        }
        
        // If F wins fair chance: D, E, F all have 1 win 1 loss - need tie breaking
        return $this->handle3PlayerLosersTripleTie($tournament, $level, $groupId, $sfWinner, $sfLoser, $byePlayer);
    }

    /**
     * Handle losers triple tie scenario using metrics
     */
    private function handle3PlayerLosersTripleTie(Tournament $tournament, string $level, ?int $groupId, $playerD, $playerE, $playerF)
    {
        \Log::info("Handling losers triple tie scenario - using metrics", [
            'players' => [$playerD, $playerE, $playerF]
        ]);
        
        // Calculate metrics for tie breaking
        $players = [$playerD, $playerE, $playerF];
        $playerMetrics = [];
        
        foreach ($players as $playerId) {
            $metrics = $this->calculatePlayerMetrics($tournament, $level, $groupId, $playerId);
            $playerMetrics[$playerId] = $metrics;
        }
        
        // Sort by win rate first, then total points
        uasort($playerMetrics, function($a, $b) {
            if ($a['win_rate'] != $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate']; // Higher win rate first
            }
            return $b['total_points'] <=> $a['total_points']; // Higher points first
        });
        
        $sortedPlayers = array_keys($playerMetrics);
        
        // Check for ties in rankings
        $tieInfo = $this->analyzeTieBreaking($playerMetrics);
        
        $positions = [
            1 => $sortedPlayers[0],  // Position 4
            2 => $sortedPlayers[1],  // Position 5
            3 => $sortedPlayers[2]   // Position 6
        ];
        
        $this->createWinnerRecords($tournament, $level, $positions, 'losers', $tieInfo);
        
        \Log::info("Losers triple tie resolved using metrics", [
            'positions' => $positions,
            'tie_info' => $tieInfo
        ]);
        
        return $positions;
    }
    
    /**
     * Check if losers tournament is needed for positions 4-6
     */
    private function checkAndCreateLosersTournament(Tournament $tournament, string $level, ?int $groupId)
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        // Only create losers tournament if we need 4, 5, or 6 winners AND we're at the tournament's target level
        if ($winnersNeeded < 4 || $winnersNeeded > 6 || !$this->isAtTournamentTargetLevel($tournament, $level)) {
            \Log::info("Skipping losers tournament creation", [
                'winners_needed' => $winnersNeeded,
                'level' => $level,
                'tournament_area_scope' => $tournament->area_scope,
                'tournament_area_name' => $tournament->area_name,
                'is_special' => $tournament->special,
                'is_target_level' => $this->isAtTournamentTargetLevel($tournament, $level),
                'reason' => !$this->isAtTournamentTargetLevel($tournament, $level) ? 'Not at tournament target level' : 'Winners needed not in 4-6 range'
            ]);
            return;
        }
        
        // Check if winners tournament is complete (positions 1-3 assigned)
        $existingWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->whereIn('position', [1, 2, 3])
            ->count();
            
        if ($existingWinners < 3) {
            return; // Winners tournament not complete yet
        }
        
        // Check if losers tournament already exists or is complete
        $existingLosersWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->whereIn('position', [4, 5, 6])
            ->count();
            
        if ($existingLosersWinners > 0) {
            return; // Losers tournament already processed
        }
        
        \Log::info("Creating losers tournament for additional positions", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_needed' => $winnersNeeded,
            'positions_needed' => [4, 5, 6]
        ]);
        
        // Get losers from the initial round (before 4player_round1)
        // For 8→4 scenario, we want losers from round_1, not from 4player_round1
        $allMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->whereNotIn('round_name', ['losers_SF', 'losers_final', 'losers_Fair_Chance', 'losers_semifinal', 'losers_winners_final', '4player_round1', 'winners_final'])
            ->get();
            
        $losers = collect();
        foreach ($allMatches as $match) {
            if ($match->winner_id) {
                $loserId = ($match->player_1_id === $match->winner_id) ? $match->player_2_id : $match->player_1_id;
                $loser = User::find($loserId);
                if ($loser) {
                    $losers->push($loser);
                }
            }
        }
        
        $losers = $losers->unique('id');
        $loserCount = $losers->count();
        
        \Log::info("Found losers for losers tournament", [
            'loser_count' => $loserCount,
            'loser_ids' => $losers->pluck('id')->toArray()
        ]);
        
        if ($loserCount === 3) {
            $this->createLosers3PlayerTournament($tournament, $level, $groupId, $losers, $winnersNeeded);
        } elseif ($loserCount === 4) {
            $this->createLosers4PlayerTournament($tournament, $level, $groupId, $losers, $winnersNeeded);
        }
    }
    
    /**
     * Create 3-player losers tournament (mirrors winners tournament)
     */
    private function createLosers3PlayerTournament(Tournament $tournament, string $level, ?int $groupId, $losers, int $winnersNeeded)
    {
        \Log::info("Creating 3-player losers tournament", [
            'tournament_id' => $tournament->id,
            'losers' => $losers->pluck('id')->toArray(),
            'winners_needed' => $winnersNeeded
        ]);
        
        // Create losers semifinal (2 players, 1 bye)
        $shuffledLosers = $losers->shuffle();
        
        PoolMatch::create([
            'match_name' => 'losers_SF_match',
            'player_1_id' => $shuffledLosers[0]->id,
            'player_2_id' => $shuffledLosers[1]->id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => 'losers_SF',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'bye_player_id' => $shuffledLosers[2]->id,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
        ]);
        
        \Log::info("Created losers semifinal match with bye player", [
            'player_1' => $shuffledLosers[0]->name,
            'player_2' => $shuffledLosers[1]->name,
            'bye_player' => $shuffledLosers[2]->name
        ]);
    }
    
    /**
     * Create 4-player losers tournament (simplified bracket)
     */
    private function createLosers4PlayerTournament(Tournament $tournament, string $level, ?int $groupId, $losers, int $winnersNeeded)
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
        
        // Create two initial matches for 4 losers
        $shuffledLosers = $losers->shuffle()->values();
        
        PoolMatch::create([
            'match_name' => '4player_losers_round1_M1',
            'player_1_id' => $shuffledLosers[0]->id,
            'player_2_id' => $shuffledLosers[1]->id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => '4player_losers_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
        ]);
        
        PoolMatch::create([
            'match_name' => '4player_losers_round1_M2',
            'player_1_id' => $shuffledLosers[2]->id,
            'player_2_id' => $shuffledLosers[3]->id,
            'level' => $level,
            'level_name' => $this->getLevelName($level, $groupId),
            'round_name' => '4player_losers_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
        ]);
        
        \Log::info("Created 4-player losers initial matches", [
            'match_1' => $shuffledLosers[0]->name . ' vs ' . $shuffledLosers[1]->name,
            'match_2' => $shuffledLosers[2]->name . ' vs ' . $shuffledLosers[3]->name
        ]);
    }
    
    /**
     * Handle Fair_Chance match result and determine final positions
     */
    private function handleFairChanceResult(Tournament $tournament, string $level, ?int $groupId, $sfMatch, $finalMatch, $fairChanceMatch)
    {
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = ($finalMatch->player_1_id === $sfLoser) ? $finalMatch->player_2_id : $finalMatch->player_1_id;
        $fairChanceWinner = $fairChanceMatch->winner_id;
        
        if ($fairChanceWinner === $sfWinner) {
            // SF winner won Fair_Chance: A=1st, B=2nd, C=3rd
            \Log::info("Fair_Chance: SF winner won - standard positioning");
            
            Winner::create(['player_id' => $sfWinner, 'position' => 1, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
            Winner::create(['player_id' => $sfLoser, 'position' => 2, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
            Winner::create(['player_id' => $byePlayer, 'position' => 3, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
            
        } else {
            // Bye player won Fair_Chance: All players have 1 win, 1 loss - use metrics
            \Log::info("Fair_Chance: Bye player won - all players tied, using metrics for positioning");
            
            $this->determinePositionsByMetrics($tournament, $level, $groupId, [$sfWinner, $sfLoser, $byePlayer]);
        }
    }
    
    /**
     * Determine positions based on tournament metrics when all players are tied (1 win, 1 loss each)
     */
    private function determinePositionsByMetrics(Tournament $tournament, string $level, ?int $groupId, array $playerIds)
    {
        $playerMetrics = [];
        
        foreach ($playerIds as $playerId) {
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
        
        // Sort by total points (desc), then win rate (desc)
        $sortedPlayers = collect($playerMetrics)->sortByDesc(function($player) {
            return [$player['total_points'], $player['win_rate']];
        })->values();
        
        // Check for ties and assign positions
        $this->assignPositionsWithTieHandling($tournament, $level, $groupId, $sortedPlayers);
    }
    
    /**
     * Assign positions handling ties with run-off declarations
     */
    private function assignPositionsWithTieHandling(Tournament $tournament, string $level, ?int $groupId, $sortedPlayers)
    {
        $positions = [];
        $currentPosition = 1;
        $tieGroups = [];
        
        // Group players by identical metrics
        for ($i = 0; $i < $sortedPlayers->count(); $i++) {
            $current = $sortedPlayers[$i];
            $tieGroup = [$current];
            
            // Check for ties with next players
            for ($j = $i + 1; $j < $sortedPlayers->count(); $j++) {
                $next = $sortedPlayers[$j];
                if ($current['total_points'] == $next['total_points'] && 
                    $current['win_rate'] == $next['win_rate']) {
                    $tieGroup[] = $next;
                    $i++; // Skip the tied player in outer loop
                } else {
                    break;
                }
            }
            
            if (count($tieGroup) > 1) {
                // Handle tie - assign positions randomly but mark as run-off
                $shuffledTie = collect($tieGroup)->shuffle();
                foreach ($shuffledTie as $index => $player) {
                    $positions[] = [
                        'player_id' => $player['player_id'],
                        'position' => $currentPosition + $index,
                        'is_runoff' => true,
                        'tied_with' => collect($tieGroup)->pluck('name')->implode(', '),
                        'metrics' => $player
                    ];
                }
                $currentPosition += count($tieGroup);
            } else {
                // No tie - assign position normally
                $positions[] = [
                    'player_id' => $tieGroup[0]['player_id'],
                    'position' => $currentPosition,
                    'is_runoff' => false,
                    'tied_with' => null,
                    'metrics' => $tieGroup[0]
                ];
                $currentPosition++;
            }
        }
        
        // Create winner records and send notifications
        foreach ($positions as $positionData) {
            Winner::create([
                'player_id' => $positionData['player_id'],
                'position' => $positionData['position'],
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
                'is_runoff' => $positionData['is_runoff'] ?? false,
                'runoff_reason' => $positionData['is_runoff'] ? 'Tied metrics after Fair_Chance round' : null,
            ]);
            
            // Send detailed notification with metrics
            $this->sendMetricsBasedPositionNotification($tournament, $positionData);
        }
        
        \Log::info("3-player Fair_Chance positions assigned", [
            'tournament_id' => $tournament->id,
            'positions' => $positions
        ]);
    }
    
    /**
     * Send notification with detailed metrics information
     */
    private function sendMetricsBasedPositionNotification(Tournament $tournament, array $positionData)
    {
        $player = User::find($positionData['player_id']);
        $metrics = $positionData['metrics'];
        
        $message = "🏆 Tournament Complete: {$tournament->name}\n\n";
        $message .= "Your Final Position: #{$positionData['position']}\n\n";
        
        if ($positionData['is_runoff']) {
            $message .= "⚖️ FAIR PLAY RESULT\n";
            $message .= "After Fair_Chance round, all players had equal wins/losses.\n";
            $message .= "Position determined by tournament metrics:\n\n";
        }
        
        $message .= "📊 Your Tournament Stats:\n";
        $message .= "• Total Points: {$metrics['total_points']}\n";
        $message .= "• Wins: {$metrics['wins']}/{$metrics['total_matches']}\n";
        $message .= "• Win Rate: " . number_format($metrics['win_rate'], 1) . "%\n\n";
        
        if ($positionData['is_runoff']) {
            $message .= "🎲 Tied with: {$positionData['tied_with']}\n";
            $message .= "Position assigned by fair random selection.\n\n";
        }
        
        $message .= "Congratulations on completing the tournament! 🎉";
        
        // Create notification
        \App\Models\Notification::create([
            'user_id' => $player->id,
            'type' => 'tournament_position_metrics',
            'title' => 'Tournament Complete - Position #' . $positionData['position'],
            'message' => $message,
            'data' => [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'position' => $positionData['position'],
                'is_runoff' => $positionData['is_runoff'],
                'metrics' => $metrics,
                'tied_with' => $positionData['tied_with']
            ]
        ]);
        
        // Send push notification if FCM token exists
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
    }
    
    /**
     * Determine winners for 4-player tournament
     */
    private function determine4PlayerWinners(Tournament $tournament, string $level, ?int $groupId)
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
                
                PoolMatch::create([
                    'match_name' => 'winners_final_match',
                    'player_1_id' => $winner1,
                    'player_2_id' => $winner2,
                    'level' => $level,
                    'level_name' => $this->getLevelName($level, $groupId),
                    'round_name' => 'winners_final',
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
            }
            
            if (!$losersSemifinal) {
                // Create losers semifinal match
                \Log::info("Creating 4-player losers semifinal match", [
                    'loser1' => $loser1,
                    'loser2' => $loser2,
                    'tournament_id' => $tournament->id
                ]);
                
                PoolMatch::create([
                    'match_name' => 'losers_semifinal_match',
                    'player_1_id' => $loser1,
                    'player_2_id' => $loser2,
                    'level' => $level,
                    'level_name' => $this->getLevelName($level, $groupId),
                    'round_name' => 'losers_semifinal',
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
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
            $winnersLoser = $winnersFinal->winner_id == $winnersFinal->player_1_id 
                ? $winnersFinal->player_2_id 
                : $winnersFinal->player_1_id;
                
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
            
            // Position 4: Losers semifinal loser (if we need 4+ winners AND at tournament target level)
            $winnersNeeded = $tournament->winners ?? 3;
            if ($winnersNeeded >= 4 && $this->isAtTournamentTargetLevel($tournament, $level)) {
                $losersLoser = $losersSemifinal->winner_id == $losersSemifinal->player_1_id 
                    ? $losersSemifinal->player_2_id 
                    : $losersSemifinal->player_1_id;
                    
                Winner::create([
                    'player_id' => $losersLoser,
                    'position' => 4,
                    'level' => $level,
                    'level_id' => $groupId,
                    'tournament_id' => $tournament->id,
                ]);
                
                \Log::info("Assigned position 4 at tournament target level", [
                    'player_id' => $losersLoser,
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'tournament_area_scope' => $tournament->area_scope,
                    'tournament_area_name' => $tournament->area_name
                ]);
            }
            
            return;
        }
        
        // Original logic for properly named matches
        $winnersSF = $matches->where('round_name', 'winners_SF')->first();
        $losersSF = $matches->where('round_name', 'losers_SF')->first();
        $finalMatch = $matches->where('round_name', '4_final')->first();
        
        if ($winnersSF && $losersSF && $finalMatch) {
            // Position 1: Winners SF winner
            Winner::create([
                'player_id' => $winnersSF->winner_id,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 2: 4_final winner
            Winner::create([
                'player_id' => $finalMatch->winner_id,
                'position' => 2,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 3: 4_final loser
            $finalLoser = $finalMatch->winner_id == $finalMatch->player_1_id 
                ? $finalMatch->player_2_id 
                : $finalMatch->player_1_id;
                
            Winner::create([
                'player_id' => $finalLoser,
                'position' => 3,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Note: Loser of losers_SF is eliminated (no position 4)
        }
    }
    
    /**
     * Determine winners for standard tournaments (3-4 players at non-target levels, or any count at target level)
     */
    private function determineStandardWinners(Tournament $tournament, string $level, ?int $groupId)
    {
        $playerCount = $this->getTotalPlayersInTournament($tournament, $level, $groupId);
        $isTargetLevel = $this->isAtTournamentTargetLevel($tournament, $level);
        
        \Log::info("Determining standard winners", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'player_count' => $playerCount,
            'is_target_level' => $isTargetLevel,
            'tournament_area_scope' => $tournament->area_scope
        ]);
        
        if ($playerCount === 3) {
            $this->determineStandard3PlayerWinners($tournament, $level, $groupId, $isTargetLevel);
        } elseif ($playerCount === 4) {
            $this->determineStandard4PlayerWinners($tournament, $level, $groupId, $isTargetLevel);
        } else {
            // For 5+ players, use elimination bracket logic
            $this->determineEliminationWinners($tournament, $level, $groupId);
        }
    }
    
    /**
     * Determine winners for 3-player standard tournaments
     */
    private function determineStandard3PlayerWinners(Tournament $tournament, string $level, ?int $groupId, bool $isTargetLevel)
    {
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
        
        if (!$sfMatch || !$finalMatch) {
            return; // Matches not complete yet
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $finalWinner = $finalMatch->winner_id;
        $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;
        
        if ($finalWinner === $sfLoser) {
            // SF loser (B) beat bye player (C) - Simple progression logic
            if (!$isTargetLevel) {
                // Progression: A=1st, B=2nd, C=3rd (no tie-breaker needed)
                Winner::create(['player_id' => $sfWinner, 'position' => 1, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                Winner::create(['player_id' => $sfLoser, 'position' => 2, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                Winner::create(['player_id' => $finalLoser, 'position' => 3, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                
                \Log::info("3-player progression: SF loser won final - simple positioning", [
                    'sf_winner' => $sfWinner, 'sf_loser' => $sfLoser, 'bye_player' => $finalLoser
                ]);
            } else {
                // Target level: Create break_tie match between A and B
                $breakTieMatch = PoolMatch::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('group_id', $groupId)
                    ->where('round_name', '3_break_tie_final')
                    ->where('status', 'completed')
                    ->first();
                
                if ($breakTieMatch) {
                    // Process break_tie result
                    $breakTieWinner = $breakTieMatch->winner_id;
                    $breakTieLoser = ($breakTieMatch->player_1_id === $breakTieWinner) ? $breakTieMatch->player_2_id : $breakTieMatch->player_1_id;
                    
                    Winner::create(['player_id' => $breakTieWinner, 'position' => 1, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                    Winner::create(['player_id' => $breakTieLoser, 'position' => 2, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                    Winner::create(['player_id' => $sfLoser, 'position' => 3, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]); // B is 3rd
                } else {
                    // Create break_tie match
                    PoolMatch::create([
                        'match_name' => '3_break_tie_final_match',
                        'player_1_id' => $sfWinner,
                        'player_2_id' => $finalLoser, // bye player who lost final
                        'level' => $level,
                        'level_name' => $this->getLevelName($level, $groupId),
                        'round_name' => '3_break_tie_final',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                }
            }
        } else {
            // Bye player (C) beat SF loser (B) in final
            if (!$isTargetLevel) {
                // Progression: C=1st, A=2nd, B=3rd (no Fair_Chance needed)
                Winner::create(['player_id' => $finalWinner, 'position' => 1, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                Winner::create(['player_id' => $sfWinner, 'position' => 2, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                Winner::create(['player_id' => $sfLoser, 'position' => 3, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
                
                \Log::info("3-player progression: Bye player won final - simple positioning", [
                    'bye_winner' => $finalWinner, 'sf_winner' => $sfWinner, 'sf_loser' => $sfLoser
                ]);
            } else {
                // Target level: Check for Fair_Chance match or create it
                $fairChanceMatch = PoolMatch::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('group_id', $groupId)
                    ->where('round_name', 'Fair_Chance')
                    ->where('status', 'completed')
                    ->first();
                
                if ($fairChanceMatch) {
                    // Process Fair_Chance result (same as special tournaments)
                    $this->handleFairChanceResult($tournament, $level, $groupId, $sfMatch, $finalMatch, $fairChanceMatch);
                } else {
                    // Create Fair_Chance match between SF winner and bye player
                    PoolMatch::create([
                        'match_name' => 'Fair_Chance_match',
                        'player_1_id' => $sfWinner,
                        'player_2_id' => $finalLoser, // bye player who lost final
                        'level' => $level,
                        'level_name' => $this->getLevelName($level, $groupId),
                        'round_name' => 'Fair_Chance',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                }
            }
        }
    }
    
    /**
     * Determine winners for 4-player standard tournaments
     */
    private function determineStandard4PlayerWinners(Tournament $tournament, string $level, ?int $groupId, bool $isTargetLevel)
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
            
            \Log::info("4-player standard winners determined", [
                'winners_final_winner' => $winnersMatch->winner_id,
                'winners_final_loser' => $winnersLoser,
                'losers_semifinal_winner' => $losersMatch->winner_id
            ]);
        }
    }
    
    /**
     * Determine winners for elimination tournaments (5+ players)
     */
    private function determineEliminationWinners(Tournament $tournament, string $level, ?int $groupId)
    {
        // Get all completed matches and determine final standings
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->get();
        
        // Find the final match (highest round)
        $finalMatch = $matches->where('round_name', 'LIKE', '%_final')->first();
        
        if (!$finalMatch) {
            // If no explicit final match, find the match with the highest round number
            $finalMatch = $matches->sortByDesc('round_name')->first();
        }
        
        if ($finalMatch) {
            // Position 1: Winner of final match
            Winner::create([
                'player_id' => $finalMatch->winner_id,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 2: Loser of final match
            $finalLoser = $finalMatch->winner_id == $finalMatch->player_1_id 
                ? $finalMatch->player_2_id 
                : $finalMatch->player_1_id;
                
            Winner::create([
                'player_id' => $finalLoser,
                'position' => 2,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 3: Winner of semi-final who didn't make it to final
            $semiMatches = $matches->filter(function($match) {
                return strpos($match->round_name, '_SF') !== false || 
                       strpos($match->round_name, 'semi') !== false;
            });
            
            foreach ($semiMatches as $semiMatch) {
                if ($semiMatch->winner_id != $finalMatch->player_1_id && 
                    $semiMatch->winner_id != $finalMatch->player_2_id) {
                    Winner::create([
                        'player_id' => $semiMatch->winner_id,
                        'position' => 3,
                        'level' => $level,
                        'level_id' => $groupId,
                        'tournament_id' => $tournament->id,
                    ]);
                    break;
                }
            }
        }
    }

    /**
     * Get group name for notifications
     */
    private function getGroupName(string $level, ?int $groupId): string
    {
        if ($groupId === null) {
            return $level;
        }
        
        switch ($level) {
            case 'community':
                $community = \App\Models\Community::find($groupId);
                return $community ? $community->name : "Community {$groupId}";
            case 'county':
                $county = \App\Models\County::find($groupId);
                return $county ? $county->name : "County {$groupId}";
            case 'regional':
                $region = \App\Models\Region::find($groupId);
                return $region ? $region->name : "Region {$groupId}";
            default:
                return $level;
        }
    }

    /**
     * Initialize special tournament without level-based grouping
     */
    private function initializeSpecialTournament(Tournament $tournament): array
    {
        // Check if tournament is already initialized
        $existingMatches = PoolMatch::where('tournament_id', $tournament->id)->exists();
        if ($existingMatches) {
            throw new \Exception("Special tournament already initialized");
        }

        DB::beginTransaction();
        try {
            // Get all registered players for the tournament
            $allRegisteredPlayers = $tournament->registeredUsers()
                ->wherePivot('payment_status', 'paid')
                ->wherePivot('status', 'approved')
                ->get();

            // Filter players based on tournament's area scope
            $players = $this->filterPlayersByTournamentScope($allRegisteredPlayers, $tournament);

            if ($players->isEmpty()) {
                throw new \Exception("No eligible players found for special tournament");
            }

            \Log::info("Initializing special tournament", [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'total_players' => $players->count()
            ]);

            // For special tournaments, create matches without level-based grouping
            // All players compete in a single pool
            $matchesCreated = $this->createSpecialTournamentMatchesLegacy($tournament, $players);

            DB::commit();

            // Send notifications to all players
            $this->sendSpecialTournamentNotifications($tournament, $players);
            
            // Send pairing notifications for specific matches
            $this->sendPairingNotifications($tournament, 'special');

            \Log::info("Special tournament initialized successfully", [
                'tournament_id' => $tournament->id,
                'matches_created' => $matchesCreated
            ]);

            return [
                'status' => 'success',
                'message' => "Special tournament initialized successfully",
                'matches_created' => $matchesCreated
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to initialize special tournament", [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create matches for special tournament (legacy method)
     */
    private function createSpecialTournamentMatchesLegacy(Tournament $tournament, $players): int
    {
        $playersArray = $players->toArray();
        $playerCount = count($playersArray);
        $matchesCreated = 0;

        \Log::info("Creating special tournament matches", [
            'tournament_id' => $tournament->id,
            'player_count' => $playerCount
        ]);

        // Handle different player counts
        if ($playerCount < 2) {
            throw new \Exception("Need at least 2 players to create matches");
        }

        // Create matches using standard tournament pairing
        // For special tournaments, we don't group by demographics - all players compete together
        $matches = $this->createMatchPairs($playersArray);

        foreach ($matches as $match) {
            $matchData = [
                'tournament_id' => $tournament->id,
                'player_1_id' => $match['player1']['id'],
                'player_2_id' => $match['player2']['id'],
                'level' => 'special', // Use 'special' as the level
                'round_name' => $match['round_name'] ?? 'Special Tournament Round 1',
                'match_name' => $match['match_name'] ?? 'Special Match #' . ($matchesCreated + 1),
                'status' => 'pending',
                'group_id' => 1, // Single group for special tournaments
                'group_name' => 'Special Tournament',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ];
            
            // Add bye player if present (for 3-player tournaments)
            if (isset($match['bye_player_id'])) {
                $matchData['bye_player_id'] = $match['bye_player_id'];
            }
            
            PoolMatch::create($matchData);
            $matchesCreated++;
            
            \Log::info("Created special tournament match", [
                'match_name' => $matchData['match_name'],
                'round_name' => $matchData['round_name'],
                'player_1_id' => $matchData['player_1_id'],
                'player_2_id' => $matchData['player_2_id'],
                'bye_player_id' => $matchData['bye_player_id'] ?? null
            ]);
        }

        return $matchesCreated;
    }

    /**
     * Create match pairs from players array
     */
    private function createMatchPairs(array $players): array
    {
        $matches = [];
        $playerCount = count($players);

        \Log::info("Creating match pairs for special tournament", [
            'player_count' => $playerCount,
            'players' => array_column($players, 'name')
        ]);

        // Handle specific player counts properly
        switch ($playerCount) {
            case 2:
                // Simple 1v1 final
                $matches[] = [
                    'player1' => $players[0],
                    'player2' => $players[1],
                    'round_name' => '2_final',
                    'match_name' => '2_final'
                ];
                break;

            case 3:
                // 3-player tournament: semifinal + final
                // Semifinal: Player 1 vs Player 2 (Player 3 gets bye to final)
                $matches[] = [
                    'player1' => $players[0],
                    'player2' => $players[1],
                    'bye_player_id' => $players[2]['id'],
                    'round_name' => '3_SF',
                    'match_name' => '3_SF'
                ];
                break;

            case 4:
                // 4-player tournament: Use unique round name to avoid conflicts
                $matches[] = [
                    'player1' => $players[0],
                    'player2' => $players[1],
                    'round_name' => '4player_round1',
                    'match_name' => '4player_round1_match1'
                ];
                $matches[] = [
                    'player1' => $players[2],
                    'player2' => $players[3],
                    'round_name' => '4player_round1',
                    'match_name' => '4player_round1_match2'
                ];
                break;

            default:
                if ($playerCount % 2 === 0) {
                    // Even number of players - pair them up for first round
                    for ($i = 0; $i < $playerCount; $i += 2) {
                        $matches[] = [
                            'player1' => $players[$i],
                            'player2' => $players[$i + 1],
                            'round_name' => 'round_1',
                            'match_name' => 'round_1_match' . (($i / 2) + 1)
                        ];
                    }
                } else {
                    // Odd number of players (>3) - one player plays twice
                    \Log::info("Odd number of players ({$playerCount}), one player will play twice");
                    
                    // Pick first player to play twice
                    $doublePlayer = $players[0];
                    
                    // Create first match with double player
                    $matches[] = [
                        'player1' => $doublePlayer,
                        'player2' => $players[1],
                        'round_name' => 'round_1',
                        'match_name' => 'round_1_match1'
                    ];
                    
                    // Create second match with double player
                    $matches[] = [
                        'player1' => $doublePlayer,
                        'player2' => $players[2],
                        'round_name' => 'round_1',
                        'match_name' => 'round_1_match2'
                    ];
                    
                    // Create matches for remaining paired players (skip first 3 players)
                    $matchNumber = 3;
                    for ($i = 3; $i < $playerCount - 1; $i += 2) {
                        $matches[] = [
                            'player1' => $players[$i],
                            'player2' => $players[$i + 1],
                            'round_name' => 'round_1',
                            'match_name' => 'round_1_match' . $matchNumber
                        ];
                        $matchNumber++;
                    }
                }
                break;
        }

        \Log::info("Match pairs created", [
            'matches_count' => count($matches),
            'matches' => $matches
        ]);

        return $matches;
    }

    /**
     * Filter players based on tournament's area scope
     */
    private function filterPlayersByTournamentScope($players, Tournament $tournament)
    {
        // If no area scope, return all players (national tournaments)
        if (!$tournament->area_scope || $tournament->area_scope === 'national') {
            return $players;
        }

        return $players->filter(function ($player) use ($tournament) {
            switch ($tournament->area_scope) {
                case 'community':
                    return $player->community && $player->community->name === $tournament->area_name;
                case 'county':
                    return $player->county && $player->county->name === $tournament->area_name;
                case 'region':
                    return $player->region && $player->region->name === $tournament->area_name;
                default:
                    return false;
            }
        });
    }

    /**
     * Send notifications for special tournament initialization
     */
    private function sendSpecialTournamentNotifications(Tournament $tournament, $players): void
    {
        foreach ($players as $player) {
            Notification::create([
                'player_id' => $player->id,
                'type' => 'tournament_started',
                'message' => "Special tournament '{$tournament->name}' has started! Check your matches.",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'tournament_type' => 'special'
                ]
            ]);
        }
    }
    
    /**
     * Check and determine losers tournament completion for positions 4-6
     */
    private function checkLosersTournamentCompletion(Tournament $tournament, string $level, ?int $groupId)
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        // Only process if we need 4, 5, or 6 winners AND we're at the tournament's target level
        if ($winnersNeeded < 4 || $winnersNeeded > 6 || !$this->isAtTournamentTargetLevel($tournament, $level)) {
            return;
        }
        
        // Check if main tournament positions 1-3 are complete
        $mainWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->whereIn('position', [1, 2, 3])
            ->count();
            
        if ($mainWinners < 3) {
            return; // Main tournament not complete
        }
        
        // Check if losers positions already assigned
        $losersWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->whereIn('position', [4, 5, 6])
            ->count();
            
        if ($losersWinners > 0) {
            return; // Already processed
        }
        
        // Get losers tournament matches
        $losersMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->where(function($q) {
                $q->where('round_name', 'LIKE', 'losers_%');
            })
            ->get();
            
        if ($losersMatches->isEmpty()) {
            return; // No losers matches yet
        }
        
        // Determine losers tournament type and process
        $losersSF = $losersMatches->where('round_name', 'losers_SF')->first();
        $losersFinal = $losersMatches->where('round_name', 'losers_final')->first();
        $losersFairChance = $losersMatches->where('round_name', 'losers_Fair_Chance')->first();
        $losersBreakTie = $losersMatches->where('round_name', 'losers_break_tie')->first();
        $losersRound1 = $losersMatches->where('round_name', '4player_losers_round1');
        $losersWinnersFinal = $losersMatches->where('round_name', 'losers_winners_final')->first();
        
        if ($losersSF) {
            // 3-player losers tournament
            $this->determineLosers3PlayerPositions($tournament, $level, $groupId, $losersSF, $losersFinal, $losersFairChance, $losersBreakTie, $winnersNeeded);
        } elseif ($losersRound1->count() === 2) {
            // 4-player losers tournament
            $this->determineLosers4PlayerPositions($tournament, $level, $groupId, $losersRound1, $losersWinnersFinal, $winnersNeeded);
        }
    }
    
    /**
     * Determine positions 4-6 from 3-player losers tournament
     */
    private function determineLosers3PlayerPositions(Tournament $tournament, string $level, ?int $groupId, $losersSF, $losersFinal, $losersFairChance, $losersBreakTie, int $winnersNeeded)
    {
        // Handle case with only SF match (no final match created)
        if ($losersSF && !$losersFinal) {
            // Position 4: SF winner
            \App\Models\Winner::create([
                'player_id' => $losersSF->winner_id,
                'position' => 4,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 5: SF loser
            $sfLoser = $losersSF->winner_id == $losersSF->player_1_id ? $losersSF->player_2_id : $losersSF->player_1_id;
            \App\Models\Winner::create([
                'player_id' => $sfLoser,
                'position' => 5,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 6: Bye player
            \App\Models\Winner::create([
                'player_id' => $losersSF->bye_player_id,
                'position' => 6,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            $this->sendLosersTournamentNotifications($tournament, $level, $groupId, $winnersNeeded);
            return;
        }
        
        // Handle completed losers tournament with Fair_Chance logic
        if ($losersSF && $losersFinal) {
            $sfWinner = $losersSF->winner_id;
            $sfLoser = ($losersSF->player_1_id === $sfWinner) ? $losersSF->player_2_id : $losersSF->player_1_id;
            $finalWinner = $losersFinal->winner_id;
            $finalLoser = ($losersFinal->player_1_id === $finalWinner) ? $losersFinal->player_2_id : $losersFinal->player_1_id;
            
            if ($finalWinner !== $sfLoser) {
                // Bye player won - check for fair chance or create it
                if (!$losersFairChance) {
                    // Create Fair_Chance match
                    PoolMatch::create([
                        'match_name' => 'losers_Fair_Chance_match',
                        'player_1_id' => $sfWinner,
                        'player_2_id' => $finalLoser,
                        'level' => $level,
                        'level_name' => $this->getLevelName($level, $groupId),
                        'round_name' => 'losers_Fair_Chance',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    return;
                } else {
                    // Process Fair_Chance result
                    $this->handleLosersFairChanceResult($tournament, $level, $groupId, $losersSF, $losersFinal, $losersFairChance, $winnersNeeded);
                }
            } else {
                // SF loser won final - Check for tie-breaker match
                $losersBreakTie = PoolMatch::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('group_id', $groupId)
                    ->where('round_name', 'losers_break_tie')
                    ->where('status', 'completed')
                    ->first();
                
                if ($losersBreakTie) {
                    // Process tie-breaker result
                    $this->handleLosersBreakTieResult($tournament, $level, $groupId, $losersSF, $losersFinal, $losersBreakTie, $winnersNeeded);
                } else {
                    // Create tie-breaker match between SF winner and bye player (final loser)
                    \Log::info("Creating losers tie-breaker match", [
                        'sf_winner' => $sfWinner,
                        'bye_player' => $finalLoser,
                        'tournament_id' => $tournament->id
                    ]);
                    
                    PoolMatch::create([
                        'match_name' => 'losers_break_tie_match',
                        'player_1_id' => $sfWinner,
                        'player_2_id' => $finalLoser, // This is the bye player who lost final
                        'level' => $level,
                        'level_name' => $this->getLevelName($level, $groupId),
                        'round_name' => 'losers_break_tie',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    return; // Wait for tie-breaker match to complete
                }
            }
        }
    }
    
    /**
     * Handle Fair_Chance result for losers tournament
     */
    private function handleLosersFairChanceResult(Tournament $tournament, string $level, ?int $groupId, $losersSF, $losersFinal, $losersFairChance, int $winnersNeeded)
    {
        $sfWinner = $losersSF->winner_id;
        $sfLoser = ($losersSF->player_1_id === $sfWinner) ? $losersSF->player_2_id : $losersSF->player_1_id;
        $byePlayer = ($losersFinal->player_1_id === $sfLoser) ? $losersFinal->player_2_id : $losersFinal->player_1_id;
        $fairChanceWinner = $losersFairChance->winner_id;
        
        if ($fairChanceWinner === $sfWinner) {
            // SF winner won Fair_Chance: standard positioning
            \App\Models\Winner::create(['player_id' => $sfWinner, 'position' => 4, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
            \App\Models\Winner::create(['player_id' => $sfLoser, 'position' => 5, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
            \App\Models\Winner::create(['player_id' => $byePlayer, 'position' => 6, 'level' => $level, 'level_id' => $groupId, 'tournament_id' => $tournament->id]);
        } else {
            // Bye player won Fair_Chance: use metrics
            $this->determineLosersPositionsByMetrics($tournament, $level, $groupId, [$sfWinner, $sfLoser, $byePlayer], [4, 5, 6], $winnersNeeded);
        }
        
        $this->sendLosersTournamentNotifications($tournament, $level, $groupId, $winnersNeeded);
    }
    
    /**
     * Handle tie-breaker result for losers tournament
     */
    private function handleLosersBreakTieResult(Tournament $tournament, string $level, ?int $groupId, $losersSF, $losersFinal, $losersBreakTie, int $winnersNeeded)
    {
        $sfWinner = $losersSF->winner_id;
        $sfLoser = ($losersSF->player_1_id === $sfWinner) ? $losersSF->player_2_id : $losersSF->player_1_id;
        $byePlayer = ($losersFinal->player_1_id === $sfLoser) ? $losersFinal->player_2_id : $losersFinal->player_1_id;
        $breakTieWinner = $losersBreakTie->winner_id;
        
        \Log::info("Processing losers tie-breaker result", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser,
            'bye_player' => $byePlayer,
            'break_tie_winner' => $breakTieWinner,
            'tournament_id' => $tournament->id
        ]);
        
        // Position 4: Tie-breaker winner
        \App\Models\Winner::create([
            'player_id' => $breakTieWinner,
            'position' => 4,
            'level' => $level,
            'level_id' => $groupId,
            'tournament_id' => $tournament->id,
        ]);
        
        // Position 5: Tie-breaker loser
        $breakTieLoser = ($losersBreakTie->player_1_id === $breakTieWinner) ? $losersBreakTie->player_2_id : $losersBreakTie->player_1_id;
        \App\Models\Winner::create([
            'player_id' => $breakTieLoser,
            'position' => 5,
            'level' => $level,
            'level_id' => $groupId,
            'tournament_id' => $tournament->id,
        ]);
        
        // Position 6: SF loser (who won the final but is now in 3rd place)
        \App\Models\Winner::create([
            'player_id' => $sfLoser,
            'position' => 6,
            'level' => $level,
            'level_id' => $groupId,
            'tournament_id' => $tournament->id,
        ]);
        
        $this->sendLosersTournamentNotifications($tournament, $level, $groupId, $winnersNeeded);
    }
    
    /**
     * Determine positions 5-6 from 4-player losers tournament
     */
    private function determineLosers4PlayerPositions(Tournament $tournament, string $level, ?int $groupId, $losersRound1, $losersWinnersFinal, int $winnersNeeded)
    {
        if ($losersRound1->count() === 2 && !$losersWinnersFinal) {
            // Create winners final for 4-player losers
            $match1 = $losersRound1->first();
            $match2 = $losersRound1->last();
            
            PoolMatch::create([
                'match_name' => 'losers_winners_final_match',
                'player_1_id' => $match1->winner_id,
                'player_2_id' => $match2->winner_id,
                'level' => $level,
                'level_name' => $this->getLevelName($level, $groupId),
                'round_name' => 'losers_winners_final',
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
            ]);
            return;
        }
        
        if ($losersRound1->count() === 2 && $losersWinnersFinal) {
            // Determine final positions
            $match1 = $losersRound1->first();
            $match2 = $losersRound1->last();
            
            // Position 4: Loser of main tournament winners final (already assigned in 4-player logic)
            
            // Position 5: Winner of losers winners final
            \App\Models\Winner::create([
                'player_id' => $losersWinnersFinal->winner_id,
                'position' => 5,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 6: Loser of losers winners final
            $winnersLoser = $losersWinnersFinal->winner_id == $losersWinnersFinal->player_1_id 
                ? $losersWinnersFinal->player_2_id 
                : $losersWinnersFinal->player_1_id;
                
            \App\Models\Winner::create([
                'player_id' => $winnersLoser,
                'position' => 6,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            $this->sendLosersTournamentNotifications($tournament, $level, $groupId, $winnersNeeded);
        }
    }
    
    /**
     * Determine losers positions by metrics (similar to main tournament)
     */
    private function determineLosersPositionsByMetrics(Tournament $tournament, string $level, ?int $groupId, array $playerIds, array $positions, int $winnersNeeded)
    {
        // Use same metrics logic as main tournament but assign to positions 4-6
        $playerMetrics = [];
        
        foreach ($playerIds as $playerId) {
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
        
        // Sort and assign positions 4-6
        $sortedPlayers = collect($playerMetrics)->sortByDesc(function($player) {
            return [$player['total_points'], $player['win_rate']];
        })->values();
        
        foreach ($sortedPlayers as $index => $player) {
            $position = $positions[$index];
            
            \App\Models\Winner::create([
                'player_id' => $player['player_id'],
                'position' => $position,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
                'is_runoff' => false, // Could add tie detection here
            ]);
        }
    }
    
    /**
     * Send notifications for losers tournament completion (only to winners up to needed count)
     */
    private function sendLosersTournamentNotifications(Tournament $tournament, string $level, ?int $groupId, int $winnersNeeded)
    {
        $allWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->where('position', '<=', $winnersNeeded)
            ->orderBy('position')
            ->get();
            
        foreach ($allWinners as $winner) {
            $player = User::find($winner->player_id);
            
            $message = "🏆 Tournament Complete: {$tournament->name}\n\n";
            $message .= "Your Final Position: #{$winner->position}\n\n";
            
            if ($winner->position >= 4) {
                $message .= "🥉 LOSERS TOURNAMENT RESULT\n";
                $message .= "You competed in the losers bracket for positions 4-6.\n\n";
            }
            
            $message .= "Congratulations on completing the tournament! 🎉";
            
            \App\Models\Notification::create([
                'user_id' => $player->id,
                'type' => 'tournament_position_final',
                'title' => 'Tournament Complete - Position #' . $winner->position,
                'message' => $message,
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'position' => $winner->position,
                    'is_losers_bracket' => $winner->position >= 4
                ]
            ]);
            
            // Send push notification
            if ($player->fcm_token) {
                app(\App\Services\PushNotificationService::class)->sendNotification(
                    $player->fcm_token,
                    "Tournament Complete!",
                    "You finished #{$winner->position} in {$tournament->name}",
                    [
                        'type' => 'tournament_position_final',
                        'tournament_id' => $tournament->id,
                        'position' => $winner->position
                    ]
                );
            }
        }
        
        \Log::info("Sent losers tournament notifications", [
            'tournament_id' => $tournament->id,
            'winners_notified' => $allWinners->count(),
            'winners_needed' => $winnersNeeded
        ]);
    }
    
    /**
     * Create 4-player tournament structure from 4 winners
     */
    private function create4PlayerTournamentFromWinners(Tournament $tournament, Collection $winners, string $level, $groupId, string $levelName)
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
        PoolMatch::create([
            'match_name' => '4player_round1_match1',
            'player_1_id' => $winnersArray[0]->id,
            'player_2_id' => $winnersArray[1]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
        ]);
        
        // Create Round 1 Match 2: C vs D
        PoolMatch::create([
            'match_name' => '4player_round1_match2',
            'player_1_id' => $winnersArray[2]->id,
            'player_2_id' => $winnersArray[3]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
        ]);
    }
    
    /**
     * Check if we're at the tournament's target level (where final winners are determined)
     */
    private function isAtTournamentTargetLevel(Tournament $tournament, string $level): bool
    {
        // Special tournaments don't have multi-level progression
        if ($tournament->special) {
            return true;
        }
        
        // If no area_scope is specified, default to national level
        if (!$tournament->area_scope || $tournament->area_scope === 'national') {
            return $level === 'national';
        }
        
        // Check if current level matches tournament's area_scope
        return $level === $tournament->area_scope;
    }
}
