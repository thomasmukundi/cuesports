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
    public function generateNextRound(int $tournamentId, string $level, ?int $groupId = null)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        DB::beginTransaction();
        try {
            // Check if current round is completed
            if (!$this->isRoundCompleted($tournament, $level, $groupId)) {
                throw new \Exception("Current round is not yet completed");
            }
            
            // Get winners from current round
            $currentRoundMatches = $this->getCurrentRoundMatches($tournament, $level, $groupId);
            $winners = $this->getWinnersFromMatches($currentRoundMatches);
            
            if ($winners->count() <= 4) {
                // Handle special progression cases (4, 3, 2 players)
                $levelName = $this->getLevelName($level, $groupId);
                $this->handleSpecialProgression($tournament, $winners, $level, $groupId, $currentRoundMatches, $levelName);
            } else {
                // Handle odd number progression (>4 players)
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
     * Handle special progression cases for 2, 3, and 4 player tournaments
     */
    private function handleSpecialProgression(Tournament $tournament, Collection $winners, string $level, $groupId, Collection $currentRoundMatches, $levelName)
    {
        $currentRound = $currentRoundMatches->first()->round_name;
        $playerCount = $this->getTotalPlayersInTournament($tournament, $level, $groupId);
        
        switch ($playerCount) {
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
                if ($currentRound === 'round_1') {
                    // After round 1, create 4_SF_winners and 4_SF_losers
                    $matches = $currentRoundMatches->sortBy('match_name');
                    $match1 = $matches->first();
                    $match2 = $matches->last();
                    
                    $winner1 = $match1->winner_id;
                    $loser1 = ($match1->player_1_id === $winner1) ? $match1->player_2_id : $match1->player_1_id;
                    
                    $winner2 = $match2->winner_id;
                    $loser2 = ($match2->player_1_id === $winner2) ? $match2->player_2_id : $match2->player_1_id;
                    
                    // Create 4_SF_winners
                    PoolMatch::create([
                        'match_name' => '4_SF_winners',
                        'player_1_id' => $winner1,
                        'player_2_id' => $winner2,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => 'semifinal',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                    ]);
                    
                    // Create 4_SF_losers
                    PoolMatch::create([
                        'match_name' => '4_SF_losers',
                        'player_1_id' => $loser1,
                        'player_2_id' => $loser2,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => 'semifinal',
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                    ]);
                    
                } elseif ($currentRound === 'semifinal') {
                    // Check if both SF matches are complete
                    $winnersSF = PoolMatch::where('tournament_id', $tournament->id)
                        ->where('level', $level)
                        ->where('level_name', $levelName)
                        ->where('round_name', 'semifinal')
                        ->where('match_name', '4_SF_winners')
                        ->where('status', 'completed')
                        ->first();
                        
                    $losersSF = PoolMatch::where('tournament_id', $tournament->id)
                        ->where('level', $level)
                        ->where('level_name', $levelName)
                        ->where('round_name', 'semifinal')
                        ->where('match_name', '4_SF_losers')
                        ->where('status', 'completed')
                        ->first();
                    
                    if ($winnersSF && $losersSF) {
                        // Create 4_final: loser of winners_SF vs winner of losers_SF
                        $winnersLoser = ($winnersSF->player_1_id === $winnersSF->winner_id) ? 
                            $winnersSF->player_2_id : $winnersSF->player_1_id;
                        $losersWinner = $losersSF->winner_id;
                        
                        PoolMatch::create([
                            'match_name' => '4_final',
                            'player_1_id' => $winnersLoser,
                            'player_2_id' => $losersWinner,
                            'level' => $level,
                            'level_name' => $levelName,
                            'round_name' => '4_final',
                            'tournament_id' => $tournament->id,
                            'group_id' => $groupId,
                            'status' => 'pending',
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
        
        \Log::info("Creating smart pairing for {$level} level: {$playerCount} players in group {$groupId}");
        
        // Only handle special cases for 1-4 players
        if ($playerCount <= 4) {
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
            // Create 4-player tournament: 2 semifinals, then final
            $pairedPlayers = $this->smartPairPlayers($players, $level);
            
            // Winners semifinal
            $sfWinners = $this->createMatch($tournament, $pairedPlayers[0], $pairedPlayers[1], 'semifinal', $level, $groupId, $levelName);
            $matches[] = $sfWinners;
            
            // Losers semifinal  
            $sfLosers = $this->createMatch($tournament, $pairedPlayers[2], $pairedPlayers[3], 'semifinal', $level, $groupId, $levelName);
            $matches[] = $sfLosers;
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
                'user_id' => $player->id,
                'title' => "Next Level Tournament Started!",
                'message' => "You have qualified for the {$level} level tournament. Check your matches to see your opponents.",
                'type' => 'tournament_level_start',
                'data' => json_encode([
                    'tournament_id' => $tournament->id,
                    'level' => $level
                ])
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
        
        if ($playerCount === 1) {
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
        
        // Shuffle players to randomize pairings while avoiding same community/county
        $shuffledPlayers = $this->shuffleWithAvoidance($players, $level);
        
        if ($playerCount <= 4) {
            $this->handleSpecialCases($tournament, $shuffledPlayers, $level, $groupId, $levelName);
        } else {
            $this->createStandardMatchesWithAvoidance($tournament, $shuffledPlayers, $level, $groupId, $roundName, $levelName);
        }
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
        $playerArray = $players->toArray();
        $playerCount = count($playerArray);
        $matchNumber = 1;
        
        \Log::info("Creating standard matches for {$playerCount} players at {$level} level, group {$groupId}");
        
        // Handle odd number of players - one player must play twice
        if ($playerCount % 2 == 1) {
            // Pick a random player to play twice
            $doublePlayerIndex = array_rand($playerArray);
            $doublePlayer = $playerArray[$doublePlayerIndex];
            
            \Log::info("Odd number ({$playerCount}) players - Player {$doublePlayer['id']} will play twice");
            
            // Create first match with the double player
            $firstOpponentIndex = ($doublePlayerIndex + 1) % $playerCount;
            if ($firstOpponentIndex == $doublePlayerIndex) {
                $firstOpponentIndex = ($doublePlayerIndex + 2) % $playerCount;
            }
            $firstOpponent = $playerArray[$firstOpponentIndex];
            
            PoolMatch::create([
                'match_name' => "{$level}_R1_M{$matchNumber}",
                'player_1_id' => $doublePlayer['id'],
                'player_2_id' => $firstOpponent['id'],
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
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
            for ($i = 0; $i < count($remainingPlayers); $i += 2) {
                if (isset($remainingPlayers[$i + 1])) {
                    PoolMatch::create([
                        'match_name' => "{$level}_R1_M{$matchNumber}",
                        'player_1_id' => $remainingPlayers[$i]['id'],
                        'player_2_id' => $remainingPlayers[$i + 1]['id'],
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => $roundName,
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    $matchNumber++;
                }
            }
            
            // Create second match for the double player with the remaining unpaired player
            if (count($remainingPlayers) % 2 == 1) {
                $lastPlayer = end($remainingPlayers);
                PoolMatch::create([
                    'match_name' => "{$level}_R1_M{$matchNumber}",
                    'player_1_id' => $doublePlayer['id'],
                    'player_2_id' => $lastPlayer['id'],
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $roundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                $matchNumber++;
            }
        } else {
            // Even number - normal pairing
            for ($i = 0; $i < $playerCount; $i += 2) {
                if (isset($playerArray[$i + 1])) {
                    PoolMatch::create([
                        'match_name' => "{$level}_R1_M{$matchNumber}",
                        'player_1_id' => $playerArray[$i]['id'],
                        'player_2_id' => $playerArray[$i + 1]['id'],
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => $roundName,
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                    ]);
                    $matchNumber++;
                }
            }
        }
        
        \Log::info("Created " . ($matchNumber - 1) . " matches for {$playerCount} players");
    }

    /**
     * Handle large group progression (>4 players) with odd number handling
     */
    private function handleLargeGroupProgression(Tournament $tournament, Collection $winners, string $level, $groupId, Collection $currentRoundMatches)
    {
        $levelName = $this->getLevelName($level, $groupId);
        $nextRoundName = $this->getNextRoundName($currentRoundMatches->first()->round_name);
        
        \Log::info("Large group progression: {$winners->count()} winners for next round at {$level} level");
        
        // If odd number of winners > 3, add a loser to make even pairs
        if ($winners->count() > 3 && $winners->count() % 2 === 1) {
            $losers = $this->getLosersFromMatches($currentRoundMatches);
            if ($losers->count() > 0) {
                // Add one loser to make even number for pairing
                $selectedLoser = $losers->first();
                $winners->push($selectedLoser);
                \Log::info("Added loser player {$selectedLoser->id} to make even number: {$winners->count()} total players");
            }
        }
        
        // For odd numbers ≤ 3, let one player play twice (handled in createStandardMatches)
        $this->createStandardMatches($tournament, $winners, $level, $groupId, $nextRoundName, $levelName);
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
    private function handleSpecialCases(Tournament $tournament, Collection $players, string $level, $groupId, $levelName)
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
                PoolMatch::create([
                    'match_name' => '2_final_match',
                    'player_1_id' => $players->first()->id,
                    'player_2_id' => $players->last()->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => '2_final',
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                break;
                
            case 3:
                // Create semifinal with one bye
                $shuffledPlayers = $players->shuffle();
                PoolMatch::create([
                    'match_name' => '3_SF_match',
                    'player_1_id' => $shuffledPlayers[0]->id,
                    'player_2_id' => $shuffledPlayers[1]->id,
                    'bye_player_id' => $shuffledPlayers[2]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => '3_SF',
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                break;
                
            case 4:
                // Create two first round matches
                $shuffledPlayers = $players->shuffle();
                
                // Debug: Log the exact values being passed to PoolMatch::create
                \Log::info("Creating match 4_R1_M1 with level_name = '{$levelName}' (length: " . strlen($levelName) . ")");
                
                PoolMatch::create([
                    'match_name' => '4_R1_M1',
                    'player_1_id' => $shuffledPlayers[0]->id,
                    'player_2_id' => $shuffledPlayers[1]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => 'round_1',
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDatesJson($tournament->id),
                ]);
                
                \Log::info("Creating match 4_R1_M2 with level_name = '{$levelName}' (length: " . strlen($levelName) . ")");
                
                PoolMatch::create([
                    'match_name' => '4_R1_M2',
                    'player_1_id' => $shuffledPlayers[2]->id,
                    'player_2_id' => $shuffledPlayers[3]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => 'round_1',
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
        if ($level !== 'community' && $level !== 'special') {
            // For county/regional/national ALL rounds, use smart pairing with previous group tracking
            $this->createSmartPairingMatches($tournament, $players, $level, $groupId, $roundName, $levelName);
        } else {
            // Random pairing with same-origin avoidance for community level
            $this->createRandomMatches($tournament, $players, $level, $groupId, $roundName, $levelName);
        }
    }

    /**
     * Create matches using smart pairing algorithm for next rounds
     */
    private function createSmartPairingMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, string $levelName)
    {
        \Log::info("Creating smart pairing matches for {$level} level, round: {$roundName}, players: {$players->count()}");
        
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
        
        // Create matches from paired players
        $matchNumber = 1;
        for ($i = 0; $i < count($pairedPlayers); $i += 2) {
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
        
        \Log::info("Created " . ($matchNumber - 1) . " smart pairing matches for {$level} level");
    }

    /**
     * Create matches based on player positions with cross-community avoidance
     */
    private function createPositionBasedMatchesForLevel(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName)
    {
        // Get winner records to know positions
        $winnerRecords = Winner::whereIn('player_id', $players->pluck('id'))
            ->where('tournament_id', $tournament->id)
            ->where('level', $this->getPreviousLevel($level))
            ->get()
            ->keyBy('player_id');
        
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
    private function createRandomMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, string $levelName = null)
    {
        if ($level === 'community') {
            // For community level, just pair randomly
            $levelName = $levelName ?? $this->getLevelName($level, $groupId);
            $this->pairPlayers($players, $tournament, $level, $groupId, $roundName, '', $levelName);
        } else {
            // For higher levels, avoid same community pairings
            $levelName = $this->getLevelName($level, $groupId);
            $this->createCommunityAwareMatches($players, $tournament, $level, $groupId, $roundName, '', $levelName);
        }
    }

    /**
     * Pair players and create matches with proper bye handling
     */
    private function pairPlayers(Collection $players, Tournament $tournament, string $level, $groupId, string $roundName, string $suffix = '', string $levelName = null)
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
        
        // Pick random loser and add to winners
        if ($losers->isNotEmpty()) {
            $promotedLoser = $losers->random();
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
        
        $latestRound = $query->orderBy('created_at', 'desc')->first();
        
        return $query->where('round_name', $latestRound->round_name)->get();
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
            default => throw new \Exception("No previous level for {$level}")
        };
    }

    /**
     * Send pairing notifications
     */
    private function sendPairingNotifications(Tournament $tournament, string $level)
    {
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('status', 'pending')
            ->with(['player1', 'player2'])
            ->get();
        
        foreach ($matches as $match) {
            // Propose available days for the match
            $this->proposeMatchDays($match);
            
            if ($match->player1) {
                Notification::create([
                    'player_id' => $match->player1->id,
                    'type' => 'pairing',
                    'message' => "You have been paired for a match in {$tournament->name}. Please select your available days.",
                    'data' => ['match_id' => $match->id]
                ]);
            }
            
            if ($match->player2) {
                Notification::create([
                    'player_id' => $match->player2->id,
                    'type' => 'pairing',
                    'message' => "You have been paired for a match in {$tournament->name}. Please select your available days.",
                    'data' => ['match_id' => $match->id]
                ]);
            }
            
            // Fire event for real-time notifications
            event(new MatchPairingCreated($match, "New match pairing created"));
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
                $this->determine3PlayerWinners($tournament, $level, $groupId);
                break;
                
            case 4:
                $this->determine4PlayerWinners($tournament, $level, $groupId);
                break;
                
            default:
                // Standard tournament - determine winners from final matches
                $this->determineStandardWinners($tournament, $level, $groupId);
                break;
        }
    }
    
    /**
     * Determine winners for 3-player tournament
     */
    private function determine3PlayerWinners(Tournament $tournament, string $level, ?int $groupId)
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
                // SF loser won the final - no tie-breaker needed, they already played SF winner
                // Position 1: SF winner (already beat the final winner in SF)
                Winner::create([
                    'player_id' => $sfWinner,
                    'position' => 1,
                    'level' => $level,
                    'level_id' => $groupId,
                    'tournament_id' => $tournament->id,
                ]);
                
                // Position 2: SF loser (who won the final)
                Winner::create([
                    'player_id' => $sfLoser,
                    'position' => 2,
                    'level' => $level,
                    'level_id' => $groupId,
                    'tournament_id' => $tournament->id,
                ]);
                
                // Position 3: Bye player (who lost the final)
                Winner::create([
                    'player_id' => $finalLoser,
                    'position' => 3,
                    'level' => $level,
                    'level_id' => $groupId,
                    'tournament_id' => $tournament->id,
                ]);
            }
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
            
        // For 4 players with round_1 matches, determine winners based on match results
        if ($matches->count() == 2 && $matches->every(fn($m) => $m->round_name == 'round_1')) {
            // Two semi-final matches, need to determine final ranking
            $match1 = $matches->first();
            $match2 = $matches->last();
            
            // Position 1 & 2: Winners of the two matches
            Winner::create([
                'player_id' => $match1->winner_id,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            Winner::create([
                'player_id' => $match2->winner_id,
                'position' => 2,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            // Position 3: One of the losers (pick first match loser)
            $loser1 = $match1->winner_id == $match1->player_1_id ? $match1->player_2_id : $match1->player_1_id;
            Winner::create([
                'player_id' => $loser1,
                'position' => 3,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
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
     * Determine winners for standard tournaments (5+ players)
     */
    private function determineStandardWinners(Tournament $tournament, string $level, ?int $groupId)
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
            $players = $tournament->registeredUsers()
                ->wherePivot('payment_status', 'paid')
                ->wherePivot('status', 'approved')
                ->get();

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
            $matchesCreated = $this->createSpecialTournamentMatches($tournament, $players);

            DB::commit();

            // Send notifications to all players
            $this->sendSpecialTournamentNotifications($tournament, $players);

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
     * Create matches for special tournament
     */
    private function createSpecialTournamentMatches(Tournament $tournament, $players): int
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
            PoolMatch::create([
                'tournament_id' => $tournament->id,
                'player_1_id' => $match['player1']['id'],
                'player_2_id' => $match['player2']['id'],
                'level' => 'special', // Use 'special' as the level
                'round_name' => 'Special Tournament Round 1',
                'match_name' => 'Special Match #' . ($matchesCreated + 1),
                'status' => 'pending',
                'group_id' => 1, // Single group for special tournaments
                'group_name' => 'Special Tournament',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            $matchesCreated++;
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

        if ($playerCount % 2 === 0) {
            // Even number of players - pair them up
            for ($i = 0; $i < $playerCount; $i += 2) {
                $matches[] = [
                    'player1' => $players[$i],
                    'player2' => $players[$i + 1]
                ];
            }
        } else {
            // Odd number of players - one player gets a bye (plays twice)
            for ($i = 0; $i < $playerCount - 1; $i += 2) {
                $matches[] = [
                    'player1' => $players[$i],
                    'player2' => $players[$i + 1]
                ];
            }
            
            // Last player gets paired with first player for second match
            if ($playerCount > 2) {
                $matches[] = [
                    'player1' => $players[$playerCount - 1],
                    'player2' => $players[0]
                ];
            }
        }

        return $matches;
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
}
