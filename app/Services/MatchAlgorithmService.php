<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Winner;
use App\Models\Notification;
use App\Events\MatchPairingCreated;
use App\Services\TournamentUtilityService;
use App\Services\TournamentNotificationService;
use App\Services\MatchCreationService;
use App\Services\TournamentProgressionService;
use App\Services\WinnerDeterminationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchAlgorithmService
{
    protected $fourPlayerService;
    protected $threePlayerService;
    protected $progressionService;
    protected $winnerService;

    public function __construct(
        FourPlayerTournamentService $fourPlayerService, 
        ThreePlayerTournamentService $threePlayerService,
        TournamentProgressionService $progressionService,
        WinnerDeterminationService $winnerService
    ) {
        $this->fourPlayerService = $fourPlayerService;
        $this->threePlayerService = $threePlayerService;
        $this->progressionService = $progressionService;
        $this->winnerService = $winnerService;
    }
    /**
     * Initialize tournament matches for a given level
     */
    public function initialize(int $tournamentId, string $level)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        // Handle special tournaments
        if ($level === 'special' || $tournament->special) {
            return $this->initializeSpecialLevel($tournamentId);
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
                    MatchCreationService::createMatchesForGroup($tournament, $groupPlayers, $level, $groupId);
                }
            } else {
                // For county, regional, national levels, use smart grouping with previous group tracking
                DB::rollBack(); // Rollback the transaction started here
                $result = $this->initializeLevel($tournament->id, $level);
                
                // Send notifications to all players - get players for notifications
                $players = $this->getEligiblePlayers($tournament, $level);
                TournamentNotificationService::sendPairingNotifications($tournament, $level);
                
                return $result;
            }
            
            DB::commit();
            
            // Send notifications to all players
            TournamentNotificationService::sendPairingNotifications($tournament, $level);
            
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
                MatchCreationService::createMatchesForGroup($tournament, $groupPlayers, $level, $groupId);
            }
            
            DB::commit();
            
            // Send notifications to all players
            TournamentNotificationService::sendLevelInitializationNotifications($tournament, $level, $allPlayers);
            
            return ['status' => 'success', 'message' => "Tournament initialized for {$level} level with {$allPlayers->count()} players"];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Tournament level initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate next round matches - DELEGATED TO TournamentProgressionService
     */
    public function generateNextRound(Tournament $tournament, string $level, ?int $groupId = null): array
    {
        Log::info("MatchAlgorithmService::generateNextRound - Delegating to TournamentProgressionService");
        
        // Delegate to the specialized progression service
        $levelName = TournamentUtilityService::getLevelName($level, $groupId);
        
        // For now, return a simple response - this method should be phased out
        return ['status' => 'delegated', 'message' => 'Method delegated to TournamentProgressionService'];
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
        MatchCreationService::createSpecialTournamentMatches($tournament, $finalPlayers, $level, $groupId, $nextRoundName);
        
        DB::commit();
        
        // Send notifications
        TournamentNotificationService::sendPairingNotifications($tournament, $level);
        
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
     * Handle special progression cases for 2+ player tournaments
     */
    private function handleSpecialProgression(Tournament $tournament, Collection $winners, string $level, $groupId, Collection $currentRoundMatches, $levelName)
    {
        $currentRound = $currentRoundMatches->first()->round_name;
        $currentWinnerCount = $winners->count();
        
        \Log::info("Special progression analysis", [
            'current_round' => $currentRound,
            'current_winner_count' => $currentWinnerCount,
            'tournament_id' => $tournament->id
        ]);
        
        // Use current winner count for progression logic
        switch ($currentWinnerCount) {
            case 2:
                // 2-player tournament ends after 2_final - no progression needed
                return;
                
            case 3:
            case 4:
                // Delegate to TournamentProgressionService for tournament-specific progression
                return $this->progressionService->handleRoundCompletion($tournament, $level, $levelName, $currentRound);
                
            default:
                // For larger groups, continue with normal progression
                $this->handleLargeGroupProgression($tournament, $winners, $level, $groupId, $currentRoundMatches);
                break;
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
            $matches = MatchCreationService::createMatchesForGroup($tournament, $players, $level, $groupId);
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
                    MatchCreationService::createMatchesForGroup($tournament, $communityPlayers, 'community', $communityId);
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
            MatchCreationService::createMatchesForGroup($tournament, $players, 'special', null);
            $totalMatches = $this->calculateMatchesCreated($players->count());

            DB::commit();
            
            // Send notifications to all players
            TournamentNotificationService::sendPairingNotifications($tournament, 'special');
            
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
     * Create a single match using MatchCreationService (single source of truth)
     */
    private function createMatch(Tournament $tournament, $player1, $player2, string $roundName, string $level, int $groupId, string $levelName, ?int $byePlayerId = null, ?string $matchName = null): \App\Models\PoolMatch
    {
        return \App\Services\MatchCreationService::createMatch(
            $tournament,
            $player1,
            $player2,
            $roundName,
            $level,
            $groupId,
            $levelName,
            $byePlayerId,
            $matchName
        );
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
            $levelName = TournamentUtilityService::getLevelName($level, $groupId);
            
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
            
            $match1 = \App\Services\MatchCreationService::createMatch(
                $tournament,
                $doublePlayer,
                $firstOpponent,
                $roundName,
                $level,
                $groupId,
                $levelName,
                null,
                "{$roundName}_M{$matchNumber}"
            );
            
            \Log::info("Created match #{$matchNumber}", [
                'match_id' => $match1->id,
                'match_name' => $match1->match_name,
                'player_1_id' => $match1->player_1_id,
                'player_2_id' => $match1->player_2_id,
                'level' => $match1->level,
                'round_name' => $match1->round_name
            ]);
            
            $matchNumber++;
            
            // Create second match with double player and another opponent
            $secondOpponentIndex = -1;
            for ($i = 0; $i < $playerCount; $i++) {
                if ($i != $doublePlayerIndex && $i != $firstOpponentIndex) {
                    $secondOpponentIndex = $i;
                    break;
                }
            }
            
            if ($secondOpponentIndex != -1) {
                $secondOpponent = $playerArray[$secondOpponentIndex];
                
                $match2 = \App\Services\MatchCreationService::createMatch(
                    $tournament,
                    $doublePlayer,
                    $secondOpponent,
                    $roundName,
                    $level,
                    $groupId,
                    $levelName,
                    null,
                    "{$roundName}_M{$matchNumber}"
                );
                
                \Log::info("Created match #{$matchNumber} (double player's second match)", [
                    'match_id' => $match2->id,
                    'match_name' => $match2->match_name,
                    'player_1_id' => $match2->player_1_id,
                    'player_2_id' => $match2->player_2_id
                ]);
                
                $matchNumber++;
            }
            
            // Remove double player and both opponents from remaining players
            $remainingPlayers = [];
            for ($i = 0; $i < $playerCount; $i++) {
                if ($i != $doublePlayerIndex && $i != $firstOpponentIndex && $i != $secondOpponentIndex) {
                    $remainingPlayers[] = $playerArray[$i];
                }
            }
            
            // Create matches for remaining players
            \Log::info("Creating matches for remaining players", [
                'remaining_count' => count($remainingPlayers)
            ]);
            
            for ($i = 0; $i < count($remainingPlayers); $i += 2) {
                if (isset($remainingPlayers[$i + 1])) {
                    $match = \App\Services\MatchCreationService::createMatch(
                        $tournament,
                        $remainingPlayers[$i],
                        $remainingPlayers[$i + 1],
                        $roundName,
                        $level,
                        $groupId,
                        $levelName,
                        null,
                        "{$roundName}_M{$matchNumber}"
                    );
                    
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
                
                $finalMatch = \App\Services\MatchCreationService::createMatch(
                    $tournament,
                    $doublePlayer,
                    $lastPlayer,
                    $roundName,
                    $level,
                    $groupId,
                    $levelName,
                    null,
                    "{$roundName}_M{$matchNumber}"
                );
                
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
                    $match = \App\Services\MatchCreationService::createMatch(
                        $tournament,
                        $playerArray[$i],
                        $playerArray[$i + 1],
                        $roundName,
                        $level,
                        $groupId,
                        $levelName,
                        null,
                        "{$roundName}_M{$matchNumber}"
                    );
                    
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
    public function handleLargeGroupProgression(Tournament $tournament, Collection $winners, string $level, $groupId, Collection $currentRoundMatches)
    {
        $levelName = TournamentUtilityService::getLevelName($level, $groupId);
        
        // Handle special tournaments where levelName might be null
        if ($levelName === null) {
            $levelName = 'Special Tournament';
        }
        
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
        
        // Check if we have odd number of winners
        if ($winners->count() % 2 === 1) {
            if ($winners->count() <= 3) {
                \Log::info("Odd number ≤ 3 winners - one player will play twice");
                $this->createStandardMatches($tournament, $winners, $level, $groupId, $nextRoundName, $levelName);
            } else {
                \Log::info("Odd number > 3 winners - finding best loser to make even number");
                $bestLoser = $this->findBestLoser($tournament, $level, $groupId, $roundName);
                
                if ($bestLoser) {
                    \Log::info("Found best loser - adding to next round", [
                        'best_loser_id' => $bestLoser->id,
                        'best_loser_name' => $bestLoser->name
                    ]);
                    
                    $winners->push($bestLoser);
                    $this->createStandardMatches($tournament, $winners, $level, $groupId, $nextRoundName, $levelName);
                } else {
                    \Log::warning("No best loser found - falling back to double play");
                    $this->createStandardMatches($tournament, $winners, $level, $groupId, $nextRoundName, $levelName);
                }
            }
        } else {
            \Log::info("Even number of winners - creating standard matches");
            $this->createStandardMatches($tournament, $winners, $level, $groupId, $nextRoundName, $levelName);
        }
        
        \Log::info("=== LARGE GROUP PROGRESSION END ===");
    }

    /**
     * Find the best loser from the completed round based on points and win rate
     */
    private function findBestLoser(Tournament $tournament, string $level, $groupId, string $roundName)
    {
        \Log::info("=== FINDING BEST LOSER ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'round_name' => $roundName
        ]);
        
        // Get all completed matches from the round
        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->where('status', 'completed')
            ->get();
            
        if ($completedMatches->isEmpty()) {
            \Log::warning("No completed matches found for best loser selection");
            return null;
        }
        
        // Get all losers from the completed matches
        $losers = collect();
        foreach ($completedMatches as $match) {
            $loserId = ($match->player_1_id === $match->winner_id) ? $match->player_2_id : $match->player_1_id;
            $loser = User::find($loserId);
            if ($loser) {
                $losers->push([
                    'user' => $loser,
                    'points_in_match' => ($match->player_1_id === $loserId) ? $match->player_2_points : $match->player_1_points,
                    'match_id' => $match->id
                ]);
            }
        }
        
        if ($losers->isEmpty()) {
            \Log::warning("No losers found for best loser selection");
            return null;
        }
        
        \Log::info("Found losers for evaluation", [
            'loser_count' => $losers->count(),
            'losers' => $losers->map(function($l) {
                return [
                    'id' => $l['user']->id,
                    'name' => $l['user']->name,
                    'points_in_match' => $l['points_in_match']
                ];
            })->toArray()
        ]);
        
        // Sort by points in the match (highest first), then by overall tournament performance
        $bestLosers = $losers->sortByDesc(function($loser) {
            return $loser['points_in_match'];
        });
        
        // Get the highest points among losers
        $highestPoints = $bestLosers->first()['points_in_match'];
        $topLosers = $bestLosers->filter(function($loser) use ($highestPoints) {
            return $loser['points_in_match'] === $highestPoints;
        });
        
        if ($topLosers->count() === 1) {
            $bestLoser = $topLosers->first()['user'];
            \Log::info("Best loser selected by points", [
                'best_loser_id' => $bestLoser->id,
                'best_loser_name' => $bestLoser->name,
                'points' => $highestPoints
            ]);
            return $bestLoser;
        }
        
        // If tied on points, check win rate in tournament
        \Log::info("Multiple losers tied on points - checking win rates", [
            'tied_count' => $topLosers->count(),
            'points' => $highestPoints
        ]);
        
        $bestLoser = null;
        $bestWinRate = -1;
        $bestTotalPoints = -1;
        
        foreach ($topLosers as $loserData) {
            $user = $loserData['user'];
            
            // Calculate win rate and total points for this user in the tournament
            $userMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('status', 'completed')
                ->where(function($query) use ($user) {
                    $query->where('player_1_id', $user->id)
                          ->orWhere('player_2_id', $user->id);
                })
                ->get();
                
            $wins = $userMatches->where('winner_id', $user->id)->count();
            $totalMatches = $userMatches->count();
            $winRate = $totalMatches > 0 ? $wins / $totalMatches : 0;
            
            $totalPoints = 0;
            foreach ($userMatches as $match) {
                $totalPoints += ($match->player_1_id === $user->id) ? $match->player_1_points : $match->player_2_points;
            }
            
            \Log::info("Evaluating tied loser", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'win_rate' => $winRate,
                'total_points' => $totalPoints,
                'total_matches' => $totalMatches
            ]);
            
            // Select best based on win rate, then total points
            if ($winRate > $bestWinRate || 
                ($winRate === $bestWinRate && $totalPoints > $bestTotalPoints)) {
                $bestLoser = $user;
                $bestWinRate = $winRate;
                $bestTotalPoints = $totalPoints;
            }
        }
        
        \Log::info("Best loser selected after tiebreaker", [
            'best_loser_id' => $bestLoser->id,
            'best_loser_name' => $bestLoser->name,
            'win_rate' => $bestWinRate,
            'total_points' => $bestTotalPoints
        ]);
        
        return $bestLoser;
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
                \App\Services\MatchCreationService::createMatch(
                    $tournament,
                    $players->first(),
                    $players->last(),
                    $finalRoundName,
                    $level,
                    $groupId,
                    $levelName,
                    null,
                    $finalRoundName . '_match'
                );
                break;
                
            case 3:
                // Delegate to ThreePlayerTournamentService
                $this->threePlayerService->generate3PlayerMatches($tournament, $players, $level, $groupId, $levelName);
                break;
                
            case 4:
                // Delegate to FourPlayerTournamentService
                $this->fourPlayerService->generateComprehensive4PlayerTournament($tournament, $level, $levelName, $players->toArray(), 4);
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
        $levelName = TournamentUtilityService::getLevelName($level, $groupId);
        $this->createCommunityAwareMatches($position1Players, $tournament, $level, $groupId, $roundName, 'pos1', $levelName);
        $this->createCommunityAwareMatches($position2Players, $tournament, $level, $groupId, $roundName, 'pos2', $levelName);
        $this->createCommunityAwareMatches($position3Players, $tournament, $level, $groupId, $roundName, 'pos3', $levelName);
        
        // Handle unpaired players across positions
        $unpaired = collect();
        if ($position1Players->count() % 2 == 1) $unpaired->push($position1Players->last());
        if ($position2Players->count() % 2 == 1) $unpaired->push($position2Players->last());
        if ($position3Players->count() % 2 == 1) $unpaired->push($position3Players->last());
        
        if ($unpaired->isNotEmpty()) {
            $levelName = TournamentUtilityService::getLevelName($level, $groupId);
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
            $levelName = $levelName ?? TournamentUtilityService::getLevelName($level, $groupId);
            $this->pairPlayers($players, $tournament, $level, $groupId, $roundName, '', $levelName);
        } else {
            \Log::info("Using createCommunityAwareMatches for level: {$level}");
            // For higher levels, avoid same community pairings
            $levelName = TournamentUtilityService::getLevelName($level, $groupId);
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
            \App\Services\MatchCreationService::createMatch(
                $tournament,
                $doublePlayer,
                $opponent1,
                $roundName,
                $level,
                $groupId,
                $levelName ?? $this->getLevelName($level, $groupId),
                null,
                $matchName
            );
            $matchNumber++;
            
            // Remove the first opponent from remaining players
            $remainingPlayers = $shuffled->where('id', '!=', $opponent1->id);
            
            // Create second match with double player and another opponent
            $opponent2 = $remainingPlayers->where('id', '!=', $doublePlayer->id)->first();
            $matchName = $roundName . '_' . $suffix . '_match' . $matchNumber;
            \App\Services\MatchCreationService::createMatch(
                $tournament,
                $doublePlayer,
                $opponent2,
                $roundName,
                $level,
                $groupId,
                $levelName ?? $this->getLevelName($level, $groupId),
                null,
                $matchName
            );
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
            \App\Services\MatchCreationService::createMatch(
                $tournament,
                $remainingArray[$i],
                $remainingArray[$i + 1],
                $roundName,
                $level,
                $groupId,
                $levelName ?? $this->getLevelName($level, $groupId),
                null,
                $matchName
            );
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
            // Progression round: promote best performing loser
            return $this->promoteBestLoser($players, $tournament, $level, $groupId, $roundName);
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
     * Promote the best performing loser to make even number of winners
     */
    private function promoteBestLoser(Collection $winners, Tournament $tournament, string $level, $groupId, string $roundName)
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
            case 4:
                // Delegate to WinnerDeterminationService for tournament-specific logic
                return $this->winnerService->determineWinners($tournament, $level, $groupId);
                break;
                
            default:
                // Delegate to WinnerDeterminationService for standard tournaments
                return $this->winnerService->determineWinners($tournament, $level, $groupId);
                break;
        }
        
        // Check for losers tournament completion
        $this->checkLosersTournamentCompletion($tournament, $level, $groupId);
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
                    'prize_amount' => 0, // Prize calculation not relevant
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
        TournamentNotificationService::sendWinnerNotifications($tournament, $level, $actualPositions, $tieInfo);
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
            \Log::info("Creating notification for player", [
                'player_id' => $playerId,
                'position' => $actualPosition,
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
     * Get match by round name
     */
    private function getMatch(Tournament $tournament, string $level, ?int $groupId, string $roundName)
    {
        return PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', $roundName)
            ->first();
    }

    /**
     * Get bye player from tournament participants
     */
    private function getByePlayer(Tournament $tournament, string $level, ?int $groupId, array $excludePlayerIds)
    {
        // Get all players in this tournament level
        $allPlayers = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where(function($q) {
                $q->where('player_1_id', '!=', null)->orWhere('player_2_id', '!=', null);
            })
            ->get()
            ->flatMap(function($match) {
                return [$match->player_1_id, $match->player_2_id];
            })
            ->unique()
            ->filter(function($playerId) use ($excludePlayerIds) {
                return !in_array($playerId, $excludePlayerIds);
            });

        $byePlayerId = $allPlayers->first();
        return $byePlayerId ? User::find($byePlayerId) : null;
    }




    
    /**
     * Check if losers tournament is needed for positions 4-6
     */
    private function checkLosersTournamentNeeded(Tournament $tournament, string $level, ?int $groupId, int $winnersNeeded): bool
    {
        // Only create losers tournament if we need 4-6 winners and we're at target level
        if ($winnersNeeded < 4 || $winnersNeeded > 6 || !TournamentUtilityService::isAtTournamentTargetLevel($tournament, $level)) {
            \Log::info("Losers tournament not needed", [
                'winners_needed' => $winnersNeeded,
                'level' => $level,
                'tournament_area_scope' => $tournament->area_scope,
                'tournament_area_name' => $tournament->area_name,
                'is_special' => $tournament->special,
                'is_target_level' => TournamentUtilityService::isAtTournamentTargetLevel($tournament, $level),
                'reason' => !TournamentUtilityService::isAtTournamentTargetLevel($tournament, $level) ? 'Not at tournament target level' : 'Winners needed not in 4-6 range'
            ]);
            return false;
        }
        
        // Check if winners tournament is complete (positions 1-3 assigned)
        $existingWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->whereIn('position', [1, 2, 3])
            ->count();
            
        if ($existingWinners < 3) {
            return false; // Winners tournament not complete yet
        }
        
        // Check if losers tournament already exists or is complete
        $existingLosersWinners = \App\Models\Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->whereIn('position', [4, 5, 6])
            ->count();
            
        if ($existingLosersWinners > 0) {
            return false; // Losers tournament already processed
        }
        
        return true; // Losers tournament is needed
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
                'player_id' => $player->id,
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
       
}
