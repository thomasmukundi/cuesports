<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PoolMatch;
use App\Models\Tournament;
use App\Models\Winner;
use App\Services\MatchAlgorithmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TournamentProgressionController extends Controller
{
    protected $matchService;

    public function __construct(MatchAlgorithmService $matchService)
    {
        $this->matchService = $matchService;
    }

    /**
     * Check if a round is complete and trigger next round generation
     */
    public function checkRoundCompletion(Request $request)
    {
        \Log::info("=== TOURNAMENT PROGRESSION: checkRoundCompletion START ===", [
            'request_data' => $request->all()
        ]);
        
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'level' => 'required|string',
            'level_name' => 'nullable|string',
            'round_name' => 'required|string',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        \Log::info("Tournament found", ['tournament_id' => $tournament->id, 'automation_mode' => $tournament->automation_mode]);
        
        if (!$tournament || $tournament->automation_mode !== 'automatic') {
            \Log::warning("Tournament not in automatic mode", ['automation_mode' => $tournament->automation_mode ?? 'null']);
            return response()->json(['message' => 'Tournament not in automatic mode'], 400);
        }

        // Check if all matches in this round are completed
        $totalMatchesQuery = PoolMatch::where('tournament_id', $request->tournament_id)
            ->where('level', $request->level)
            ->where('round_name', $request->round_name);
            
        if ($request->level_name) {
            $totalMatchesQuery->where('level_name', $request->level_name);
        } else {
            $totalMatchesQuery->whereNull('level_name');
        }
        
        $totalMatches = $totalMatchesQuery->count();

        $completedMatchesQuery = PoolMatch::where('tournament_id', $request->tournament_id)
            ->where('level', $request->level)
            ->where('round_name', $request->round_name)
            ->where('status', 'completed');
            
        if ($request->level_name) {
            $completedMatchesQuery->where('level_name', $request->level_name);
        } else {
            $completedMatchesQuery->whereNull('level_name');
        }
        
        $completedMatches = $completedMatchesQuery->count();

        \Log::info("Round completion check", [
            'total_matches' => $totalMatches,
            'completed_matches' => $completedMatches,
            'round_complete' => $completedMatches >= $totalMatches && $totalMatches > 0
        ]);

        if ($completedMatches < $totalMatches) {
            \Log::info("Round not complete yet", [
                'completed' => $completedMatches,
                'total' => $totalMatches
            ]);
            return response()->json([
                'round_complete' => false,
                'completed_matches' => $completedMatches,
                'total_matches' => $totalMatches,
                'message' => 'Round not yet complete'
            ]);
        }

        // Round is complete - generate next round
        \Log::info("Round is complete, generating next round...");
        try {
            $this->generateNextRound($tournament, $request->level, $request->level_name, $request->round_name);
            
            \Log::info("Next round generation successful");
            return response()->json([
                'round_complete' => true,
                'next_round_generated' => true,
                'message' => 'Next round generated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error("Next round generation failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'round_complete' => true,
                'next_round_generated' => false,
                'message' => 'Failed to generate next round: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine final positions for completed tournaments
     */
    public function determineFinalPositions(Request $request)
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'level' => 'required|string',
            'level_name' => 'nullable|string',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        
        // Check for existing positions first to avoid duplicates
        $existingPositionsQuery = Winner::where('tournament_id', $request->tournament_id)
            ->where('level', $request->level);
            
        if ($request->level_name) {
            $existingPositionsQuery->where('level_name', $request->level_name);
        } else {
            $existingPositionsQuery->whereNull('level_name');
        }
        
        $existingPositions = $existingPositionsQuery->exists();
            
        if ($existingPositions) {
            return response()->json([
                'positions_created' => false,
                'message' => 'Positions already exist for this tournament level'
            ]);
        }
        
        // Check for 1-player scenario (automatic winner created during initialization)
        $singleWinnerQuery = Winner::where('tournament_id', $request->tournament_id)
            ->where('level', $request->level)
            ->where('position', 1);
            
        if ($request->level_name) {
            $singleWinnerQuery->where('level_name', $request->level_name);
        } else {
            $singleWinnerQuery->whereNull('level_name');
        }
        
        $singleWinner = $singleWinnerQuery->first();
            
        if ($singleWinner) {
            return response()->json([
                'positions_created' => true,
                'final_round' => '1_player',
                'message' => 'Single player tournament - positions already determined'
            ]);
        }
        
        // Check for tie-breaker match first (highest priority)
        $tieBreakerMatchQuery = PoolMatch::where('tournament_id', $request->tournament_id)
            ->where('level', $request->level)
            ->where('round_name', '3_break_tie_final')
            ->where('status', 'completed');
            
        if ($request->level_name) {
            $tieBreakerMatchQuery->where('level_name', $request->level_name);
        } else {
            $tieBreakerMatchQuery->whereNull('level_name');
        }
        
        $tieBreakerMatch = $tieBreakerMatchQuery->first();
            
        if ($tieBreakerMatch) {
            \Log::info("Tie-breaker match completed, creating final positions");
            $this->createPositionsFromFinalRound($tournament, $request->level, $request->level_name, '3_break_tie_final');
            
            return response()->json([
                'positions_created' => true,
                'final_round' => '3_break_tie_final',
                'message' => 'Final positions determined from tie-breaker match'
            ]);
        }
        
        // Check final rounds in order of complexity
        $finalRounds = ['4_final', '3_final', '2_final'];
        
        foreach ($finalRounds as $finalRound) {
            $finalMatchQuery = PoolMatch::where('tournament_id', $request->tournament_id)
                ->where('level', $request->level)
                ->where('round_name', $finalRound)
                ->where('status', 'completed');
                
            if ($request->level_name) {
                $finalMatchQuery->where('level_name', $request->level_name);
            } else {
                $finalMatchQuery->whereNull('level_name');
            }
            
            $finalMatch = $finalMatchQuery->first();

            if ($finalMatch) {
                // For 3_final, check if tie-breaker is needed and generate it
                if ($finalRound === '3_final') {
                    $tieBreakerNeeded = $this->checkIfTieBreakerNeeded($request->tournament_id, $request->level, $request->level_name);
                    if ($tieBreakerNeeded) {
                        \Log::info("3_final completed and tie-breaker needed - generating tie-breaker match");
                        $this->generateTieBreakerMatch($request->tournament_id, $request->level, $request->level_name);
                        return response()->json([
                            'positions_created' => false,
                            'final_round' => $finalRound,
                            'tie_breaker_generated' => true,
                            'message' => 'Tie-breaker match generated - waiting for completion before final positions'
                        ]);
                    }
                }
                
                $this->createPositionsFromFinalRound($tournament, $request->level, $request->level_name, $finalRound);
                
                return response()->json([
                    'positions_created' => true,
                    'final_round' => $finalRound,
                    'message' => 'Final positions determined successfully'
                ]);
            }
        }

        return response()->json([
            'positions_created' => false,
            'message' => 'No final rounds completed yet'
        ]);
    }

    /**
     * Generate next round based on current round completion
     */
    private function generateNextRound(Tournament $tournament, string $level, ?string $levelName, string $roundName)
    {
        \Log::info("=== GENERATE NEXT ROUND START ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => $roundName
        ]);
        
        // Get all completed matches in this round
        $matchesQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->where('status', 'completed');
            
        if ($levelName) {
            $matchesQuery->where('level_name', $levelName);
        } else {
            $matchesQuery->whereNull('level_name');
        }
        
        $matches = $matchesQuery->get();
            
        \Log::info("Found completed matches", [
            'match_count' => $matches->count(),
            'match_ids' => $matches->pluck('id')->toArray()
        ]);

        // Count actual unique winners from current round
        $winners = $matches->pluck('winner_id')->filter()->unique();
        $winnerCount = $winners->count();

        \Log::info("Analyzing winner count and round", [
            'winner_count' => $winnerCount,
            'round_name' => $roundName,
            'winners' => $winners->toArray(),
            'total_matches' => $matches->count()
        ]);

        // Determine if this is the first round (various naming patterns)
        $isFirstRound = in_array($roundName, [
            'round_1', 
            'Special Tournament Round 1',
            ucfirst($level) . ' Tournament Round 1',
            $level . '_round_1'
        ]);
        
        // Handle different scenarios based on winner count and current round
        switch ($winnerCount) {
            case 1:
                // Check if this is part of comprehensive semifinals
                if ($roundName === 'SF_winners' || $roundName === 'SF_losers' || $roundName === 'losers_SF_winners' || $roundName === 'losers_SF_losers') {
                    \Log::info("Single comprehensive semifinal complete - checking if all semifinals done");
                    $this->checkComprehensiveSemifinalsComplete($tournament, $level, $levelName);
                } elseif ($roundName === '3_winners_SF' || $roundName === 'losers_3_SF' || 
                         $roundName === '3_winners_final' || $roundName === 'losers_3_final' ||
                         $roundName === '3_winners_tie_breaker' || $roundName === 'losers_3_tie_breaker' ||
                         $roundName === '3_winners_fair_chance' || $roundName === 'losers_3_fair_chance') {
                    \Log::info("3-player tournament match complete - checking progression", [
                        'round_name' => $roundName,
                        'tournament_id' => $tournament->id
                    ]);
                    $this->check3PlayerTournamentProgression($tournament, $level, $levelName, $roundName);
                } else {
                    \Log::info("1 winner but not in comprehensive semifinals - no action needed", [
                        'winner_count' => $winnerCount,
                        'round_name' => $roundName
                    ]);
                    \Log::info("Processing 1 winner scenario");
                }
                break;
                
            case 2:
                \Log::info("Processing 2 winner scenario");
                // Check if this is part of comprehensive semifinals
                if ($roundName === 'SF_winners' || $roundName === 'SF_losers' || $roundName === 'losers_SF_winners' || $roundName === 'losers_SF_losers') {
                    \Log::info("Two comprehensive semifinals complete - checking if all semifinals done");
                    $this->checkComprehensiveSemifinalsComplete($tournament, $level, $levelName);
                } elseif ($isFirstRound) {
                    \Log::info("Generating semifinals from first round with 2 winners");
                    $this->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
                    \Log::info("4-player semifinals generated successfully");
                } elseif ($roundName === 'semifinal' || strpos($roundName, 'SF') !== false) {
                    \Log::info("Generating final from semifinals");
                    $this->generate4PlayerFinal($tournament, $level, $levelName);
                } else {
                    \Log::info("Generating 4-player semifinals");
                    $this->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
                }
                break;
                
            case 3:
                \Log::info("Processing 3 winner scenario from round");
                $winnersNeeded = $tournament->winners ?? 3;
                
                // Check if this is part of comprehensive semifinals
                if ($roundName === 'SF_winners' || $roundName === 'SF_losers' || $roundName === 'losers_SF_winners' || $roundName === 'losers_SF_losers') {
                    \Log::info("Three comprehensive semifinals complete - checking if all semifinals done");
                    $this->checkComprehensiveSemifinalsComplete($tournament, $level, $levelName);
                } elseif ($roundName === 'winners_final' || $roundName === 'losers_semifinal') {
                    // Check if we need exactly 3 winners - if so, generate positions directly from semifinals
                    if ($winnersNeeded == 3) {
                        \Log::info("3 winners needed - checking if both semifinals complete for direct position generation");
                        $this->checkTwoSemifinalsCompleteFor3Winners($tournament, $level, $levelName);
                    } else {
                        \Log::info("More than 3 winners needed - using standard progression");
                        // Handle other cases if needed
                    }
                } else {
                    // Only use 3-winner semifinal logic if we actually need exactly 3 winners
                    if ($winnersNeeded == 3) {
                        \Log::info("Generating 3-winner semifinal match (need exactly 3 winners)", [
                            'round_name' => $roundName,
                            'is_first_round' => $isFirstRound,
                            'winner_count' => $winnerCount,
                            'winners_needed' => $winnersNeeded
                        ]);
                        $this->generate3WinnerSemifinal($tournament, $level, $levelName, $matches);
                    } else {
                        \Log::info("Need more than 3 winners - using comprehensive tournament logic", [
                            'winners_needed' => $winnersNeeded,
                            'winner_count' => $winnerCount
                        ]);
                        // Use comprehensive logic for 4+ winners
                        $this->generateComprehensiveTournament($tournament, $level, $levelName, $matches, $winnersNeeded);
                    }
                }
                break;
                
            case 4:
                \Log::info("Processing 4 winner scenario");
                if ($roundName === 'winners_final' || $roundName === 'losers_semifinal') {
                    // Check if we need 4 winners - if so, generate positions directly from semifinals
                    $winnersNeeded = $tournament->winners ?? 3;
                    if ($winnersNeeded == 4) {
                        \Log::info("4 winners needed - checking if both semifinals complete for direct position generation");
                        $this->checkTwoSemifinalsComplete($tournament, $level, $levelName);
                    } else {
                        \Log::info("More than 4 winners needed - generating 4-player final");
                        $this->generate4PlayerFinal($tournament, $level, $levelName);
                    }
                } elseif ($roundName === 'SF_winners' || $roundName === 'SF_losers' || $roundName === 'losers_SF_winners' || $roundName === 'losers_SF_losers') {
                    \Log::info("Comprehensive semifinal complete - checking if all semifinals done");
                    $this->checkComprehensiveSemifinalsComplete($tournament, $level, $levelName);
                } elseif ($roundName === '4player_round1') {
                    $winnersNeeded = $tournament->winners ?? 3;
                    \Log::info("4-player round 1 complete - checking tournament type", [
                        'winners_needed' => $winnersNeeded,
                        'match_count' => $matches->count()
                    ]);
                    
                    if ($winnersNeeded >= 5 && $winnersNeeded <= 6 && $matches->count() === 4) {
                        \Log::info("Generating comprehensive semifinals (SF_winners, SF_losers, losers_SF_losers)");
                        $this->generateComprehensiveSemifinals($tournament, $level, $levelName, $matches);
                    } else {
                        \Log::info("Generating standard winners final and losers semifinal");
                        $this->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
                    }
                } elseif ($this->shouldCreateComprehensive4PlayerTournament($matches, $winnerCount, $tournament)) {
                    \Log::info(">>> USING COMPREHENSIVE 4-PLAYER TOURNAMENT WITH LOSERS BRACKET <<<");
                    $this->generateComprehensive4PlayerTournament($tournament, $level, $levelName, $matches);
                } elseif ($this->allMatchesFromSameRound($matches)) {
                    \Log::info(">>> USING STANDARD 4-PLAYER ROUND 1 MATCHES <<<");
                    $this->generate4PlayerRound1($tournament, $level, $levelName, $matches);
                } else {
                    \Log::info("Generating 4-player semifinals", [
                        'round_name' => $roundName,
                        'is_first_round' => $isFirstRound,
                        'winner_count' => $winnerCount
                    ]);
                    $this->generate4PlayerSemifinals($tournament, $level, $levelName, $matches);
                }
                break;
                
            default:
                // Handle larger winner counts (5+)
                if ($winnerCount >= 5) {
                    \Log::info("Processing large winner count scenario", [
                        'winner_count' => $winnerCount,
                        'round_name' => $roundName,
                        'is_first_round' => $isFirstRound
                    ]);
                    
                    if ($isFirstRound) {
                        \Log::info("Generating next round for large winner count from first round");
                        $this->generateLargeWinnerNextRound($tournament, $level, $levelName, $matches);
                    } else {
                        \Log::info("Large winner count but not in first round - using standard progression");
                        $this->generateStandardNextRound($tournament, $level, $levelName, $matches);
                    }
                } else {
                    \Log::warning("Unhandled winner count scenario", [
                        'winner_count' => $winnerCount,
                        'round_name' => $roundName,
                        'is_first_round' => $isFirstRound,
                        'expected_counts' => [1, 2, 3, 4]
                    ]);
                }
                break;
        }
        
        // After generating next round, check if we should determine final positions
        $this->checkAndDetermineFinalPositions($tournament, $level, $levelName);
        
        // Send notifications to players about new matches
        $this->sendNextRoundNotifications($tournament, $level, $levelName);
        
        \Log::info("=== GENERATE NEXT ROUND END ===");
    }

    /**
     * Generate 3-player final match
     */
    private function generate3PlayerFinal(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        $sfMatch = $matches->first();
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        
        // Find bye player
        $byePlayerId = $sfMatch->bye_player_id;
        
        PoolMatch::create([
            'match_name' => '3_final_match',
            'player_1_id' => $sfLoser,
            'player_2_id' => $byePlayerId,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '3_final',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Generate comprehensive 4-player tournament with winners and losers brackets
     */
    private function generateComprehensive4PlayerTournament(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        // Get the 4 winners from the completed matches
        $winners = collect();
        $losers = collect();
        
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $winner = \App\Models\User::find($match->winner_id);
                $loserId = ($match->player_1_id === $match->winner_id) ? $match->player_2_id : $match->player_1_id;
                $loser = \App\Models\User::find($loserId);
                
                if ($winner) $winners->push($winner);
                if ($loser) $losers->push($loser);
            }
        }
        
        if ($winners->count() !== 4 || $losers->count() !== 4) {
            \Log::error("Expected 4 winners and 4 losers but got {$winners->count()} winners and {$losers->count()} losers");
            return;
        }
        
        // Shuffle for fair pairing
        $shuffledWinners = $winners->shuffle()->values();
        $shuffledLosers = $losers->shuffle()->values();
        
        \Log::info("=== CREATING COMPREHENSIVE 4-PLAYER TOURNAMENT ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_count' => $shuffledWinners->count(),
            'losers_count' => $shuffledLosers->count(),
            'winners' => $shuffledWinners->pluck('name')->toArray(),
            'losers' => $shuffledLosers->pluck('name')->toArray(),
            'matches_to_create' => 4,
            'match_names' => ['4player_round1_match1', '4player_round1_match2', '4player_round1_match3', '4player_round1_match4']
        ]);
        
        // Create 4player_round1_match1: Winner A vs Winner B (for positions 1-2)
        PoolMatch::create([
            'match_name' => '4player_round1_match1',
            'player_1_id' => $shuffledWinners[0]->id,
            'player_2_id' => $shuffledWinners[1]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        // Create 4player_round1_match2: Winner C vs Winner D (for positions 1-2)
        PoolMatch::create([
            'match_name' => '4player_round1_match2',
            'player_1_id' => $shuffledWinners[2]->id,
            'player_2_id' => $shuffledWinners[3]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        // Create 4player_round1_match3: Loser A vs Loser B (for positions 3-4)
        PoolMatch::create([
            'match_name' => '4player_round1_match3',
            'player_1_id' => $shuffledLosers[0]->id,
            'player_2_id' => $shuffledLosers[1]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        // Create 4player_round1_match4: Loser C vs Loser D (for positions 5-6)
        PoolMatch::create([
            'match_name' => '4player_round1_match4',
            'player_1_id' => $shuffledLosers[2]->id,
            'player_2_id' => $shuffledLosers[3]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Generate 4-player round 1 matches from winners of larger tournament
     */
    private function generate4PlayerRound1(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        // Get the 4 winners from the completed matches
        $winners = collect();
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $winner = \App\Models\User::find($match->winner_id);
                if ($winner) {
                    $winners->push($winner);
                }
            }
        }
        
        if ($winners->count() !== 4) {
            \Log::error("Expected 4 winners but got {$winners->count()}");
            return;
        }
        
        // Shuffle winners for fair pairing
        $shuffledWinners = $winners->shuffle()->values();
        
        \Log::info("Creating 4-player round 1 matches", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'player_1' => $shuffledWinners[0]->name,
            'player_2' => $shuffledWinners[1]->name,
            'player_3' => $shuffledWinners[2]->name,
            'player_4' => $shuffledWinners[3]->name
        ]);
        
        // Create 4-player round 1 match 1: A vs B
        PoolMatch::create([
            'match_name' => '4player_round1_match1',
            'player_1_id' => $shuffledWinners[0]->id,
            'player_2_id' => $shuffledWinners[1]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        // Create 4-player round 1 match 2: C vs D
        PoolMatch::create([
            'match_name' => '4player_round1_match2',
            'player_1_id' => $shuffledWinners[2]->id,
            'player_2_id' => $shuffledWinners[3]->id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '4player_round1',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Generate comprehensive semifinals for 5-6 winner tournaments
     */
    private function generateComprehensiveSemifinals(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        // Sort matches by match name to get them in order
        $sortedMatches = $matches->sortBy('match_name');
        
        // Get winners and losers from each match
        $match1 = $sortedMatches->where('match_name', '4player_round1_match1')->first(); // Winners bracket
        $match2 = $sortedMatches->where('match_name', '4player_round1_match2')->first(); // Winners bracket
        $match3 = $sortedMatches->where('match_name', '4player_round1_match3')->first(); // Losers bracket pos 3-4
        $match4 = $sortedMatches->where('match_name', '4player_round1_match4')->first(); // Losers bracket pos 5-6
        
        if (!$match1 || !$match2 || !$match3 || !$match4) {
            \Log::error("Missing required matches for comprehensive semifinals");
            return;
        }
        
        \Log::info("Creating comprehensive semifinals", [
            'match1_winner' => $match1->winner_id,
            'match2_winner' => $match2->winner_id,
            'match3_winner' => $match3->winner_id,
            'match4_winner' => $match4->winner_id
        ]);
        
        // Create SF_winners: Winners of match1 vs match2 (for positions 1-2)
        PoolMatch::create([
            'match_name' => 'SF_winners',
            'player_1_id' => $match1->winner_id,
            'player_2_id' => $match2->winner_id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => 'SF_winners',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        // Create SF_losers: Losers of match1 vs match2 (for positions 3-4)
        $match1_loser = ($match1->player_1_id === $match1->winner_id) ? $match1->player_2_id : $match1->player_1_id;
        $match2_loser = ($match2->player_1_id === $match2->winner_id) ? $match2->player_2_id : $match2->player_1_id;
        
        PoolMatch::create([
            'match_name' => 'SF_losers',
            'player_1_id' => $match1_loser,
            'player_2_id' => $match2_loser,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => 'SF_losers',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        // Create losers_SF_winners: Winners of match3 vs match4 (for positions 5-6)
        PoolMatch::create([
            'match_name' => 'losers_SF_winners',
            'player_1_id' => $match3->winner_id,
            'player_2_id' => $match4->winner_id,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => 'losers_SF_winners',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Check if should create comprehensive 4-player tournament
     */
    private function shouldCreateComprehensive4PlayerTournament($matches, $winnerCount, $tournament)
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        return $winnerCount === 4 && 
               $winnersNeeded >= 5 && 
               $winnersNeeded <= 6 &&
               $this->allMatchesFromSameRound($matches);
    }

    /**
     * Check if all matches are from the same round
     */
    private function allMatchesFromSameRound($matches)
    {
        $roundNames = $matches->pluck('round_name')->unique();
        return $roundNames->count() === 1;
    }

    /**
     * Check if all comprehensive semifinals are complete and generate winners
     */
    public function checkComprehensiveSemifinalsComplete(Tournament $tournament, string $level, ?string $levelName)
    {
        $winnersNeeded = $tournament->winners ?? 3;
        
        if ($winnersNeeded >= 5 && $winnersNeeded <= 6) {
            // Check all 3 semifinals for 5-6 winner tournaments
            $this->checkThreeSemifinalsComplete($tournament, $level, $levelName);
        } else {
            // Check 2 semifinals for 3-4 winner tournaments  
            $this->checkTwoSemifinalsComplete($tournament, $level, $levelName);
        }
    }

    private function checkThreeSemifinalsComplete(Tournament $tournament, string $level, ?string $levelName)
    {
        // Check if all 3 semifinals are completed (for 5-6 winners)
        $sfWinnersQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'SF_winners')
            ->where('status', 'completed');
            
        $sfLosersQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', 'SF_losers')
            ->where('status', 'completed');
            
        // Check for both possible names for the losers bracket final match
        $losersSfWinnersQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where(function($query) {
                $query->where('round_name', 'losers_SF_winners')
                      ->orWhere('round_name', 'losers_SF_losers');
            })
            ->where('status', 'completed');
            
        if ($levelName) {
            $sfWinnersQuery->where('level_name', $levelName);
            $sfLosersQuery->where('level_name', $levelName);
            $losersSfWinnersQuery->where('level_name', $levelName);
        }
        
        $sfWinners = $sfWinnersQuery->first();
        $sfLosers = $sfLosersQuery->first();
        $losersSfWinners = $losersSfWinnersQuery->first();
        
        if ($sfWinners && $sfLosers && $losersSfWinners) {
            \Log::info("All 3 comprehensive semifinals complete - generating final positions");
            $this->generateComprehensiveFinalPositions($tournament, $level, $levelName, $sfWinners, $sfLosers, $losersSfWinners);
        } else {
            \Log::info("Not all 3 comprehensive semifinals complete yet", [
                'sf_winners_complete' => !!$sfWinners,
                'sf_losers_complete' => !!$sfLosers,
                'losers_sf_winners_complete' => !!$losersSfWinners
            ]);
        }
    }

    private function checkTwoSemifinalsComplete(Tournament $tournament, string $level, ?string $levelName)
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
        }
        
        $winnersFinal = $winnersFinalQuery->first();
        $losersSemifinal = $losersSemifinalQuery->first();
        
        if ($winnersFinal && $losersSemifinal) {
            // Check how many winners are needed and generate positions accordingly
            $winnersNeeded = $tournament->winners ?? 3;
            if ($winnersNeeded == 3) {
                \Log::info("All 2 standard semifinals complete - generating 3 positions directly (no final needed)");
                $this->generateStandard3PlayerPositions($tournament, $level, $levelName, $winnersFinal, $losersSemifinal);
            } elseif ($winnersNeeded == 4) {
                \Log::info("All 2 standard semifinals complete - generating 4 positions directly (no final needed)");
                $this->generateStandard4PlayerPositions($tournament, $level, $levelName, $winnersFinal, $losersSemifinal);
            } else {
                \Log::info("All 2 standard semifinals complete - generating 4-player final for more than 4 winners");
                $this->generate4PlayerFinal($tournament, $level, $levelName);
            }
        } else {
            \Log::info("Not all 2 standard semifinals complete yet", [
                'winners_final_complete' => !!$winnersFinal,
                'losers_semifinal_complete' => !!$losersSemifinal
            ]);
        }
    }

    /**
     * Generate 4 positions directly from standard semifinals (no final needed)
     */
    private function generateStandard4PlayerPositions(Tournament $tournament, string $level, ?string $levelName, $winnersFinal, $losersSemifinal)
    {
        \Log::info("Generating 4 positions directly from standard semifinals", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_final_id' => $winnersFinal->id,
            'losers_semifinal_id' => $losersSemifinal->id
        ]);
        
        $groupId = $this->getGroupIdFromLevelName($level, $levelName);
        
        // Position 1: Winner of winners_final
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $winnersFinal->winner_id,
            'position' => 1,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 2: Loser of winners_final
        $winnersLoser = ($winnersFinal->player_1_id === $winnersFinal->winner_id) ? $winnersFinal->player_2_id : $winnersFinal->player_1_id;
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $winnersLoser,
            'position' => 2,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 3: Winner of losers_semifinal
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersSemifinal->winner_id,
            'position' => 3,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 4: Loser of losers_semifinal
        $losersLoser = ($losersSemifinal->player_1_id === $losersSemifinal->winner_id) ? $losersSemifinal->player_2_id : $losersSemifinal->player_1_id;
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersLoser,
            'position' => 4,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Send push notifications to all players about their final positions
        $this->sendStandard4PlayerPositionNotifications($tournament, $level, $levelName, [
            1 => $winnersFinal->winner_id,
            2 => $winnersLoser,
            3 => $losersSemifinal->winner_id,
            4 => $losersLoser
        ]);
        
        \Log::info("Generated 4 positions directly from standard semifinals", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'position_1' => $winnersFinal->winner_id,
            'position_2' => $winnersLoser,
            'position_3' => $losersSemifinal->winner_id,
            'position_4' => $losersLoser
        ]);
    }

    /**
     * Check if both standard semifinals are complete for 3-winner tournament
     */
    private function checkTwoSemifinalsCompleteFor3Winners(Tournament $tournament, string $level, ?string $levelName)
    {
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
        }
        
        $winnersFinal = $winnersFinalQuery->first();
        $losersSemifinal = $losersSemifinalQuery->first();
        
        if ($winnersFinal && $losersSemifinal) {
            \Log::info("All 2 standard semifinals complete - generating 3 positions directly (no final needed)");
            $this->generateStandard3PlayerPositions($tournament, $level, $levelName, $winnersFinal, $losersSemifinal);
        } else {
            \Log::info("Not all 2 standard semifinals complete yet for 3 winners", [
                'winners_final_complete' => !!$winnersFinal,
                'losers_semifinal_complete' => !!$losersSemifinal
            ]);
        }
    }

    /**
     * Generate 3 positions directly from standard semifinals (no final needed)
     */
    private function generateStandard3PlayerPositions(Tournament $tournament, string $level, ?string $levelName, $winnersFinal, $losersSemifinal)
    {
        \Log::info("Generating 3 positions directly from standard semifinals", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'winners_final_id' => $winnersFinal->id,
            'losers_semifinal_id' => $losersSemifinal->id
        ]);
        
        $groupId = $this->getGroupIdFromLevelName($level, $levelName);
        
        // Position 1: Winner of winners_final
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $winnersFinal->winner_id,
            'position' => 1,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 2: Loser of winners_final
        $winnersLoser = ($winnersFinal->player_1_id === $winnersFinal->winner_id) ? $winnersFinal->player_2_id : $winnersFinal->player_1_id;
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $winnersLoser,
            'position' => 2,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 3: Winner of losers_semifinal (loser of losers_semifinal does NOT count for 3 winners)
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersSemifinal->winner_id,
            'position' => 3,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Send push notifications to the 3 winners about their final positions
        $this->sendStandard3PlayerPositionNotifications($tournament, $level, $levelName, [
            1 => $winnersFinal->winner_id,
            2 => $winnersLoser,
            3 => $losersSemifinal->winner_id
        ]);
        
        \Log::info("Generated 3 positions directly from standard semifinals", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'position_1' => $winnersFinal->winner_id,
            'position_2' => $winnersLoser,
            'position_3' => $losersSemifinal->winner_id
        ]);
    }

    /**
     * Send push notifications to players about their standard 3-player tournament positions
     */
    private function sendStandard3PlayerPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $positions)
    {
        foreach ($positions as $position => $playerId) {
            try {
                $player = \App\Models\User::find($playerId);
                if (!$player) {
                    \Log::warning("Player not found for position notification", [
                        'player_id' => $playerId,
                        'position' => $position
                    ]);
                    continue;
                }
                
                // Create position-specific messages
                $messages = [
                    1 => "Congratulations! You won 1st place in {$tournament->name}!",
                    2 => "Great job! You finished 2nd place in {$tournament->name}!",
                    3 => "Well done! You finished 3rd place in {$tournament->name}!"
                ];
                
                $message = $messages[$position] ?? "You finished position {$position} in {$tournament->name}!";
                
                // Create notification
                \App\Models\Notification::create([
                    'player_id' => $playerId,
                    'type' => 'tournament_position',
                    'title' => 'Tournament Results',
                    'message' => $message,
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'position' => $position,
                        'level' => $level,
                        'level_name' => $levelName,
                        'total_participants' => 3
                    ],
                    'read' => false,
                ]);
                
                \Log::info("Position notification sent", [
                    'player_id' => $playerId,
                    'player_name' => $player->name,
                    'position' => $position,
                    'tournament' => $tournament->name
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Failed to send position notification", [
                    'player_id' => $playerId,
                    'position' => $position,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        \Log::info("All standard 3-player tournament position notifications sent", [
            'tournament_id' => $tournament->id,
            'positions_notified' => count($positions)
        ]);
    }

    /**
     * Send push notifications to players about their standard 4-player tournament positions
     */
    private function sendStandard4PlayerPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $positions)
    {
        foreach ($positions as $position => $playerId) {
            try {
                $player = \App\Models\User::find($playerId);
                if (!$player) {
                    \Log::warning("Player not found for position notification", [
                        'player_id' => $playerId,
                        'position' => $position
                    ]);
                    continue;
                }
                
                // Create position-specific messages
                $messages = [
                    1 => "Congratulations! You won 1st place in {$tournament->name}!",
                    2 => "Great job! You finished 2nd place in {$tournament->name}!",
                    3 => "Well done! You finished 3rd place in {$tournament->name}!",
                    4 => "You finished 4th place in {$tournament->name}. Great effort!"
                ];
                
                $message = $messages[$position] ?? "You finished position {$position} in {$tournament->name}!";
                
                // Create notification
                \App\Models\Notification::create([
                    'player_id' => $playerId,
                    'type' => 'tournament_position',
                    'title' => 'Tournament Results',
                    'message' => $message,
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'position' => $position,
                        'level' => $level,
                        'level_name' => $levelName,
                        'total_participants' => 4
                    ],
                    'read' => false,
                ]);
                
                \Log::info("Position notification sent", [
                    'player_id' => $playerId,
                    'player_name' => $player->name,
                    'position' => $position,
                    'tournament' => $tournament->name
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Failed to send position notification", [
                    'player_id' => $playerId,
                    'position' => $position,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        \Log::info("All standard 4-player tournament position notifications sent", [
            'tournament_id' => $tournament->id,
            'positions_notified' => count($positions)
        ]);
    }

    /**
     * Generate final positions for comprehensive tournament
     */
    private function generateComprehensiveFinalPositions(Tournament $tournament, string $level, ?string $levelName, $sfWinners, $sfLosers, $losersSfWinners)
    {
        $groupId = $this->getGroupIdFromLevelName($level, $levelName);
        
        // Position 1: Winner of SF_winners
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $sfWinners->winner_id,
            'position' => 1,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 2: Loser of SF_winners
        $sfWinnersLoser = ($sfWinners->player_1_id === $sfWinners->winner_id) ? $sfWinners->player_2_id : $sfWinners->player_1_id;
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $sfWinnersLoser,
            'position' => 2,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 3: Winner of SF_losers
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $sfLosers->winner_id,
            'position' => 3,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 4: Loser of SF_losers
        $sfLosersLoser = ($sfLosers->player_1_id === $sfLosers->winner_id) ? $sfLosers->player_2_id : $sfLosers->player_1_id;
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $sfLosersLoser,
            'position' => 4,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 5: Winner of losers_SF_winners
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersSfWinners->winner_id,
            'position' => 5,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Position 6: Loser of losers_SF_winners
        $losersSfWinnersLoser = ($losersSfWinners->player_1_id === $losersSfWinners->winner_id) ? $losersSfWinners->player_2_id : $losersSfWinners->player_1_id;
        \App\Models\Winner::create([
            'tournament_id' => $tournament->id,
            'player_id' => $losersSfWinnersLoser,
            'position' => 6,
            'level' => $level,
            'level_name' => $levelName,
            'level_id' => $groupId,
        ]);
        
        // Send push notifications to all players about their final positions
        $this->sendComprehensiveTournamentPositionNotifications($tournament, $level, $levelName, [
            1 => $sfWinners->winner_id,
            2 => $sfWinnersLoser,
            3 => $sfLosers->winner_id,
            4 => $sfLosersLoser,
            5 => $losersSfWinners->winner_id,
            6 => $losersSfWinnersLoser
        ]);
        
        \Log::info("Generated all 6 positions for comprehensive tournament", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'positions_created' => 6
        ]);
    }

    /**
     * Send push notifications to players about their comprehensive tournament positions
     */
    private function sendComprehensiveTournamentPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $positions)
    {
        foreach ($positions as $position => $playerId) {
            try {
                $player = \App\Models\User::find($playerId);
                if (!$player) {
                    \Log::warning("Player not found for position notification", [
                        'player_id' => $playerId,
                        'position' => $position
                    ]);
                    continue;
                }
                
                // Create position-specific messages
                $messages = [
                    1 => "Congratulations! You won 1st place in {$tournament->name}!",
                    2 => "Great job! You finished 2nd place in {$tournament->name}!",
                    3 => "Well done! You finished 3rd place in {$tournament->name}!",
                    4 => "You finished 4th place in {$tournament->name}. Great effort!",
                    5 => "You finished 5th place in {$tournament->name}. Keep it up!",
                    6 => "You finished 6th place in {$tournament->name}. Thanks for participating!"
                ];
                
                $message = $messages[$position] ?? "You finished position {$position} in {$tournament->name}!";
                
                // Create notification
                \App\Models\Notification::create([
                    'player_id' => $playerId,
                    'type' => 'tournament_position',
                    'title' => 'Tournament Results',
                    'message' => $message,
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'tournament_name' => $tournament->name,
                        'position' => $position,
                        'level' => $level,
                        'level_name' => $levelName,
                        'total_participants' => 6
                    ],
                    'read' => false,
                ]);
                
                \Log::info("Position notification sent", [
                    'player_id' => $playerId,
                    'player_name' => $player->name,
                    'position' => $position,
                    'tournament' => $tournament->name
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Failed to send position notification", [
                    'player_id' => $playerId,
                    'position' => $position,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        \Log::info("All comprehensive tournament position notifications sent", [
            'tournament_id' => $tournament->id,
            'positions_notified' => count($positions)
        ]);
    }

    /**
     * Generate 4-player semifinal matches
     */
    private function generate4PlayerSemifinals(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        $sortedMatches = $matches->sortBy('match_name');
        $match1 = $sortedMatches->first();
        $match2 = $sortedMatches->last();
        
        $winner1 = $match1->winner_id;
        $loser1 = ($match1->player_1_id === $winner1) ? $match1->player_2_id : $match1->player_1_id;
        
        $winner2 = $match2->winner_id;
        $loser2 = ($match2->player_1_id === $winner2) ? $match2->player_2_id : $match2->player_1_id;
        
        // Create winners final (not semifinal)
        PoolMatch::create([
            'match_name' => 'winners_final_match',
            'player_1_id' => $winner1,
            'player_2_id' => $winner2,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => 'winners_final',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        // Create losers semifinal
        PoolMatch::create([
            'match_name' => 'losers_semifinal_match',
            'player_1_id' => $loser1,
            'player_2_id' => $loser2,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => 'losers_semifinal',
            'tournament_id' => $tournament->id,
            'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Generate 4-player final match
     */
    private function generate4PlayerFinal(Tournament $tournament, string $level, ?string $levelName)
    {
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

        if ($winnersSF && $losersSF) {
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
                'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
        }
    }

    /**
     * Create final positions based on completed final rounds
     */
    private function createPositionsFromFinalRound(Tournament $tournament, string $level, ?string $levelName, string $finalRound)
    {
        switch ($finalRound) {
            case '2_final':
                $this->create2PlayerPositions($tournament, $level, $levelName);
                break;
            case '3_final':
                $this->create3PlayerPositions($tournament, $level, $levelName);
                break;
            case '3_break_tie_final':
                $this->create3PlayerPositionsFromTieBreaker($tournament, $level, $levelName);
                break;
            case '4_final':
                $this->create4PlayerPositions($tournament, $level, $levelName);
                break;
        }
    }

    /**
     * Check if tie-breaker is needed for 3-player tournament
     */
    private function checkIfTieBreakerNeeded(int $tournamentId, string $level, ?string $levelName): bool
    {
        $sfMatchQuery = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->where('round_name', '3_SF')
            ->where('status', 'completed');
            
        // Apply level_name filter
        $this->applyLevelNameFilter($sfMatchQuery, $levelName);
        $sfMatch = $sfMatchQuery->first();

        $finalMatchQuery = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->where('round_name', '3_final')
            ->where('status', 'completed');
            
        // Apply level_name filter
        $this->applyLevelNameFilter($finalMatchQuery, $levelName);
        $finalMatch = $finalMatchQuery->first();

        if ($sfMatch && $finalMatch) {
            $sfWinner = $sfMatch->winner_id;
            $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
            $finalWinner = $finalMatch->winner_id;

            // Tie-breaker needed if bye player (not SF loser) won the final
            return $finalWinner !== $sfLoser;
        }

        return false;
    }

    /**
     * Create positions for 3-player tournament from tie-breaker match
     */
    private function create3PlayerPositionsFromTieBreaker(Tournament $tournament, string $level, ?string $levelName)
    {
        $tieBreakerMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', '3_break_tie_final')
            ->where('status', 'completed')
            ->first();

        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();

        if ($tieBreakerMatch && $sfMatch) {
            $tieBreakerWinner = $tieBreakerMatch->winner_id;
            $tieBreakerLoser = ($tieBreakerMatch->player_1_id === $tieBreakerWinner) ? 
                $tieBreakerMatch->player_2_id : $tieBreakerMatch->player_1_id;
            
            $sfWinner = $sfMatch->winner_id;
            $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;

            \Log::info("Creating positions from tie-breaker", [
                'tie_breaker_winner' => $tieBreakerWinner,
                'tie_breaker_loser' => $tieBreakerLoser,
                'sf_loser' => $sfLoser
            ]);

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $tieBreakerWinner,
                'level' => $level,
                'level_name' => $levelName,
                'position' => 1,
                'points' => 3,
            ]);

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $tieBreakerLoser,
                'level' => $level,
                'level_name' => $levelName,
                'position' => 2,
                'points' => 2,
            ]);

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $sfLoser, // SF loser gets 3rd place
                'level' => $level,
                'level_name' => $levelName,
                'position' => 3,
                'points' => 1,
            ]);
            
            // Send notifications to winners
            $this->sendPositionNotifications($tournament, $level, $levelName, [
                ['player_id' => $tieBreakerWinner, 'position' => 1],
                ['player_id' => $tieBreakerLoser, 'position' => 2],
                ['player_id' => $sfLoser, 'position' => 3]
            ]);
            
            \Log::info("Final positions created from tie-breaker match");
        }
    }

    /**
     * Create positions for 2-player tournament
     */
    private function create2PlayerPositions(Tournament $tournament, string $level, ?string $levelName)
    {
        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', '2_final')
            ->where('status', 'completed')
            ->first();

        if ($finalMatch) {
            $winner = $finalMatch->winner_id;
            $loser = ($finalMatch->player_1_id === $winner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $winner,
                'level' => $level,
                'level_name' => $levelName,
                'position' => 1,
                'points' => 3,
            ]);

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $loser,
                'level' => $level,
                'level_name' => $levelName,
                'position' => 2,
                'points' => 1,
            ]);
            
            // Send notifications to winners
            $this->sendPositionNotifications($tournament, $level, $levelName, [
                ['player_id' => $winner, 'position' => 1],
                ['player_id' => $loser, 'position' => 2]
            ]);
        }
    }

    /**
     * Create positions for 3-player tournament
     */
    private function create3PlayerPositions(Tournament $tournament, string $level, ?string $levelName)
    {
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', '3_SF')
            ->where('status', 'completed')
            ->first();

        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', '3_final')
            ->where('status', 'completed')
            ->first();

        if ($sfMatch && $finalMatch) {
            $sfWinner = $sfMatch->winner_id;
            $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
            $byePlayer = $sfMatch->bye_player_id; // Get the bye player from semifinal
            $finalWinner = $finalMatch->winner_id;
            $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id;

            \Log::info("3-player tournament position determination", [
                'sf_winner' => $sfWinner,
                'sf_loser' => $sfLoser,
                'bye_player' => $byePlayer,
                'final_winner' => $finalWinner,
                'final_loser' => $finalLoser,
                'final_participants' => 'SF loser vs bye player'
            ]);

            // CORRECT LOGIC: Final is SF loser vs bye player
            // If SF loser wins final → SF winner=1st, SF loser=2nd, bye player=3rd
            // If bye player wins final → Tie-breaker needed between SF winner and bye player
            if ($finalWinner === $sfLoser) {
                // SF loser won final - standard positioning applies
                \Log::info("Standard 3-player positioning: SF loser won final");
                
                // Create positions directly
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $sfWinner, // SF winner gets 1st (never lost)
                    'level' => $level,
                    'level_name' => $levelName,
                    'position' => 1,
                    'points' => 3,
                ]);

                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $finalWinner, // SF loser who won final gets 2nd
                    'level' => $level,
                    'level_name' => $levelName,
                    'position' => 2,
                    'points' => 2,
                ]);

                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $byePlayer, // Bye player who lost final gets 3rd
                    'level' => $level,
                    'level_name' => $levelName,
                    'position' => 3,
                    'points' => 1,
                ]);
                
                // Send notifications to winners
                $this->sendPositionNotifications($tournament, $level, $levelName, [
                    ['player_id' => $sfWinner, 'position' => 1],
                    ['player_id' => $finalWinner, 'position' => 2],
                    ['player_id' => $byePlayer, 'position' => 3]
                ]);
            } else {
                // Bye player won final - need tie-breaker between SF winner and bye player
                \Log::info("Tie-breaker needed: Bye player won final, creating 3_break_tie_final");
                
                // Check if tie-breaker already exists
                $tieBreakerMatch = PoolMatch::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('level_name', $levelName)
                    ->where('round_name', '3_break_tie_final')
                    ->first();
                
                if (!$tieBreakerMatch) {
                    // Create tie-breaker match between SF winner and bye player (final winner)
                    PoolMatch::create([
                        'match_name' => '3_break_tie_final_match',
                        'player_1_id' => $sfWinner,
                        'player_2_id' => $byePlayer, // Bye player who won final
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => '3_break_tie_final',
                        'tournament_id' => $tournament->id,
                        'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
                    ]);
                    
                    \Log::info("Tie-breaker match created successfully");
                } else {
                    \Log::info("Tie-breaker match already exists");
                }
                
                // Create position for SF loser (guaranteed 3rd place)
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $sfLoser,
                    'level' => $level,
                    'level_name' => $levelName,
                    'position' => 3,
                    'points' => 1,
                ]);
                
                // Send notification to SF loser about 3rd place
                $this->sendPositionNotifications($tournament, $level, $levelName, [
                    ['player_id' => $sfLoser, 'position' => 3]
                ]);
            }
        }
    }

    /**
     * Generate semifinal for 3 winners from round 1
     */
    private function generate3WinnerSemifinal(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        \Log::info("=== GENERATE 3-WINNER SEMIFINAL START ===");
        
        // Get the 3 winners from round 1
        $winners = [];
        foreach ($matches as $match) {
            if ($match->winner_id) {
                $winners[] = $match->winner_id;
            }
        }
        
        \Log::info("Extracted winners from matches", [
            'winners' => $winners,
            'winner_count' => count($winners)
        ]);
        
        // Check if 3_SF already exists to prevent duplicates
        $existingSFQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', '3_SF');
            
        if ($levelName) {
            $existingSFQuery->where('level_name', $levelName);
        } else {
            $existingSFQuery->whereNull('level_name');
        }
        
        $existingSF = $existingSFQuery->exists();
            
        \Log::info("Checking for existing 3_SF match", ['exists' => $existingSF]);
            
        if (!$existingSF) {
            \Log::info("Creating new 3_SF match", [
                'player_1' => $winners[0],
                'player_2' => $winners[1], 
                'bye_player' => $winners[2]
            ]);
            
            // Create one semifinal match with any 2 winners
            $newMatch = PoolMatch::create([
                'match_name' => '3_SF_match',
                'player_1_id' => $winners[0],
                'player_2_id' => $winners[1],
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => '3_SF',
                'tournament_id' => $tournament->id,
                'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
                'status' => 'pending',
                'bye_player_id' => $winners[2], // Third winner gets bye to final
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            
            \Log::info("3_SF match created successfully", ['match_id' => $newMatch->id]);
        } else {
            \Log::info("3_SF match already exists - skipping creation");
        }
        
        \Log::info("=== GENERATE 3-WINNER SEMIFINAL END ===");
    }

    /**
     * Generate comprehensive tournament for 4+ winners
     */
    private function generateComprehensiveTournament(Tournament $tournament, string $level, ?string $levelName, $matches, int $winnersNeeded)
    {
        \Log::info("=== GENERATE COMPREHENSIVE TOURNAMENT START ===", [
            'winners_needed' => $winnersNeeded,
            'matches_count' => count($matches)
        ]);
        
        // Extract winners from completed matches
        $winners = [];
        foreach ($matches as $match) {
            if ($match->status === 'completed' && $match->winner_id) {
                $winners[] = $match->winner_id;
            }
        }
        
        \Log::info("Extracted winners for comprehensive tournament", [
            'winners' => $winners,
            'winner_count' => count($winners)
        ]);
        
        // For 3-player comprehensive tournament (when we need 4, 5, or 6 winners from 3 players)
        if (count($winners) == 3 && $winnersNeeded >= 4 && $winnersNeeded <= 6) {
            $this->generateComprehensive3PlayerTournament($tournament, $level, $levelName, $winners, $winnersNeeded);
        } 
        // For 5-6 winners from 4+ players, use comprehensive semifinals
        elseif ($winnersNeeded >= 5 && $winnersNeeded <= 6) {
            $this->generateComprehensiveFromWinners($tournament, $level, $levelName, $winners);
        } else {
            // For other cases, use standard progression
            \Log::info("Using standard progression for winners needed: " . $winnersNeeded);
            // Add logic for other winner counts if needed
        }
        
        \Log::info("=== GENERATE COMPREHENSIVE TOURNAMENT END ===");
    }

    /**
     * Generate comprehensive semifinals for 5-6 winners from array
     */
    private function generateComprehensiveFromWinners(Tournament $tournament, string $level, ?string $levelName, array $winners)
    {
        \Log::info("Generating comprehensive semifinals", [
            'winners' => $winners,
            'level' => $level
        ]);
        
        $groupId = $this->getGroupIdFromLevelName($level, $levelName);
        
        // Check if comprehensive semifinals already exist
        $existingSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->whereIn('round_name', ['SF_winners', 'SF_losers', 'losers_SF_winners'])
            ->exists();
            
        if ($existingSF) {
            \Log::info("Comprehensive semifinals already exist - skipping creation");
            return;
        }
        
        // Create SF_winners (top 2 winners)
        if (count($winners) >= 2) {
            PoolMatch::create([
                'match_name' => 'SF_winners_match',
                'player_1_id' => $winners[0],
                'player_2_id' => $winners[1],
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => 'SF_winners',
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            
            \Log::info("Created SF_winners match", [
                'player_1' => $winners[0],
                'player_2' => $winners[1]
            ]);
        }
        
        // Create SF_losers (next 2 winners)
        if (count($winners) >= 4) {
            PoolMatch::create([
                'match_name' => 'SF_losers_match',
                'player_1_id' => $winners[2],
                'player_2_id' => $winners[3],
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => 'SF_losers',
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            
            \Log::info("Created SF_losers match", [
                'player_1' => $winners[2],
                'player_2' => $winners[3]
            ]);
        }
        
        // Create losers_SF_winners if we have a 5th winner
        if (count($winners) >= 5) {
            // 5th winner gets bye, will play against loser of SF_winners
            \Log::info("5th winner gets bye to losers_SF_winners", [
                'bye_player' => $winners[4]
            ]);
            
            // The losers_SF_winners match will be created later when SF_winners completes
        }
    }

    /**
     * Generate comprehensive 3-player tournament for 4-6 winners
     * A, B, C are winners; D, E, F are losers (if progression)
     */
    private function generateComprehensive3PlayerTournament(Tournament $tournament, string $level, ?string $levelName, array $winners, int $winnersNeeded)
    {
        \Log::info("=== GENERATE COMPREHENSIVE 3-PLAYER TOURNAMENT START ===", [
            'winners' => $winners,
            'winners_needed' => $winnersNeeded,
            'level' => $level
        ]);
        
        $groupId = $this->getGroupIdFromLevelName($level, $levelName);
        
        // Generate winners tournament (A, B, C) - positions 1, 2, 3
        $this->generate3PlayerWinnersTournament($tournament, $level, $levelName, $winners, $groupId);
        
        // Generate losers tournament (D, E, F) - positions 4, 5, 6 (if needed)
        if ($winnersNeeded >= 4) {
            $this->generate3PlayerLosersTournament($tournament, $level, $levelName, $winnersNeeded, $groupId);
        }
        
        \Log::info("=== GENERATE COMPREHENSIVE 3-PLAYER TOURNAMENT END ===");
    }

    /**
     * Generate 3-player winners tournament (A, B, C) for positions 1, 2, 3
     */
    private function generate3PlayerWinnersTournament(Tournament $tournament, string $level, ?string $levelName, array $winners, $groupId)
    {
        \Log::info("Generating 3-player winners tournament", [
            'winners' => $winners
        ]);
        
        // Check if winners tournament already exists
        $existingWinnersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_SF')
            ->exists();
            
        if ($existingWinnersSF) {
            \Log::info("3-player winners tournament already exists - skipping creation");
            return;
        }
        
        // Create winners semifinal: A vs B (C gets bye)
        PoolMatch::create([
            'match_name' => '3_winners_SF_match',
            'player_1_id' => $winners[0], // A
            'player_2_id' => $winners[1], // B
            'bye_player_id' => $winners[2], // C gets bye
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => '3_winners_SF',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        \Log::info("Created 3-player winners semifinal", [
            'player_1' => $winners[0], // A
            'player_2' => $winners[1], // B
            'bye_player' => $winners[2] // C
        ]);
    }

    /**
     * Generate 3-player losers tournament (D, E, F) for positions 4, 5, 6
     */
    private function generate3PlayerLosersTournament(Tournament $tournament, string $level, ?string $levelName, int $winnersNeeded, $groupId)
    {
        \Log::info("Generating 3-player losers tournament", [
            'winners_needed' => $winnersNeeded
        ]);
        
        // Get losers from previous round
        $losers = $this->getLoserPlayersForTournament($tournament, $level, $groupId);
        
        if ($losers->count() < 3) {
            \Log::info("Not enough losers for 3-player losers tournament", [
                'losers_count' => $losers->count()
            ]);
            return;
        }
        
        // Check if losers tournament already exists
        $existingLosersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->exists();
            
        if ($existingLosersSF) {
            \Log::info("3-player losers tournament already exists - skipping creation");
            return;
        }
        
        $losersList = $losers->take(3)->values();
        
        // Create losers semifinal: D vs E (F gets bye)
        PoolMatch::create([
            'match_name' => 'losers_3_SF_match',
            'player_1_id' => $losersList[0]->id, // D
            'player_2_id' => $losersList[1]->id, // E
            'bye_player_id' => $losersList[2]->id, // F gets bye
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => 'losers_3_SF',
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
        
        \Log::info("Created 3-player losers semifinal", [
            'player_1' => $losersList[0]->id, // D
            'player_2' => $losersList[1]->id, // E
            'bye_player' => $losersList[2]->id // F
        ]);
    }

    /**
     * Get loser players for tournament level
     */
    private function getLoserPlayersForTournament(Tournament $tournament, string $level, $groupId)
    {
        // Get all matches from previous round
        $previousMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->get();
        
        $losers = collect();
        
        foreach ($previousMatches as $match) {
            if ($match->winner_id) {
                // Add the loser to the collection
                $loserId = ($match->winner_id == $match->player_1_id) ? $match->player_2_id : $match->player_1_id;
                $loser = \App\Models\User::find($loserId);
                if ($loser) {
                    $losers->push($loser);
                }
            }
        }
        
        return $losers;
    }

    /**
     * Check 3-player tournament progression and generate next matches
     */
    private function check3PlayerTournamentProgression(Tournament $tournament, string $level, ?string $levelName, string $completedRound)
    {
        \Log::info("=== CHECK 3-PLAYER TOURNAMENT PROGRESSION START ===", [
            'completed_round' => $completedRound,
            'tournament_id' => $tournament->id
        ]);
        
        $groupId = $this->getGroupIdFromLevelName($level, $levelName);
        
        if ($completedRound === '3_winners_SF') {
            $this->handle3PlayerWinnersSFComplete($tournament, $level, $levelName, $groupId);
        } elseif ($completedRound === 'losers_3_SF') {
            $this->handle3PlayerLosersSFComplete($tournament, $level, $levelName, $groupId);
        } elseif ($completedRound === '3_winners_final') {
            $this->handle3PlayerWinnersFinalComplete($tournament, $level, $levelName, $groupId);
        } elseif ($completedRound === 'losers_3_final') {
            $this->handle3PlayerLosersFinalComplete($tournament, $level, $levelName, $groupId);
        } elseif ($completedRound === '3_winners_tie_breaker' || $completedRound === '3_winners_fair_chance') {
            $this->handle3PlayerWinnersComplete($tournament, $level, $levelName, $groupId);
        } elseif ($completedRound === 'losers_3_tie_breaker' || $completedRound === 'losers_3_fair_chance') {
            $this->handle3PlayerLosersComplete($tournament, $level, $levelName, $groupId);
        }
        
        \Log::info("=== CHECK 3-PLAYER TOURNAMENT PROGRESSION END ===");
    }

    /**
     * Handle 3-player winners semifinal completion
     */
    private function handle3PlayerWinnersSFComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3-player winners semifinal completion");
        
        // Get the completed SF match
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_SF')
            ->where('status', 'completed')
            ->first();
            
        if (!$sfMatch) {
            \Log::error("3-player winners SF match not found");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        \Log::info("3-player winners SF results", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser,
            'bye_player' => $byePlayer
        ]);
        
        // Check if final already exists
        $existingFinal = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', '3_winners_final')
            ->exists();
            
        if (!$existingFinal) {
            // Create final match: SF loser vs bye player
            PoolMatch::create([
                'match_name' => '3_winners_final_match',
                'player_1_id' => $sfLoser,
                'player_2_id' => $byePlayer,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => '3_winners_final',
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            
            \Log::info("Created 3-player winners final match", [
                'sf_loser' => $sfLoser,
                'bye_player' => $byePlayer
            ]);
        }
    }

    /**
     * Handle 3-player losers semifinal completion
     */
    private function handle3PlayerLosersSFComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("Handling 3-player losers semifinal completion");
        
        // Get the completed SF match
        $sfMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_SF')
            ->where('status', 'completed')
            ->first();
            
        if (!$sfMatch) {
            \Log::error("3-player losers SF match not found");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $sfLoser = ($sfMatch->player_1_id === $sfWinner) ? $sfMatch->player_2_id : $sfMatch->player_1_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        \Log::info("3-player losers SF results", [
            'sf_winner' => $sfWinner,
            'sf_loser' => $sfLoser,
            'bye_player' => $byePlayer
        ]);
        
        // Check if final already exists
        $existingFinal = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', 'losers_3_final')
            ->exists();
            
        if (!$existingFinal) {
            // Create final match: SF loser vs bye player
            PoolMatch::create([
                'match_name' => 'losers_3_final_match',
                'player_1_id' => $sfLoser,
                'player_2_id' => $byePlayer,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => 'losers_3_final',
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            
            \Log::info("Created 3-player losers final match", [
                'sf_loser' => $sfLoser,
                'bye_player' => $byePlayer
            ]);
        }
    }

    /**
     * Handle 3-player winners final completion
     */
    private function handle3PlayerWinnersFinalComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
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
            \Log::error("Required matches not found for winners final completion");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $finalWinner = $finalMatch->winner_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        \Log::info("3-player winners final results", [
            'sf_winner' => $sfWinner,
            'final_winner' => $finalWinner,
            'bye_player' => $byePlayer
        ]);
        
        // Subcase 1a: Bye player wins final - create tie breaker
        if ($finalWinner === $byePlayer) {
            $this->create3PlayerTieBreaker($tournament, $level, $levelName, $groupId, $byePlayer, $sfWinner, 'winners');
        } else {
            // Subcase 1b: Bye player loses final - create fair chance
            $this->create3PlayerFairChance($tournament, $level, $levelName, $groupId, $byePlayer, $sfWinner, 'winners');
        }
    }

    /**
     * Handle 3-player losers final completion
     */
    private function handle3PlayerLosersFinalComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
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
            \Log::error("Required matches not found for losers final completion");
            return;
        }
        
        $sfWinner = $sfMatch->winner_id;
        $finalWinner = $finalMatch->winner_id;
        $byePlayer = $sfMatch->bye_player_id;
        
        \Log::info("3-player losers final results", [
            'sf_winner' => $sfWinner,
            'final_winner' => $finalWinner,
            'bye_player' => $byePlayer
        ]);
        
        // Subcase 1a: Bye player wins final - create tie breaker
        if ($finalWinner === $byePlayer) {
            $this->create3PlayerTieBreaker($tournament, $level, $levelName, $groupId, $byePlayer, $sfWinner, 'losers');
        } else {
            // Subcase 1b: Bye player loses final - create fair chance
            $this->create3PlayerFairChance($tournament, $level, $levelName, $groupId, $byePlayer, $sfWinner, 'losers');
        }
    }

    /**
     * Create 3-player tie breaker match
     */
    private function create3PlayerTieBreaker(Tournament $tournament, string $level, ?string $levelName, $groupId, $byePlayer, $sfWinner, string $type)
    {
        $roundName = $type === 'winners' ? '3_winners_tie_breaker' : 'losers_3_tie_breaker';
        $matchName = $type === 'winners' ? '3_winners_tie_breaker_match' : 'losers_3_tie_breaker_match';
        
        // Check if tie breaker already exists
        $existing = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', $roundName)
            ->exists();
            
        if (!$existing) {
            PoolMatch::create([
                'match_name' => $matchName,
                'player_1_id' => $byePlayer,
                'player_2_id' => $sfWinner,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            
            \Log::info("Created 3-player tie breaker match", [
                'type' => $type,
                'bye_player' => $byePlayer,
                'sf_winner' => $sfWinner
            ]);
        }
    }

    /**
     * Create 3-player fair chance match
     */
    private function create3PlayerFairChance(Tournament $tournament, string $level, ?string $levelName, $groupId, $byePlayer, $sfWinner, string $type)
    {
        $roundName = $type === 'winners' ? '3_winners_fair_chance' : 'losers_3_fair_chance';
        $matchName = $type === 'winners' ? '3_winners_fair_chance_match' : 'losers_3_fair_chance_match';
        
        // Check if fair chance already exists
        $existing = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('round_name', $roundName)
            ->exists();
            
        if (!$existing) {
            PoolMatch::create([
                'match_name' => $matchName,
                'player_1_id' => $byePlayer,
                'player_2_id' => $sfWinner,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            
            \Log::info("Created 3-player fair chance match", [
                'type' => $type,
                'bye_player' => $byePlayer,
                'sf_winner' => $sfWinner
            ]);
        }
    }

    /**
     * Handle 3-player winners tournament completion
     */
    private function handle3PlayerWinnersComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("3-player winners tournament complete - generating positions 1-3");
        // This will be handled by the MatchAlgorithmService when determining winners
    }

    /**
     * Handle 3-player losers tournament completion
     */
    private function handle3PlayerLosersComplete(Tournament $tournament, string $level, ?string $levelName, $groupId)
    {
        \Log::info("3-player losers tournament complete - generating positions 4-6");
        // This will be handled by the MatchAlgorithmService when determining winners
    }

    /**
     * Create positions for 4-player tournament
     */
    private function create4PlayerPositions(Tournament $tournament, string $level, ?string $levelName)
    {
        $winnersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', 'winners_final')
            ->where('status', 'completed')
            ->first();

        $losersSF = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', 'losers_semifinal')
            ->where('status', 'completed')
            ->first();

        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('level_name', $levelName)
            ->where('round_name', '4_final')
            ->where('status', 'completed')
            ->first();

        if ($finalMatch && $winnersSF && $losersSF) {
            $position1 = $winnersSF->winner_id; // Winner of winners SF
            $finalWinner = $finalMatch->winner_id; // Position 2
            $finalLoser = ($finalMatch->player_1_id === $finalWinner) ? $finalMatch->player_2_id : $finalMatch->player_1_id; // Position 3
            $position4 = ($losersSF->player_1_id === $losersSF->winner_id) ? $losersSF->player_2_id : $losersSF->player_1_id; // Loser of losers SF

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $position1,
                'level' => $level,
                'level_name' => $levelName,
                'position' => 1,
                'points' => 3,
            ]);

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $finalWinner,
                'level' => $level,
                'level_name' => $levelName,
                'position' => 2,
                'points' => 2,
            ]);

            Winner::create([
                'tournament_id' => $tournament->id,
                'player_id' => $finalLoser,
                'level' => $level,
                'level_name' => $levelName,
                'position' => 3,
                'points' => 1,
            ]);

            // Send notifications to winners
            $this->sendPositionNotifications($tournament, $level, $levelName, [
                ['player_id' => $position1, 'position' => 1],
                ['player_id' => $finalWinner, 'position' => 2],
                ['player_id' => $finalLoser, 'position' => 3]
            ]);

            // Position 4 player is eliminated (no points)
        }
    }


    /**
     * Get player count for a specific level and level name
     */
    private function getPlayerCountForLevel(Tournament $tournament, string $level, ?string $levelName)
    {
        // Count unique players in first round matches for this level/level_name
        $matchesQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where(function($query) {
                $query->where('round_name', 'round_1')
                      ->orWhere('round_name', '3_SF')
                      ->orWhere('round_name', '2_final');
            });
            
        // Apply level_name filter
        $this->applyLevelNameFilter($matchesQuery, $levelName);
        $matches = $matchesQuery->get();

        $playerIds = collect();
        foreach ($matches as $match) {
            $playerIds->push($match->player_1_id);
            $playerIds->push($match->player_2_id);
            if ($match->bye_player_id) {
                $playerIds->push($match->bye_player_id);
            }
        }

        return $playerIds->filter()->unique()->count();
    }

    /**
     * Apply level_name filter to query (handles null values)
     */
    private function applyLevelNameFilter($query, ?string $levelName)
    {
        if ($levelName) {
            return $query->where('level_name', $levelName);
        } else {
            return $query->whereNull('level_name');
        }
    }

    /**
     * Get group ID from level name
     */
    private function getGroupIdFromLevelName(string $level, ?string $levelName)
    {
        // For special tournaments or when level_name is null, return a default group ID
        if (!$levelName) {
            return 1; // Default group ID for special tournaments
        }
        
        switch ($level) {
            case 'community':
                $community = \App\Models\Community::where('name', $levelName)->first();
                return $community ? $community->id : 1;
            case 'county':
                $county = \App\Models\County::where('name', $levelName)->first();
                return $county ? $county->id : 1;
            case 'regional':
                $region = \App\Models\Region::where('name', $levelName)->first();
                return $region ? $region->id : 1;
            default:
                return 1; // Default group ID
        }
    }

    /**
     * Send notifications to players about new next round matches
     */
    private function sendNextRoundNotifications(Tournament $tournament, string $level, ?string $levelName)
    {
        // Get the most recent matches created for this tournament/level
        $recentMatchesQuery = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5)) // Recent matches within last 5 minutes
            ->with(['player1', 'player2']);
            
        // Apply level_name filter
        $this->applyLevelNameFilter($recentMatchesQuery, $levelName);
        
        $recentMatches = $recentMatchesQuery->get();
        
        if ($recentMatches->isEmpty()) {
            \Log::info("No recent matches found for next round notifications");
            return;
        }
        
        \Log::info("Sending next round notifications", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'match_count' => $recentMatches->count()
        ]);
        
        // Collect players to avoid duplicate notifications
        $notifiedPlayers = [];
        
        foreach ($recentMatches as $match) {
            // Notify player 1
            if ($match->player1 && !in_array($match->player_1_id, $notifiedPlayers)) {
                \App\Models\Notification::create([
                    'player_id' => $match->player_1_id,
                    'type' => 'pairing',
                    'message' => "You've advanced to the next round! New match created in {$tournament->name}.",
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'match_id' => $match->id,
                        'level' => $level,
                        'level_name' => $levelName,
                        'opponent_name' => $match->player2->name ?? 'TBD'
                    ]
                ]);
                $notifiedPlayers[] = $match->player_1_id;
            }
            
            // Notify player 2
            if ($match->player2 && !in_array($match->player_2_id, $notifiedPlayers)) {
                \App\Models\Notification::create([
                    'player_id' => $match->player_2_id,
                    'type' => 'pairing',
                    'message' => "You've advanced to the next round! New match created in {$tournament->name}.",
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'match_id' => $match->id,
                        'level' => $level,
                        'level_name' => $levelName,
                        'opponent_name' => $match->player1->name ?? 'TBD'
                    ]
                ]);
                $notifiedPlayers[] = $match->player_2_id;
            }
        }
        
        \Log::info("Next round notifications sent", [
            'players_notified' => count($notifiedPlayers),
            'player_ids' => $notifiedPlayers
        ]);
    }

    /**
     * Send notifications to players who achieved positions
     */
    private function sendPositionNotifications(Tournament $tournament, string $level, ?string $levelName, array $winners)
    {
        foreach ($winners as $winner) {
            $position = $winner['position'];
            $playerId = $winner['player_id'];
            
            $positionText = match($position) {
                1 => '1st place',
                2 => '2nd place', 
                3 => '3rd place',
                default => "{$position}th place"
            };
            
            $nextLevel = $this->getNextLevel($level);
            $nextLevelText = $nextLevel ? " You qualify for the {$nextLevel} level!" : " Congratulations on completing the tournament!";
            
            $levelText = $levelName ? "{$levelName} {$level}" : $level;
            
            \App\Models\Notification::create([
                'player_id' => $playerId,
                'message' => "Congratulations! You finished in {$positionText} in the {$levelText} tournament.{$nextLevelText}",
                'type' => 'tournament_position',
                'data' => [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'position' => $position,
                    'next_level' => $nextLevel
                ]
            ]);
        }
    }

    /**
     * Generate tie-breaker match for 3-player tournament when bye player wins 3_final
     */
    private function generateTieBreakerMatch(int $tournamentId, string $level, ?string $levelName)
    {
        $tournament = Tournament::find($tournamentId);
        
        $sfMatchQuery = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->where('round_name', '3_SF')
            ->where('status', 'completed');
            
        // Apply level_name filter
        $this->applyLevelNameFilter($sfMatchQuery, $levelName);
        $sfMatch = $sfMatchQuery->first();

        $finalMatchQuery = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->where('round_name', '3_final')
            ->where('status', 'completed');
            
        // Apply level_name filter
        $this->applyLevelNameFilter($finalMatchQuery, $levelName);
        $finalMatch = $finalMatchQuery->first();

        if ($sfMatch && $finalMatch) {
            $sfWinner = $sfMatch->winner_id;
            $finalWinner = $finalMatch->winner_id; // This is the bye player who won

            // Create tie-breaker match between SF winner and bye player (final winner)
            PoolMatch::create([
                'match_name' => '3_break_tie_final_match',
                'player_1_id' => $sfWinner,
                'player_2_id' => $finalWinner,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => '3_break_tie_final',
                'tournament_id' => $tournament->id,
                'group_id' => $this->getGroupIdFromLevelName($level, $levelName),
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);

            \Log::info("Tie-breaker match created", [
                'tournament_id' => $tournamentId,
                'level' => $level,
                'level_name' => $levelName,
                'sf_winner' => $sfWinner,
                'bye_player_final_winner' => $finalWinner
            ]);
        }
    }

    /**
     * Get next tournament level
     */
    private function getNextLevel(string $currentLevel)
    {
        $levels = ['community' => 'county', 'county' => 'regional', 'regional' => 'national'];
        return $levels[$currentLevel] ?? null;
    }

    /**
     * Check if final positions should be determined and call determineFinalPositions
     */
    private function checkAndDetermineFinalPositions(Tournament $tournament, string $level, ?string $levelName)
    {
        \Log::info("=== CHECKING FOR FINAL POSITION DETERMINATION ===", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName
        ]);

        // Check if there are any final round matches completed
        $finalRounds = ['4_final', '3_final', '2_final', '3_break_tie_final'];
        
        foreach ($finalRounds as $finalRound) {
            $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('level_name', $levelName)
                ->where('round_name', $finalRound)
                ->where('status', 'completed')
                ->first();

            if ($finalMatch) {
                \Log::info("Final round match found", [
                    'round_name' => $finalRound,
                    'match_id' => $finalMatch->id
                ]);

                // Check if positions already exist to avoid duplicates
                $existingPositions = Winner::where('tournament_id', $tournament->id)
                    ->where('level', $level)
                    ->where('level_name', $levelName)
                    ->exists();

                if (!$existingPositions) {
                    \Log::info("No existing positions found, calling determineFinalPositions");
                    
                    // Create a request object to call determineFinalPositions
                    $request = new \Illuminate\Http\Request([
                        'tournament_id' => $tournament->id,
                        'level' => $level,
                        'level_name' => $levelName,
                    ]);

                    try {
                        $response = $this->determineFinalPositions($request);
                        $responseData = json_decode($response->getContent(), true);
                        
                        \Log::info("Final positions determination result", $responseData);
                        
                        if ($responseData['positions_created'] ?? false) {
                            \Log::info("✅ WINNERS GENERATED SUCCESSFULLY for {$level} {$levelName}");
                        }
                    } catch (\Exception $e) {
                        \Log::error("Failed to determine final positions: " . $e->getMessage(), [
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    \Log::info("Positions already exist for this tournament level");
                }
                
                // Only process the first final round found
                break;
            }
        }
        
        \Log::info("=== FINAL POSITION CHECK COMPLETE ===");
    }

    /**
     * Generate next round for large winner counts (5+)
     * This is PROGRESSION logic - will add a loser if odd number of winners
     */
    private function generateLargeWinnerNextRound(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        \Log::info("Generating PROGRESSION round for large winner count", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'logic' => 'If odd winners >3, add one loser to make even pairs'
        ]);

        try {
            // Use the MatchAlgorithmService to generate the next round
            // This will call handleLargeGroupProgression which adds a loser for odd winner counts
            $matchAlgorithmService = app(\App\Services\MatchAlgorithmService::class);
            $result = $matchAlgorithmService->generateNextRound($tournament, $level, null);
            
            \Log::info("Large winner PROGRESSION round generation completed", [
                'result' => $result,
                'note' => 'Used handleLargeGroupProgression - adds loser for odd winners'
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Failed to generate next round for large winner count", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate standard next round progression
     * This is also PROGRESSION logic - will add a loser if odd number of winners
     */
    private function generateStandardNextRound(Tournament $tournament, string $level, ?string $levelName, $matches)
    {
        \Log::info("Generating standard PROGRESSION round", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'level_name' => $levelName,
            'logic' => 'Standard progression - adds loser for odd winners if >3'
        ]);

        try {
            // Use the MatchAlgorithmService to generate the next round
            // This will call appropriate progression logic (handleLargeGroupProgression or handleSpecialProgression)
            $matchAlgorithmService = app(\App\Services\MatchAlgorithmService::class);
            $result = $matchAlgorithmService->generateNextRound($tournament, $level, null);
            
            \Log::info("Standard PROGRESSION round generation completed", [
                'result' => $result,
                'note' => 'Used appropriate progression logic based on winner count'
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Failed to generate standard next round", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
