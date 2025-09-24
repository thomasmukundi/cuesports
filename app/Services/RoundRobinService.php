<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoundRobinService
{
    /**
     * Check if round robin should be triggered based on remaining players and winners needed
     */
    public function shouldTriggerRoundRobin(Tournament $tournament, string $level, int $remainingPlayers): bool
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        \Log::info("Checking round robin trigger conditions", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'remaining_players' => $remainingPlayers,
            'winners_needed' => $winnersNeeded,
            'is_special' => $tournament->special ?? false
        ]);
        
        // Only trigger round robin if we need more than 3 winners
        if ($winnersNeeded <= 3) {
            \Log::info("Round robin not triggered: winners needed <= 3", [
                'winners_needed' => $winnersNeeded
            ]);
            return false;
        }
        
        // Check if we have the right number of players for round robin
        // Should trigger when remaining players are close to winners needed
        $shouldTriggerByPlayerCount = $remainingPlayers >= $winnersNeeded && 
                                     $remainingPlayers <= (2 * $winnersNeeded - 1);
        
        // For special tournaments, check at any level
        if ($tournament->special) {
            \Log::info("Special tournament - checking round robin trigger", [
                'should_trigger_by_count' => $shouldTriggerByPlayerCount,
                'remaining_players' => $remainingPlayers,
                'winners_needed' => $winnersNeeded,
                'max_players_for_rr' => (2 * $winnersNeeded - 1)
            ]);
            return $shouldTriggerByPlayerCount;
        }
        
        // For regular tournaments, check if we're at the tournament's area_scope level
        $tournamentAreaScope = $tournament->area_scope; // community, county, regional, national
        
        if ($level === $tournamentAreaScope) {
            \Log::info("Regular tournament at area_scope level - checking round robin trigger", [
                'tournament_area_scope' => $tournamentAreaScope,
                'current_level' => $level,
                'should_trigger_by_count' => $shouldTriggerByPlayerCount,
                'remaining_players' => $remainingPlayers,
                'winners_needed' => $winnersNeeded,
                'max_players_for_rr' => (2 * $winnersNeeded - 1)
            ]);
            return $shouldTriggerByPlayerCount;
        }
        
        \Log::info("Round robin not triggered: not special tournament and not at area_scope level", [
            'level' => $level,
            'tournament_area_scope' => $tournament->area_scope,
            'is_special' => $tournament->special ?? false
        ]);
        
        return false;
    }
    
    /**
     * Generate round robin matches for remaining players
     */
    public function generateRoundRobinMatches(Tournament $tournament, string $level, array $playerIds): array
    {
        Log::info("Generating round robin matches", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'players' => count($playerIds),
            'player_ids' => $playerIds
        ]);
        
        $roundName = $this->getRoundRobinRoundName($level);
        
        // Check if round robin matches already exist for this tournament and level
        $existingMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->count();
            
        if ($existingMatches > 0) {
            Log::info("Round robin matches already exist, skipping generation", [
                'tournament_id' => $tournament->id,
                'level' => $level,
                'existing_matches' => $existingMatches
            ]);
            return [];
        }
        
        $matches = [];
        
        // Generate all possible pairings (each player plays every other player once)
        for ($i = 0; $i < count($playerIds); $i++) {
            for ($j = $i + 1; $j < count($playerIds); $j++) {
                $match = PoolMatch::create([
                    'tournament_id' => $tournament->id,
                    'player_1_id' => $playerIds[$i],
                    'player_2_id' => $playerIds[$j],
                    'level' => $level,
                    'round_name' => $roundName,
                    'status' => 'pending',
                    'player_1_points' => null,
                    'player_2_points' => null,
                    'winner_id' => null,
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
                ]);
                
                $matches[] = $match;
            }
        }
        
        Log::info("Created round robin matches", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'matches_created' => count($matches)
        ]);
        
        return $matches;
    }
    
    /**
     * Calculate round robin standings and determine winners
     */
    public function calculateRoundRobinStandings(Tournament $tournament, string $level): array
    {
        $roundName = $this->getRoundRobinRoundName($level);
        $winnersNeeded = $tournament->winners ?? 3;
        
        // Get all round robin matches for this tournament and level
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->where('status', 'completed')
            ->get();
        
        if ($matches->isEmpty()) {
            Log::warning("No completed round robin matches found", [
                'tournament_id' => $tournament->id,
                'level' => $level
            ]);
            return [];
        }
        
        // Get all players in round robin
        $player1Ids = $matches->pluck('player_1_id');
        $player2Ids = $matches->pluck('player_2_id');
        $playerIds = $player1Ids->merge($player2Ids)->unique()->values()->toArray();
        $players = User::whereIn('id', $playerIds)->get()->keyBy('id');
        
        // Initialize standings
        $standings = [];
        foreach ($playerIds as $playerId) {
            $standings[$playerId] = [
                'player' => $players[$playerId],
                'wins' => 0,
                'losses' => 0,
                'total_points_scored' => 0,
                'total_points_conceded' => 0,
                'matches_played' => 0,
                'average_points' => 0
            ];
        }
        
        // Calculate stats from matches
        foreach ($matches as $match) {
            $player1Id = $match->player_1_id;
            $player2Id = $match->player_2_id;
            $player1Points = $match->player_1_points ?? 0;
            $player2Points = $match->player_2_points ?? 0;
            
            // Update match counts
            $standings[$player1Id]['matches_played']++;
            $standings[$player2Id]['matches_played']++;
            
            // Update points
            $standings[$player1Id]['total_points_scored'] += $player1Points;
            $standings[$player1Id]['total_points_conceded'] += $player2Points;
            $standings[$player2Id]['total_points_scored'] += $player2Points;
            $standings[$player2Id]['total_points_conceded'] += $player1Points;
            
            // Update wins/losses
            if ($player1Points > $player2Points) {
                $standings[$player1Id]['wins']++;
                $standings[$player2Id]['losses']++;
            } elseif ($player2Points > $player1Points) {
                $standings[$player2Id]['wins']++;
                $standings[$player1Id]['losses']++;
            }
        }
        
        // Calculate average points for tiebreaking
        foreach ($standings as $playerId => &$stats) {
            if ($stats['matches_played'] > 0) {
                $stats['average_points'] = $stats['total_points_scored'] / $stats['matches_played'];
            }
        }
        
        // Sort standings: 1) Most wins, 2) Highest average points, 3) Random for ties
        uasort($standings, function ($a, $b) {
            if ($a['wins'] !== $b['wins']) {
                return $b['wins'] <=> $a['wins']; // More wins first
            }
            if (abs($a['average_points'] - $b['average_points']) > 0.001) { // Avoid floating point precision issues
                return $b['average_points'] <=> $a['average_points']; // Higher average points first
            }
            // Random tiebreaker when wins and average points are equal
            return rand(-1, 1);
        });
        
        // Add positions
        $position = 1;
        foreach ($standings as $playerId => &$stats) {
            $stats['position'] = $position++;
        }
        
        Log::info("Round robin standings calculated", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'total_players' => count($standings),
            'winners_needed' => $winnersNeeded
        ]);
        
        return array_values($standings);
    }
    
    /**
     * Get winners from round robin standings and save to winners table
     */
    public function getRoundRobinWinners(Tournament $tournament, string $level): array
    {
        $standings = $this->calculateRoundRobinStandings($tournament, $level);
        $levelName = $this->getLevelName($tournament, $level);
        
        // Create winner records for ALL players (positions 1 to N)
        $winners = [];
        foreach ($standings as $standing) {
            $winner = \App\Models\Winner::create([
                'player_id' => $standing['player']['id'],
                'position' => $standing['position'],
                'points' => $standing['total_points_scored'], // Total points scored in round robin
                'level' => $level,
                'level_name' => $levelName,
                'level_id' => $this->getGroupIdForLevel($tournament, $level),
                'tournament_id' => $tournament->id,
            ]);
            
            $winners[] = [
                'player_id' => $standing['player']['id'],
                'position' => $standing['position'],
                'wins' => $standing['wins'],
                'points' => $standing['total_points_scored'],
                'average_points' => $standing['average_points'],
                'player_name' => $standing['player']['name']
            ];
        }
        
        Log::info("Round robin winners created in database", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'total_winners' => count($winners),
            'level_name' => $levelName
        ]);
        
        return $winners;
    }
    
    /**
     * Get level name for winner records
     */
    private function getLevelName(Tournament $tournament, string $level): string
    {
        switch ($level) {
            case 'community':
                return "Community Round Robin";
            case 'county':
                return "County Round Robin";
            case 'regional':
                return "Regional Round Robin";
            case 'national':
                return "National Round Robin";
            default:
                return ucfirst($level) . " Round Robin";
        }
    }
    
    /**
     * Get group ID for the level
     */
    private function getGroupIdForLevel(Tournament $tournament, string $level): int
    {
        // For round robin, we typically use a single group per level
        // This could be enhanced based on your specific grouping logic
        return 1;
    }
    
    /**
     * Check if all round robin matches are completed
     */
    public function areAllRoundRobinMatchesCompleted(Tournament $tournament, string $level): bool
    {
        $roundName = $this->getRoundRobinRoundName($level);
        
        $totalMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->count();
            
        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->where('status', 'completed')
            ->count();
            
        return $totalMatches > 0 && $totalMatches === $completedMatches;
    }
    
    /**
     * Get the round name for round robin matches
     */
    private function getRoundRobinRoundName(string $level): string
    {
        if ($level === 'special') {
            return 'Special Tournament Round Robin';
        }
        return ucfirst($level) . ' Round Robin';
    }
}
