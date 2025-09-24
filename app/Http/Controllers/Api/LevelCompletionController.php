<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Winner;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use App\Services\MatchAlgorithmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LevelCompletionController extends Controller
{
    protected $matchService;

    public function __construct(MatchAlgorithmService $matchService)
    {
        $this->matchService = $matchService;
    }

    /**
     * Check if a tournament level is complete and ready for next level
     */
    public function checkLevelCompletion(Request $request)
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'level' => 'required|string|in:community,county,regional,national',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        $level = $request->level;
        
        $completionStatus = $this->getLevelCompletionStatus($tournament, $level);
        
        return response()->json([
            'level' => $level,
            'next_level' => $this->getNextLevel($level),
            'is_complete' => $completionStatus['is_complete'],
            'completion_details' => $completionStatus['details'],
            'ready_for_next_level' => $completionStatus['is_complete'],
            'message' => $completionStatus['message']
        ]);
    }

    /**
     * Initialize next tournament level with position-based grouping
     */
    public function initializeNextLevel(Request $request)
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'current_level' => 'required|string|in:community,county,regional',
        ]);

        $tournament = Tournament::find($request->tournament_id);
        $currentLevel = $request->current_level;
        $nextLevel = $this->getNextLevel($currentLevel);

        if (!$nextLevel) {
            return response()->json(['message' => 'No next level available'], 400);
        }

        try {
            // Get winners from current level grouped by position
            $winners = $this->getWinnersGroupedByPosition($tournament, $currentLevel);
            
            // Initialize next level with position-based pairing and community avoidance
            $this->matchService->initializeTournamentLevelWithPositions($tournament->id, $nextLevel, $winners);
            
            Log::info("Initialized {$nextLevel} level for tournament {$tournament->id}");
            
            return response()->json([
                'success' => true,
                'message' => "Successfully initialized {$nextLevel} level",
                'level_initialized' => $nextLevel,
                'players_count' => $winners->flatten()->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Next level initialization failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize next level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get level completion status
     */
    private function getLevelCompletionStatus(Tournament $tournament, string $level)
    {
        switch ($level) {
            case 'community':
                return $this->checkCommunityLevelCompletion($tournament);
            case 'county':
                return $this->checkCountyLevelCompletion($tournament);
            case 'regional':
                return $this->checkRegionalLevelCompletion($tournament);
            case 'national':
                return $this->checkNationalLevelCompletion($tournament);
            default:
                return ['is_complete' => false, 'message' => 'Invalid level', 'details' => []];
        }
    }

    /**
     * Check community level completion
     */
    private function checkCommunityLevelCompletion(Tournament $tournament)
    {
        // Get all communities that have registered players
        $communities = Community::whereHas('users.tournamentRegistrations', function($query) use ($tournament) {
            $query->where('tournament_id', $tournament->id);
        })->get();

        $completedCommunities = [];
        $incompleteCommunities = [];

        foreach ($communities as $community) {
            $winners = Winner::where('tournament_id', $tournament->id)
                ->where('level', 'community')
                ->where('level_name', $community->name)
                ->count();

            if ($winners >= 3) { // At least top 3 positions filled
                $completedCommunities[] = [
                    'name' => $community->name,
                    'winners_count' => $winners
                ];
            } else {
                $incompleteCommunities[] = [
                    'name' => $community->name,
                    'winners_count' => $winners,
                    'needed' => 3 - $winners
                ];
            }
        }

        $isComplete = empty($incompleteCommunities);

        return [
            'is_complete' => $isComplete,
            'message' => $isComplete ? 'All communities have completed their tournaments' : 'Some communities still have incomplete tournaments',
            'details' => [
                'total_communities' => $communities->count(),
                'completed_communities' => $completedCommunities,
                'incomplete_communities' => $incompleteCommunities
            ]
        ];
    }

    /**
     * Check county level completion
     */
    private function checkCountyLevelCompletion(Tournament $tournament)
    {
        $counties = County::whereHas('users.tournamentRegistrations', function($query) use ($tournament) {
            $query->where('tournament_id', $tournament->id);
        })->get();

        $completedCounties = [];
        $incompleteCounties = [];

        foreach ($counties as $county) {
            $winners = Winner::where('tournament_id', $tournament->id)
                ->where('level', 'county')
                ->where('level_name', $county->name)
                ->count();

            if ($winners >= 3) {
                $completedCounties[] = [
                    'name' => $county->name,
                    'winners_count' => $winners
                ];
            } else {
                $incompleteCounties[] = [
                    'name' => $county->name,
                    'winners_count' => $winners,
                    'needed' => 3 - $winners
                ];
            }
        }

        $isComplete = empty($incompleteCounties);

        return [
            'is_complete' => $isComplete,
            'message' => $isComplete ? 'All counties have completed their tournaments' : 'Some counties still have incomplete tournaments',
            'details' => [
                'total_counties' => $counties->count(),
                'completed_counties' => $completedCounties,
                'incomplete_counties' => $incompleteCounties
            ]
        ];
    }

    /**
     * Check regional level completion
     */
    private function checkRegionalLevelCompletion(Tournament $tournament)
    {
        $regions = Region::whereHas('users.tournamentRegistrations', function($query) use ($tournament) {
            $query->where('tournament_id', $tournament->id);
        })->get();

        $completedRegions = [];
        $incompleteRegions = [];

        foreach ($regions as $region) {
            $winners = Winner::where('tournament_id', $tournament->id)
                ->where('level', 'regional')
                ->where('level_name', $region->name)
                ->count();

            if ($winners >= 3) {
                $completedRegions[] = [
                    'name' => $region->name,
                    'winners_count' => $winners
                ];
            } else {
                $incompleteRegions[] = [
                    'name' => $region->name,
                    'winners_count' => $winners,
                    'needed' => 3 - $winners
                ];
            }
        }

        $isComplete = empty($incompleteRegions);

        return [
            'is_complete' => $isComplete,
            'message' => $isComplete ? 'All regions have completed their tournaments' : 'Some regions still have incomplete tournaments',
            'details' => [
                'total_regions' => $regions->count(),
                'completed_regions' => $completedRegions,
                'incomplete_regions' => $incompleteRegions
            ]
        ];
    }

    /**
     * Check national level completion
     */
    private function checkNationalLevelCompletion(Tournament $tournament)
    {
        $winners = Winner::where('tournament_id', $tournament->id)
            ->where('level', 'national')
            ->count();

        $isComplete = $winners >= 3;

        return [
            'is_complete' => $isComplete,
            'message' => $isComplete ? 'National tournament completed' : 'National tournament still in progress',
            'details' => [
                'winners_count' => $winners,
                'needed' => $isComplete ? 0 : (3 - $winners)
            ]
        ];
    }

    /**
     * Get winners grouped by position for next level initialization
     */
    private function getWinnersGroupedByPosition(Tournament $tournament, string $level)
    {
        $position1 = Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('position', 1)
            ->with('player')
            ->get()
            ->pluck('player');

        $position2 = Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('position', 2)
            ->with('player')
            ->get()
            ->pluck('player');

        $position3 = Winner::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('position', 3)
            ->with('player')
            ->get()
            ->pluck('player');

        return [
            'position_1' => $position1,
            'position_2' => $position2,
            'position_3' => $position3
        ];
    }

    /**
     * Get next level
     */
    private function getNextLevel(string $currentLevel)
    {
        $levels = ['community' => 'county', 'county' => 'regional', 'regional' => 'national'];
        return $levels[$currentLevel] ?? null;
    }
}
