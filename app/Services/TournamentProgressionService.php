<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Winner;
use App\Services\TournamentUtilityService;
use App\Services\TournamentNotificationService;
use App\Services\MatchCreationService;
use App\Services\ThreePlayerTournamentService;
use App\Services\FourPlayerTournamentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentProgressionService
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
     * Check if a round is complete and trigger next round generation
     */
    public function checkRoundCompletion(Tournament $tournament, string $level, ?string $levelName, string $roundName): array
    {
        Log::info("=== TOURNAMENT PROGRESSION: checkRoundCompletion START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => $roundName
        ]);

        if (!$tournament || $tournament->automation_mode !== 'automatic') {
            Log::warning("Tournament not in automatic mode", ['automation_mode' => $tournament->automation_mode ?? 'null']);
            return ['status' => 'error', 'message' => 'Tournament not in automatic mode'];
        }

        // Check if all matches in this round are completed
        $totalMatchesQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName);
            
        if ($levelName) {
            $totalMatchesQuery->where('level_name', $levelName);
        } else {
            $totalMatchesQuery->whereNull('level_name');
        }
        
        $totalMatches = $totalMatchesQuery->count();
        $completedMatches = $totalMatchesQuery->where('status', 'completed')->count();
        
        Log::info("Round completion check", [
            'total_matches' => $totalMatches,
            'completed_matches' => $completedMatches,
            'round_name' => $roundName
        ]);

        if ($totalMatches === 0) {
            return ['status' => 'error', 'message' => 'No matches found for this round'];
        }

        if ($completedMatches < $totalMatches) {
            return ['status' => 'pending', 'message' => 'Round not yet complete', 'progress' => "{$completedMatches}/{$totalMatches}"];
        }

        // Round is complete, determine next action based on round type
        return $this->handleCompletedRound($tournament, $level, $levelName, $roundName);
    }

    /**
     * Handle completed round and determine next action
     */
    private function handleCompletedRound(Tournament $tournament, string $level, ?string $levelName, string $roundName): array
    {
        Log::info("Handling completed round", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'round_name' => $roundName
        ]);

        // Get completed matches for this round
        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->where('status', 'completed');
            
        if ($levelName) {
            $completedMatches->where('level_name', $levelName);
        } else {
            $completedMatches->whereNull('level_name');
        }
        
        $matches = $completedMatches->get();
        $winnerCount = $matches->count();

        Log::info("Processing completed round", [
            'round_name' => $roundName,
            'winner_count' => $winnerCount,
            'tournament_id' => $tournament->id
        ]);

        // Route to appropriate handler based on winner count and round type
        return $this->routeToTournamentHandler($tournament, $level, $levelName, $roundName, $matches, $winnerCount);
    }

    /**
     * Route to appropriate tournament handler based on player count
     */
    private function routeToTournamentHandler(Tournament $tournament, string $level, ?string $levelName, string $roundName, Collection $matches, int $winnerCount): array
    {
        // Check if this is a 3-player tournament round - handle specially regardless of winner count
        $threePlayerRounds = ['3_SF', '3_final', '3_tie_breaker', '3_fair_chance', 'losers_3_SF', 'losers_3_final', 'losers_3_tie_breaker', 'losers_3_fair_chance'];
        
        if (in_array($roundName, $threePlayerRounds)) {
            Log::info("Processing 3-player tournament round", [
                'round_name' => $roundName,
                'winner_count' => $winnerCount
            ]);
            return $this->threePlayerService->check3PlayerTournamentProgression($tournament, $level, $levelName, $roundName);
        }

        switch ($winnerCount) {
            case 1:
                Log::info("Processing 1 winner scenario");
                // Single winner - tournament complete for this level
                return $this->handleSingleWinner($tournament, $level, $levelName, $matches->first());
                
            case 2:
                Log::info("Processing 2 winner scenario");
                return $this->handleTwoWinners($tournament, $level, $levelName, $roundName, $matches);
                
            case 3:
                Log::info("Processing 3 winner scenario");
                return $this->handleThreeWinners($tournament, $level, $levelName, $roundName, $matches);
                
            case 4:
                Log::info("Processing 4 winner scenario");
                return $this->handleFourWinners($tournament, $level, $levelName, $roundName, $matches);
                
            default:
                Log::info("Processing large winner count scenario", ['winner_count' => $winnerCount]);
                return $this->handleLargeWinnerCount($tournament, $level, $levelName, $roundName, $matches, $winnerCount);
        }
    }

    /**
     * Handle single winner scenario
     */
    private function handleSingleWinner(Tournament $tournament, string $level, ?string $levelName, PoolMatch $match): array
    {
        // Create winner record
        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $match->winner_id,
            'position' => 1,
            'level' => $level,
            'level_id' => $groupId,
        ]);

        // Send notifications
        TournamentNotificationService::sendPositionNotifications($tournament, $level, $levelName, [
            1 => $match->winner_id
        ]);

        return ['status' => 'complete', 'message' => 'Tournament complete with single winner'];
    }

    /**
     * Handle two winners scenario
     */
    private function handleTwoWinners(Tournament $tournament, string $level, ?string $levelName, string $roundName, Collection $matches): array
    {
        // Check if this is part of comprehensive semifinals
        if ($roundName === 'SF_winners' || $roundName === 'SF_losers' || $roundName === 'losers_SF_winners' || $roundName === 'losers_SF_losers') {
            Log::info("Two comprehensive semifinals complete - checking if all semifinals done");
            return $this->checkComprehensiveSemifinalsComplete($tournament, $level, $levelName);
        } elseif (strpos($roundName, 'round1') !== false || strpos($roundName, '4player_round1') !== false) {
            Log::info("Generating semifinals from first round with 2 winners");
            $this->fourPlayerService->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
            return ['status' => 'success', 'message' => '4-player semifinals generated'];
        } elseif ($roundName === 'semifinal' || strpos($roundName, 'SF') !== false) {
            Log::info("Generating final from semifinals");
            $this->fourPlayerService->generate4PlayerFinal($tournament, $level, $levelName);
            return ['status' => 'success', 'message' => '4-player final generated'];
        } else {
            Log::info("Generating 4-player semifinals");
            $this->fourPlayerService->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
            return ['status' => 'success', 'message' => '4-player semifinals generated'];
        }
    }

    /**
     * Handle three winners scenario
     */
    private function handleThreeWinners(Tournament $tournament, string $level, ?string $levelName, string $roundName, Collection $matches): array
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        // Check if this is part of comprehensive semifinals
        if ($roundName === 'SF_winners' || $roundName === 'SF_losers' || $roundName === 'losers_SF_winners' || $roundName === 'losers_SF_losers') {
            Log::info("Three comprehensive semifinals complete - checking if all semifinals done");
            return $this->checkComprehensiveSemifinalsComplete($tournament, $level, $levelName);
        } elseif ($winnersNeeded == 3) {
            Log::info("3 winners needed - using 3-player service");
            return $this->threePlayerService->check3PlayerTournamentProgression($tournament, $level, $levelName, $roundName);
        } else {
            Log::info("More than 3 winners needed - creating comprehensive 3-player tournament");
            
            // Get the 3 winners from the completed matches
            $winners = [];
            foreach ($matches as $match) {
                if ($match->winner_id) {
                    $winners[] = $match->winner_id;
                }
            }
            
            if (count($winners) !== 3) {
                Log::error("Expected 3 winners but found " . count($winners));
                return ['status' => 'error', 'message' => 'Invalid winner count for comprehensive tournament'];
            }
            
            // Create comprehensive tournament (both winners and losers sides)
            $this->threePlayerService->generateComprehensive3PlayerTournament(
                $tournament, 
                $level, 
                $levelName, 
                $winners, 
                $winnersNeeded
            );
            
            return ['status' => 'success', 'message' => 'Comprehensive 3-player tournament created'];
        }
    }

    /**
     * Handle four winners scenario
     */
    private function handleFourWinners(Tournament $tournament, string $level, ?string $levelName, string $roundName, Collection $matches): array
    {
        if ($roundName === 'winners_final' || $roundName === 'losers_semifinal') {
            // Check if we need 4 winners - if so, generate positions directly from semifinals
            $winnersNeeded = $tournament->winners ?? 3;
            if ($winnersNeeded == 4) {
                Log::info("4 winners needed - checking if both semifinals complete for direct position generation");
                return $this->checkTwoSemifinalsComplete($tournament, $level, $levelName);
            } else {
                Log::info("More than 4 winners needed - generating 4-player final");
                $this->fourPlayerService->generate4PlayerFinal($tournament, $level, $levelName);
                return ['status' => 'success', 'message' => '4-player final generated'];
            }
        } elseif ($roundName === 'SF_winners' || $roundName === 'SF_losers' || $roundName === 'losers_SF_winners' || $roundName === 'losers_SF_losers') {
            Log::info("Comprehensive semifinal complete - checking if all semifinals done");
            return $this->checkComprehensiveSemifinalsComplete($tournament, $level, $levelName);
        } else {
            // Always delegate to FourPlayerTournamentService for proper progression handling
            Log::info("Using 4-player progression service for winner-count-based logic");
            return $this->fourPlayerService->check4PlayerTournamentProgression($tournament, $level, $levelName, $roundName);
        }
    }

    /**
     * Handle large winner count scenario (5+ winners)
     */
    private function handleLargeWinnerCount(Tournament $tournament, string $level, ?string $levelName, string $roundName, Collection $matches, int $winnerCount): array
    {
        if ($winnerCount >= 5) {
            Log::info("Processing large winner count scenario", [
                'winner_count' => $winnerCount,
                'round_name' => $roundName
            ]);
            
            // For large groups, continue with next round generation
            return $this->generateNextRoundForLargeGroup($tournament, $level, $levelName, $matches);
        }
        
        return ['status' => 'error', 'message' => 'Unhandled winner count scenario'];
    }

    /**
     * Generate next round for large groups
     */
    private function generateNextRoundForLargeGroup(Tournament $tournament, string $level, ?string $levelName, Collection $matches): array
    {
        // Get winners from completed matches
        $winners = collect();
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $winner = User::find($match->winner_id);
                if ($winner) {
                    $winners->push($winner);
                }
            }
        }

        $groupId = TournamentUtilityService::getGroupIdFromLevelName($level, $levelName);
        
        // Create matches for next round using MatchCreationService
        MatchCreationService::createMatchesForGroup($tournament, $winners, $level, $groupId);
        
        // Send notifications
        TournamentNotificationService::sendPairingNotifications($tournament, $level);
        
        return ['status' => 'success', 'message' => 'Next round generated for large group'];
    }

    /**
     * Check if comprehensive semifinals are complete
     */
    private function checkComprehensiveSemifinalsComplete(Tournament $tournament, string $level, ?string $levelName): array
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        if ($winnersNeeded == 5 || $winnersNeeded == 6) {
            return $this->checkThreeSemifinalsComplete($tournament, $level, $levelName);
        } else {
            return $this->checkTwoSemifinalsComplete($tournament, $level, $levelName);
        }
    }

    /**
     * Check if three semifinals are complete (for 5-6 winners)
     */
    private function checkThreeSemifinalsComplete(Tournament $tournament, string $level, ?string $levelName): array
    {
        Log::info("Checking three semifinals completion for 5-6 winners");
        
        $winnersNeeded = $tournament->winners ?? 3;
        
        // We have 3 winners, need 5-6 winners, so we need to create losers tournament
        if ($winnersNeeded >= 5 && $winnersNeeded <= 6) {
            Log::info("Creating 3-player losers tournament for additional winners", [
                'current_winners' => 3,
                'winners_needed' => $winnersNeeded,
                'additional_needed' => $winnersNeeded - 3
            ]);
            
            // Use ThreePlayerTournamentService to create the losers tournament
            return $this->threePlayerService->createLosers3PlayerTournamentForProgression(
                $tournament, 
                $level, 
                null, // groupId 
                $winnersNeeded
            );
        }
        
        Log::warning("Unexpected winners needed count for three semifinals", [
            'winners_needed' => $winnersNeeded
        ]);
        
        return ['status' => 'error', 'message' => 'Unexpected winners configuration'];
    }

    /**
     * Check if two semifinals are complete (for 3-4 winners)
     */
    private function checkTwoSemifinalsComplete(Tournament $tournament, string $level, ?string $levelName): array
    {
        // Check if 2 semifinals are completed (for 3-4 winners)
        $winnersFinalQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'winners_final')
            ->where('status', 'completed');
            
        $losersSemifinalQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'losers_semifinal')
            ->where('status', 'completed');
        
        if ($levelName) {
            $winnersFinalQuery->where('level_name', $levelName);
            $losersSemifinalQuery->where('level_name', $levelName);
        } else {
            $winnersFinalQuery->whereNull('level_name');
            $losersSemifinalQuery->whereNull('level_name');
        }
        
        $winnersFinal = $winnersFinalQuery->first();
        $losersSemifinal = $losersSemifinalQuery->first();
        
        if ($winnersFinal && $losersSemifinal) {
            // Check how many winners are needed and generate positions accordingly
            $winnersNeeded = $tournament->winners ?? 3;
            if ($winnersNeeded == 3) {
                Log::info("All 2 standard semifinals complete - generating 3 positions directly (no final needed)");
                $this->threePlayerService->generateStandard3PlayerPositions($tournament, $level, $levelName, $winnersFinal, $losersSemifinal);
                return ['status' => 'complete', 'message' => '3 positions generated from semifinals'];
            } elseif ($winnersNeeded == 4) {
                Log::info("All 2 standard semifinals complete - generating 4 positions directly (no final needed)");
                $this->fourPlayerService->generateStandard4PlayerPositions($tournament, $level, $levelName, $winnersFinal, $losersSemifinal);
                return ['status' => 'complete', 'message' => '4 positions generated from semifinals'];
            } else {
                Log::info("All 2 standard semifinals complete - generating 4-player final for more than 4 winners");
                $this->fourPlayerService->generate4PlayerFinal($tournament, $level, $levelName);
                return ['status' => 'success', 'message' => '4-player final generated'];
            }
        } else {
            Log::info("Not all 2 standard semifinals complete yet", [
                'winners_final_complete' => !!$winnersFinal,
                'losers_semifinal_complete' => !!$losersSemifinal
            ]);
            return ['status' => 'pending', 'message' => 'Waiting for both semifinals to complete'];
        }
    }

    /**
     * Generate comprehensive semifinals for 5-6 winners
     */
    private function generateComprehensiveSemifinals(Tournament $tournament, string $level, ?string $levelName, Collection $matches): array
    {
        Log::info("Generating comprehensive semifinals for 5-6 winners");
        
        // Delegate to FourPlayerTournamentService for proper semifinal creation
        $fourPlayerService = new \App\Services\FourPlayerTournamentService();
        $fourPlayerService->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
        
        return ['status' => 'success', 'message' => 'Comprehensive semifinals generated'];
    }
}
