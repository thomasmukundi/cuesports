<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\User;
use App\Models\PoolMatch;
use App\Models\Winner;
use App\Models\Notification;
use App\Services\MatchAlgorithmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $matchService;

    public function __construct(MatchAlgorithmService $matchService)
    {
        $this->matchService = $matchService;
        // Temporarily disable middleware for testing
        // $this->middleware('auth:sanctum');
        // $this->middleware('admin');
    }

    /**
     * Create a new tournament
     */
    public function createTournament(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'registration_deadline' => 'required|date|before:start_date',
            'entry_fee' => 'required|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
        ]);

        $tournament = Tournament::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'registration_deadline' => $request->registration_deadline,
            'entry_fee' => $request->entry_fee,
            'max_participants' => $request->max_participants,
            'status' => 'registration',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'tournament' => $tournament,
            'message' => 'Tournament created successfully'
        ], 201);
    }

    /**
     * Initialize tournament matches at specific level
     */
    public function initializeTournament(Request $request, $tournamentId)
    {
        \Log::info("=== DashboardController::initializeTournament called (SHOULD NOT BE USED) ===");
        \Log::info("Tournament ID: " . $tournamentId);
        \Log::info("This controller method should be disabled - use AdminTournamentController instead");
        
        return response()->json([
            'error' => 'This endpoint is deprecated. Use /api/admin/tournaments/{id}/initialize instead.'
        ], 410);
        
        $tournament = Tournament::findOrFail($tournamentId);

        $request->validate([
            'level' => 'required|in:community,county,regional,national'
        ]);

        $level = $request->level;

        try {
            // Get registrations for this tournament with location data
            $registrations = DB::table('registered_users')
                ->join('users', 'registered_users.player_id', '=', 'users.id')
                ->leftJoin('communities', 'users.community_id', '=', 'communities.id')
                ->leftJoin('counties', 'communities.county_id', '=', 'counties.id')
                ->leftJoin('regions', 'counties.region_id', '=', 'regions.id')
                ->where('registered_users.tournament_id', $tournamentId)
                ->where('registered_users.payment_status', 'completed')
                ->select([
                    'users.id as player_id',
                    'users.name as player_name',
                    'communities.id as community_id',
                    'communities.name as community_name',
                    'counties.id as county_id',
                    'counties.name as county_name',
                    'regions.id as region_id',
                    'regions.name as region_name'
                ])
                ->get();

            if ($registrations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No registered players found for this tournament'
                ], 400);
            }

            // Group players by the specified level
            $groups = [];
            switch ($level) {
                case 'community':
                    $groups = $registrations->groupBy('community_id');
                    break;
                case 'county':
                    $groups = $registrations->groupBy('county_id');
                    break;
                case 'regional':
                    $groups = $registrations->groupBy('region_id');
                    break;
                case 'national':
                    $groups = collect(['national' => $registrations]);
                    break;
            }

            $matchesCreated = 0;
            foreach ($groups as $groupId => $players) {
                if ($players->count() >= 2) {
                    // Create matches for this group using the match service
                    $result = $this->matchService->initializeLevel($tournament->id, $level, $groupId, $players->pluck('player_id')->toArray());
                    $matchesCreated += $result['matches_created'] ?? 0;
                }
            }

            // Update tournament status if this is the first initialization
            if ($tournament->status === 'registration') {
                $tournament->update(['status' => 'upcoming']);
            }

            return response()->json([
                'success' => true,
                'message' => "Tournament initialized at {$level} level successfully",
                'matches_created' => $matchesCreated,
                'groups_created' => $groups->count(),
                'level' => $level
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize tournament: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tournament progress and matches
     */
    public function getTournamentProgress($tournamentId)
    {
        $tournament = Tournament::with([
            'approvedPlayers.community.county.region',
            'matches.player1',
            'matches.player2'
        ])->findOrFail($tournamentId);

        $levels = ['community', 'county', 'regional', 'national'];
        $progress = [];

        foreach ($levels as $level) {
            $matches = $tournament->matches->where('level', $level);
            $completedMatches = $matches->where('status', 'completed');
            $pendingMatches = $matches->where('status', 'pending');
            $scheduledMatches = $matches->where('status', 'scheduled');

            $winners = Winner::where('tournament_id', $tournamentId)
                ->where('level', $level)
                ->with('player')
                ->get();

            $progress[$level] = [
                'total_matches' => $matches->count(),
                'completed_matches' => $completedMatches->count(),
                'pending_matches' => $pendingMatches->count(),
                'scheduled_matches' => $scheduledMatches->count(),
                'winners_count' => $winners->count(),
                'is_complete' => $matches->count() > 0 && $completedMatches->count() === $matches->count(),
                'matches' => $matches->values(),
                'winners' => $winners
            ];
        }

        return response()->json([
            'tournament' => $tournament,
            'progress' => $progress
        ]);
    }

    /**
     * Initialize next level of tournament
     */
    public function initializeNextLevel(Request $request, $tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        $request->validate([
            'current_level' => 'required|in:community,county,regional,national'
        ]);

        $currentLevel = $request->current_level;
        $nextLevel = $this->getNextLevel($currentLevel);

        if (!$nextLevel) {
            return response()->json([
                'message' => 'No next level available after ' . $currentLevel
            ], 400);
        }

        try {
            // Check if ALL groups at current level are complete
            $currentMatches = PoolMatch::where('tournament_id', $tournamentId)
                ->where('level', $currentLevel)
                ->get();

            $completedMatches = $currentMatches->where('status', 'completed');

            // Check that all matches are completed
            if ($currentMatches->count() === 0 || $completedMatches->count() !== $currentMatches->count()) {
                return response()->json([
                    'message' => 'All matches at ' . $currentLevel . ' level must be completed before initializing next level'
                ], 400);
            }

            // Additional check: Ensure all participating groups/communities have winners
            $expectedWinners = $this->getExpectedWinnersCount($tournamentId, $currentLevel);
            $actualWinners = Winner::where('tournament_id', $tournamentId)
                ->where('level', $currentLevel)
                ->where('position', 1)
                ->count();

            if ($actualWinners < $expectedWinners) {
                return response()->json([
                    'message' => "Not all {$currentLevel} groups have completed. Expected {$expectedWinners} winners, found {$actualWinners}"
                ], 400);
            }

            $result = $this->matchService->initializeLevel($tournament->id, $nextLevel);

            return response()->json([
                'message' => "Successfully initialized {$nextLevel} level",
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initialize next level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate expected number of winners for a tournament level
     */
    private function getExpectedWinnersCount($tournamentId, $level)
    {
        switch ($level) {
            case 'community':
                // Count distinct communities participating in this tournament
                return PoolMatch::where('tournament_id', $tournamentId)
                    ->where('level', 'community')
                    ->distinct('level_id')
                    ->count('level_id');
                    
            case 'county':
                // Count distinct counties that had community winners
                return Winner::where('tournament_id', $tournamentId)
                    ->where('level', 'community')
                    ->where('position', 1)
                    ->join('users', 'winners.player_id', '=', 'users.id')
                    ->distinct('users.county_id')
                    ->count('users.county_id');
                    
            case 'regional':
                // Count distinct regions that had county winners
                return Winner::where('tournament_id', $tournamentId)
                    ->where('level', 'county')
                    ->where('position', 1)
                    ->join('users', 'winners.player_id', '=', 'users.id')
                    ->join('counties', 'users.county_id', '=', 'counties.id')
                    ->distinct('counties.region_id')
                    ->count('counties.region_id');
                    
            case 'national':
                // Should have one winner per region
                return Winner::where('tournament_id', $tournamentId)
                    ->where('level', 'regional')
                    ->where('position', 1)
                    ->count();
                    
            default:
                return 1;
        }
    }

    /**
     * Get all winners for all levels and groups
     */
    public function getAllWinners($tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        $winners = Winner::where('tournament_id', $tournamentId)
            ->with(['player.community.county.region'])
            ->orderBy('level')
            ->orderBy('level_id')
            ->orderBy('position')
            ->get()
            ->groupBy(['level', 'level_id']);

        return response()->json([
            'tournament' => $tournament,
            'winners' => $winners
        ]);
    }

    /**
     * Get tournament registrations with user location details
     */
    public function getTournamentRegistrations($tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        $registrations = DB::table('registered_users')
            ->join('users', 'registered_users.player_id', '=', 'users.id')
            ->leftJoin('communities', 'users.community_id', '=', 'communities.id')
            ->leftJoin('counties', 'communities.county_id', '=', 'counties.id')
            ->leftJoin('regions', 'counties.region_id', '=', 'regions.id')
            ->where('registered_users.tournament_id', $tournamentId)
            ->select([
                'registered_users.*',
                'users.name as player_name',
                'users.email as player_email',
                'users.phone as player_phone',
                'communities.name as community_name',
                'counties.name as county_name',
                'regions.name as region_name'
            ])
            ->orderBy('registered_users.registration_date', 'desc')
            ->get();

        // Group registrations by location hierarchy
        $byRegion = $registrations->groupBy('region_name');
        $byCounty = $registrations->groupBy('county_name');
        $byCommunity = $registrations->groupBy('community_name');

        $statistics = [
            'total_registrations' => $registrations->count(),
            'regions_represented' => $byRegion->count(),
            'counties_represented' => $byCounty->count(),
            'communities_represented' => $byCommunity->count(),
            'completed_payments' => $registrations->where('payment_status', 'completed')->count(),
            'pending_payments' => $registrations->where('payment_status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'tournament' => $tournament,
            'registrations' => $registrations,
            'statistics' => $statistics,
            'grouped_by_region' => $byRegion,
            'grouped_by_county' => $byCounty,
            'grouped_by_community' => $byCommunity
        ]);
    }

    /**
     * Send notification to all players
     */
    public function sendNotificationToPlayers(Request $request, $tournamentId)
    {
        $request->validate([
            'message' => 'required|string',
            'type' => 'required|in:general,match,tournament,system'
        ]);

        $tournament = Tournament::with('approvedPlayers')->findOrFail($tournamentId);

        $notifications = [];
        foreach ($tournament->approvedPlayers as $player) {
            $notification = Notification::create([
                'player_id' => $player->id,
                'type' => $request->type,
                'message' => $request->message,
                'data' => ['tournament_id' => $tournamentId]
            ]);
            $notifications[] = $notification;
        }

        return response()->json([
            'message' => 'Notification sent to all players',
            'notifications_sent' => count($notifications)
        ]);
    }

    /**
     * Get tournament details with player groupings
     */
    public function getTournamentDetails($tournamentId)
    {
        $tournament = Tournament::with([
            'approvedPlayers.community.county.region'
        ])->findOrFail($tournamentId);

        $playersByRegion = $tournament->approvedPlayers->groupBy('community.county.region.name');
        $playersByCounty = $tournament->approvedPlayers->groupBy('community.county.name');
        $playersByCommunity = $tournament->approvedPlayers->groupBy('community.name');

        $statistics = [
            'total_players' => $tournament->approvedPlayers->count(),
            'regions_count' => $playersByRegion->count(),
            'counties_count' => $playersByCounty->count(),
            'communities_count' => $playersByCommunity->count(),
        ];

        return response()->json([
            'tournament' => $tournament,
            'statistics' => $statistics,
            'players_by_region' => $playersByRegion,
            'players_by_county' => $playersByCounty,
            'players_by_community' => $playersByCommunity
        ]);
    }

    /**
     * Get all tournaments for admin dashboard
     */
    public function getAllTournaments()
    {
        $tournaments = Tournament::with(['approvedPlayers'])
            ->withCount(['matches', 'approvedPlayers'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'tournaments' => $tournaments
        ]);
    }

    private function getNextLevel($currentLevel)
    {
        $levels = ['community' => 'county', 'county' => 'regional', 'regional' => 'national'];
        return $levels[$currentLevel] ?? null;
    }
}
