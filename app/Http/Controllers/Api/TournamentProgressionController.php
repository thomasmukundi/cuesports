<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Services\TournamentProgressionService;
use App\Services\WinnerDeterminationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TournamentProgressionController extends Controller
{
    protected $progressionService;
    protected $winnerService;

    public function __construct(
        TournamentProgressionService $progressionService,
        WinnerDeterminationService $winnerService
    ) {
        $this->progressionService = $progressionService;
        $this->winnerService = $winnerService;
    }

    /**
     * Check if a round is complete and trigger next round generation
     */
    public function checkRoundCompletion(Request $request)
    {
        Log::info("=== TOURNAMENT PROGRESSION: checkRoundCompletion START ===", [
            'request_data' => $request->all()
        ]);
        
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'level' => 'required|string',
            'level_name' => 'nullable|string',
            'round_name' => 'required|string',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        Log::info("Tournament found", ['tournament_id' => $tournament->id, 'automation_mode' => $tournament->automation_mode]);
        
        // Delegate to TournamentProgressionService
        $result = $this->progressionService->checkRoundCompletion(
            $tournament, 
            $request->level, 
            $request->level_name, 
            $request->round_name
        );
        
        return response()->json($result);
    }

    /**
     * Determine final positions for completed tournaments
     */
    public function determineFinalPositions(Request $request)
    {
        Log::info("=== WINNER DETERMINATION: determineFinalPositions START ===", [
            'request_data' => $request->all()
        ]);
        
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'level' => 'required|string',
            'level_name' => 'nullable|string',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        
        // Delegate to WinnerDeterminationService
        $result = $this->winnerService->determineFinalPositions(
            $tournament, 
            $request->level, 
            $request->level_name
        );
        
        return response()->json($result);
    }

    /**
     * Initialize tournament for a specific level
     */
    public function initializeTournament(Request $request)
    {
        Log::info("=== TOURNAMENT INITIALIZATION START ===", [
            'request_data' => $request->all()
        ]);
        
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'level' => 'required|string',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        
        // For now, delegate to MatchAlgorithmService for initialization
        // This can be moved to TournamentProgressionService later
        $matchService = app(\App\Services\MatchAlgorithmService::class);
        $result = $matchService->initialize($request->tournament_id, $request->level);
        
        return response()->json($result);
    }

    /**
     * Get tournament status for a specific level
     */
    public function getTournamentStatus(Request $request)
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'level' => 'required|string',
            'level_name' => 'nullable|string',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        
        // Get basic tournament status information
        $status = [
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name,
            'level' => $request->level,
            'level_name' => $request->level_name,
            'automation_mode' => $tournament->automation_mode,
            'special' => $tournament->special,
            'winners_needed' => $tournament->winners ?? 3,
        ];
        
        return response()->json(['status' => 'success', 'data' => $status]);
    }
}
