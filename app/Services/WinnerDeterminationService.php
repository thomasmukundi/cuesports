<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Winner;
use App\Services\TournamentUtilityService;
use App\Services\TournamentNotificationService;
use App\Services\ThreePlayerTournamentService;
use App\Services\FourPlayerTournamentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WinnerDeterminationService
{
    protected $threePlayerService;
    protected $fourPlayerService;

    public function __construct(
        ThreePlayerTournamentService $threePlayerService,
        FourPlayerTournamentService $fourPlayerService
    ) {
        $this->threePlayerService = $threePlayerService;
        $this->fourPlayerService = $fourPlayerService;
    }

    /**
     * Main entry point for winner determination (called from MatchAlgorithmService)
     */
    public function determineWinners(Tournament $tournament, string $level, ?int $groupId): array
    {
        Log::info("WinnerDeterminationService::determineWinners called", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'group_id' => $groupId
        ]);

        // Convert groupId to levelName for consistency
        $levelName = TournamentUtilityService::getLevelNameFromGroupId($level, $groupId);
        
        return $this->determineFinalPositions($tournament, $level, $levelName);
    }

    /**
     * Determine final positions for completed tournaments
     */
    public function determineFinalPositions(Tournament $tournament, string $level, ?string $levelName): array
    {
        Log::info("=== WINNER DETERMINATION: determineFinalPositions START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName
        ]);

        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // Check if positions already exist
        $existingPositions = Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_id', $groupId)
            ->exists();

        if ($existingPositions) {
            Log::info("Positions already exist for this tournament level");
            return ['status' => 'already_complete', 'message' => 'Final positions already determined'];
        }

        // Determine the tournament type and route to appropriate handler
        $tournamentType = $this->determineTournamentType($tournament, $level, $levelName, $groupId);
        
        Log::info("Tournament type determined", [
            'type' => $tournamentType['type'],
            'player_count' => $tournamentType['player_count']
        ]);

        return $this->routeToWinnerHandler($tournament, $level, $levelName, $groupId, $tournamentType);
    }

    /**
     * Determine tournament type based on completed matches
     */
    private function determineTournamentType(Tournament $tournament, string $level, ?string $levelName, ?int $groupId): array
    {
        // Get all completed matches for this tournament level
        $matchesQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('status', 'completed');
            
        if ($levelName) {
            $matchesQuery->where('level_name', $levelName);
        } else {
            $matchesQuery->whereNull('level_name');
        }
        
        if ($groupId) {
            $matchesQuery->where('group_id', $groupId);
        }
        
        $matches = $matchesQuery->get();
        
        // Analyze match patterns to determine tournament type
        $roundNames = $matches->pluck('round_name')->unique()->sort()->values();
        $totalPlayers = $this->getTotalPlayersInTournament($tournament, $level, $groupId);
        
        Log::info("Tournament analysis", [
            'total_matches' => $matches->count(),
            'round_names' => $roundNames->toArray(),
            'total_players' => $totalPlayers
        ]);

        // Determine type based on round patterns
        if ($roundNames->contains('2_final')) {
            return ['type' => '2_player', 'player_count' => 2, 'matches' => $matches];
        } elseif ($roundNames->contains('3_final') || $roundNames->contains('3_SF')) {
            return ['type' => '3_player', 'player_count' => 3, 'matches' => $matches];
        } elseif ($roundNames->contains('4_final') || $roundNames->contains('winners_final')) {
            return ['type' => '4_player', 'player_count' => 4, 'matches' => $matches];
        } elseif ($totalPlayers <= 4) {
            return ['type' => 'small_group', 'player_count' => $totalPlayers, 'matches' => $matches];
        } else {
            return ['type' => 'large_group', 'player_count' => $totalPlayers, 'matches' => $matches];
        }
    }

    /**
     * Route to appropriate winner determination handler
     */
    private function routeToWinnerHandler(Tournament $tournament, string $level, ?string $levelName, ?int $groupId, array $tournamentType): array
    {
        switch ($tournamentType['type']) {
            case '2_player':
                return $this->determine2PlayerWinners($tournament, $level, $groupId, $tournamentType['matches']);
                
            case '3_player':
                return $this->determine3PlayerWinners($tournament, $level, $levelName, $groupId, $tournamentType['matches']);
                
            case '4_player':
                return $this->determine4PlayerWinners($tournament, $level, $levelName, $groupId, $tournamentType['matches']);
                
            case 'small_group':
                return $this->determineSmallGroupWinners($tournament, $level, $groupId, $tournamentType);
                
            case 'large_group':
                return $this->determineLargeGroupWinners($tournament, $level, $groupId, $tournamentType);
                
            default:
                return ['status' => 'error', 'message' => 'Unknown tournament type'];
        }
    }

    /**
     * Determine winners for 2-player tournament
     */
    private function determine2PlayerWinners(Tournament $tournament, string $level, ?int $groupId, Collection $matches): array
    {
        $finalMatch = $matches->where('round_name', '2_final')->first();
        
        if (!$finalMatch) {
            return ['status' => 'error', 'message' => '2-player final match not found'];
        }

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

        // Send notifications
        $levelName = TournamentUtilityService::getLevelName($level, $groupId);
        TournamentNotificationService::sendPositionNotifications($tournament, $level, $levelName, [
            1 => $finalMatch->winner_id,
            2 => $loser
        ]);

        Log::info("2-player winners determined successfully");
        return ['status' => 'success', 'message' => '2-player winners determined'];
    }

    /**
     * Determine winners for 3-player tournament
     */
    private function determine3PlayerWinners(Tournament $tournament, string $level, ?string $levelName, ?int $groupId, Collection $matches): array
    {
        Log::info("Delegating 3-player winner determination to ThreePlayerTournamentService");
        
        // Delegate to specialized 3-player service
        $this->threePlayerService->handle3PlayerWinnersComplete($tournament, $level, $levelName, $groupId);
        
        return ['status' => 'success', 'message' => '3-player winners determined by specialized service'];
    }

    /**
     * Determine winners for 4-player tournament
     */
    private function determine4PlayerWinners(Tournament $tournament, string $level, ?string $levelName, ?int $groupId, Collection $matches): array
    {
        Log::info("Delegating 4-player winner determination to FourPlayerTournamentService");
        
        // Check if this is a standard 4-player tournament or needs final
        $finalMatch = $matches->where('round_name', '4_final')->first();
        
        if ($finalMatch) {
            // Full 4-player tournament with final
            $this->fourPlayerService->create4PlayerPositions($tournament, $level, $levelName);
        } else {
            // Standard semifinals only - direct position generation
            $winnersFinal = $matches->where('round_name', 'winners_final')->first();
            $losersSemifinal = $matches->where('round_name', 'losers_semifinal')->first();
            
            if ($winnersFinal && $losersSemifinal) {
                $this->fourPlayerService->generateStandard4PlayerPositions($tournament, $level, $levelName, $winnersFinal, $losersSemifinal);
            } else {
                return ['status' => 'error', 'message' => 'Required 4-player matches not found'];
            }
        }
        
        return ['status' => 'success', 'message' => '4-player winners determined by specialized service'];
    }

    /**
     * Determine winners for small group tournaments (1-4 players)
     */
    private function determineSmallGroupWinners(Tournament $tournament, string $level, ?int $groupId, array $tournamentType): array
    {
        $playerCount = $tournamentType['player_count'];
        $matches = $tournamentType['matches'];
        
        Log::info("Determining winners for small group", ['player_count' => $playerCount]);

        switch ($playerCount) {
            case 1:
                return $this->determineSinglePlayerWinner($tournament, $level, $groupId);
                
            case 2:
                return $this->determine2PlayerWinners($tournament, $level, $groupId, $matches);
                
            case 3:
                $levelName = TournamentUtilityService::getLevelName($level, $groupId);
                return $this->determine3PlayerWinners($tournament, $level, $levelName, $groupId, $matches);
                
            case 4:
                $levelName = TournamentUtilityService::getLevelName($level, $groupId);
                return $this->determine4PlayerWinners($tournament, $level, $levelName, $groupId, $matches);
                
            default:
                return ['status' => 'error', 'message' => 'Invalid small group size'];
        }
    }

    /**
     * Determine single player winner
     */
    private function determineSinglePlayerWinner(Tournament $tournament, string $level, ?int $groupId): array
    {
        // Get the single player
        $players = $this->getOriginalPlayersForTournament($tournament, $level, $groupId);
        $player = $players->first();
        
        if (!$player) {
            return ['status' => 'error', 'message' => 'No players found'];
        }

        Winner::create([
            'player_id' => $player->id,
            'position' => 1,
            'level' => $level,
            'level_id' => $groupId,
            'tournament_id' => $tournament->id,
        ]);

        // Send notifications
        $levelName = TournamentUtilityService::getLevelName($level, $groupId);
        TournamentNotificationService::sendPositionNotifications($tournament, $level, $levelName, [
            1 => $player->id
        ]);

        Log::info("Single player winner determined successfully");
        return ['status' => 'success', 'message' => 'Single player winner determined'];
    }

    /**
     * Determine winners for large group tournaments (5+ players)
     */
    private function determineLargeGroupWinners(Tournament $tournament, string $level, ?int $groupId, array $tournamentType): array
    {
        $matches = $tournamentType['matches'];
        $playerCount = $tournamentType['player_count'];
        
        Log::info("Determining winners for large group", [
            'player_count' => $playerCount,
            'match_count' => $matches->count()
        ]);

        // For large groups, use performance-based winner determination
        return $this->determineWinnersByPerformance($tournament, $level, $groupId, $matches);
    }

    /**
     * Determine winners by performance metrics (for large tournaments)
     */
    private function determineWinnersByPerformance(Tournament $tournament, string $level, ?int $groupId, Collection $matches): array
    {
        // Get all players who participated
        $allPlayerIds = collect();
        foreach ($matches as $match) {
            $allPlayerIds->push($match->player_1_id);
            $allPlayerIds->push($match->player_2_id);
        }
        $allPlayerIds = $allPlayerIds->unique();

        // Calculate performance metrics for each player
        $playerMetrics = [];
        foreach ($allPlayerIds as $playerId) {
            $playerMatches = $matches->where(function($match) use ($playerId) {
                return $match->player_1_id == $playerId || $match->player_2_id == $playerId;
            });

            $wins = $playerMatches->where('winner_id', $playerId)->count();
            $totalMatches = $playerMatches->count();
            $winRate = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;
            $totalPoints = $wins * 3; // 3 points per win

            $playerMetrics[$playerId] = [
                'player_id' => $playerId,
                'wins' => $wins,
                'matches_played' => $totalMatches,
                'win_rate' => $winRate,
                'total_points' => $totalPoints
            ];
        }

        // Sort by performance (total points, then win rate, then wins)
        $sortedPlayers = collect($playerMetrics)->sortByDesc(function($metrics) {
            return [$metrics['total_points'], $metrics['win_rate'], $metrics['wins']];
        })->values();

        // Create winner records
        $positions = [];
        foreach ($sortedPlayers as $index => $metrics) {
            $position = $index + 1;
            
            Winner::create([
                'player_id' => $metrics['player_id'],
                'position' => $position,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            $positions[$position] = $metrics['player_id'];
        }

        // Send notifications
        $levelName = TournamentUtilityService::getLevelName($level, $groupId);
        TournamentNotificationService::sendPositionNotifications($tournament, $level, $levelName, $positions);

        Log::info("Large group winners determined by performance", [
            'total_positions' => count($positions)
        ]);

        return ['status' => 'success', 'message' => 'Large group winners determined by performance'];
    }

    /**
     * Get total players in tournament for a specific level and group
     */
    private function getTotalPlayersInTournament(Tournament $tournament, string $level, ?int $groupId): int
    {
        return $this->getOriginalPlayersForTournament($tournament, $level, $groupId)->count();
    }

    /**
     * Get original players for a tournament level/group
     */
    private function getOriginalPlayersForTournament(Tournament $tournament, string $level, ?int $groupId): Collection
    {
        if ($level === 'community') {
            return $tournament->approvedPlayers->where('community_id', $groupId);
        } elseif ($level === 'special') {
            return $tournament->approvedPlayers;
        } else {
            // For county, regional, national levels, get winners from previous level
            $winners = Winner::where('tournament_id', $tournament->id)
                ->where('level', $this->getPreviousLevel($level))
                ->with('player')
                ->get()
                ->pluck('player');
                
            if ($groupId) {
                return $winners->where($this->getLevelColumn($level), $groupId);
            }
            
            return $winners;
        }
    }

    /**
     * Get previous level in tournament hierarchy
     */
    private function getPreviousLevel(string $level): string
    {
        return match($level) {
            'county' => 'community',
            'regional' => 'county',
            'national' => 'regional',
            'special' => throw new \Exception("Special tournaments don't have previous levels"),
            default => throw new \Exception("No previous level for {$level}")
        };
    }

    /**
     * Get the column name for filtering players by level
     */
    private function getLevelColumn(string $level): string
    {
        return TournamentUtilityService::getLevelColumn($level);
    }
}
