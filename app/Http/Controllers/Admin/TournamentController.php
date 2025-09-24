<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Services\MatchAlgorithmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentController extends Controller
{
    protected $matchService;

    public function __construct(MatchAlgorithmService $matchService)
    {
        $this->matchService = $matchService;
        // Remove auth middleware for testing
        // $this->middleware('auth:sanctum');
        // $this->middleware('admin');
    }

    /**
     * List all tournaments
     */
    public function index(Request $request)
    {
        $query = Tournament::query();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $tournaments = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json($tournaments);
    }

    /**
     * Create a new tournament
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'special' => 'boolean',
            'community_prize' => 'nullable|numeric|min:0',
            'county_prize' => 'nullable|numeric|min:0',
            'regional_prize' => 'nullable|numeric|min:0',
            'national_prize' => 'nullable|numeric|min:0',
            'area_scope' => 'nullable|in:community,county,region,national',
            'area_name' => 'nullable|string|max:255',
            'tournament_charge' => 'required|numeric|min:0',
            'entry_fee' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'winners' => 'nullable|integer|min:1|max:50',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'registration_deadline' => 'required|date|before:start_date',
            'automation_mode' => 'required|in:automatic,manual'
        ]);
        
        // Set default values
        $validated['status'] = 'upcoming';
        $validated['special'] = $validated['special'] ?? false;
        $validated['entry_fee'] = $validated['entry_fee'] ?? 0;
        $validated['automation_mode'] = $validated['automation_mode'] ?? 'automatic';
        
        $tournament = Tournament::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Tournament created successfully',
            'tournament' => $tournament
        ], 201);
    }

    /**
     * Show a specific tournament
     */
    public function show(Tournament $tournament)
    {
        $tournament->load(['registeredUsers', 'matches']);
        
        return response()->json([
            'success' => true,
            'tournament' => $tournament,
            'registration_count' => $tournament->registeredUsers->count(),
            'matches_count' => $tournament->matches->count()
        ]);
    }

    /**
     * Update a tournament
     */
    public function update(Request $request, Tournament $tournament)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'special' => 'boolean',
            'community_prize' => 'nullable|numeric|min:0',
            'county_prize' => 'nullable|numeric|min:0',
            'regional_prize' => 'nullable|numeric|min:0',
            'national_prize' => 'nullable|numeric|min:0',
            'area_scope' => 'nullable|in:community,county,region,national',
            'area_name' => 'nullable|string|max:255',
            'tournament_charge' => 'required|numeric|min:0',
            'entry_fee' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_deadline' => 'required|date|before:start_date',
            'status' => 'nullable|in:upcoming,ongoing,completed',
            'automation_mode' => 'required|in:automatic,manual'
        ]);

        $tournament->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Tournament updated successfully',
            'tournament' => $tournament->fresh()
        ]);
    }

    /**
     * Delete a tournament
     */
    public function destroy(Tournament $tournament)
    {
        // Check if tournament has matches
        if ($tournament->matches()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete tournament with existing matches'
            ], 400);
        }

        $tournament->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Tournament deleted successfully'
        ]);
    }

    /**
     * Initialize tournament matches
     */
    public function initialize(Request $request, Tournament $tournament)
    {
        // IMMEDIATE logging - first line of method
        error_log("TOURNAMENT INITIALIZE METHOD HIT - ID: " . $tournament->id);
        
        // Add comprehensive logging at the very start
        file_put_contents(storage_path('logs/debug.log'), 
            "[" . date('Y-m-d H:i:s') . "] ===== INITIALIZE METHOD CALLED =====\n" .
            "Tournament ID: " . $tournament->id . "\n" .
            "Tournament Name: " . $tournament->name . "\n" .
            "Tournament Special: " . ($tournament->special ? 'YES' : 'NO') . "\n" .
            "Request Method: " . $request->method() . "\n" .
            "Request URL: " . $request->fullUrl() . "\n" .
            "Request Headers: " . json_encode($request->headers->all()) . "\n" .
            "Request Body: " . $request->getContent() . "\n" .
            "Auth Token Present: " . ($request->bearerToken() ? 'YES' : 'NO') . "\n" .
            "Authenticated User ID: " . (auth()->id() ?? 'NULL') . "\n" .
            "================================================\n", 
            FILE_APPEND | LOCK_EX
        );
        
        file_put_contents(storage_path('logs/tournament-init.log'), 
            "[" . date('Y-m-d H:i:s') . "] Tournament initialization started for ID: " . $tournament->id . "\n", 
            FILE_APPEND | LOCK_EX
        );
        // Laravel logging
        \Log::info("=== AdminTournamentController::initialize called ===");
        \Log::info("Tournament ID: " . $tournament->id);
        \Log::info("Request data: " . json_encode($request->all()));
        \Log::info("Request method: " . $request->method());
        \Log::info("Request URL: " . $request->fullUrl());
        \Log::info("Auth user: " . ($request->user() ? $request->user()->id : 'NONE'));
        
        try {
            \Log::info("Tournament found: " . $tournament->name . " (special: " . ($tournament->special ? 'yes' : 'no') . ")");
            
            // For special tournaments, no level validation needed
            if ($tournament->special) {
                $level = 'special';
                \Log::info("Special tournament detected, using level: special");
            } else {
                $validated = $request->validate([
                    'level' => 'required|in:community,county,regional,national'
                ]);
                $level = $validated['level'];
                \Log::info("Regular tournament detected, using level: " . $level);
            }
            
            \Log::info("Calling MatchAlgorithmService::initialize with tournament ID: " . $tournament->id . " and level: " . $level);
            $result = $this->matchService->initialize($tournament->id, $level);
            \Log::info("MatchAlgorithmService returned: " . json_encode($result));
            
            // Update tournament status if needed
            $updated = Tournament::where('id', $tournament->id)
                ->where('status', 'registration')
                ->update(['status' => 'ongoing']);
            \Log::info("Tournament status updated: " . ($updated ? 'yes' : 'no'));
            
            \Log::info("Returning success response");
            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error("Tournament initialization failed: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            // Log error to debug file too
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents(storage_path('logs/debug.log'), "[$timestamp] ERROR: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate next round
     */
    public function generateNextRound(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'level' => 'required|in:community,county,regional,national,special',
            'group_id' => 'nullable|integer'
        ]);
        
        try {
            $result = $this->matchService->generateNextRound(
                $tournamentId, 
                $validated['level'], 
                $validated['group_id'] ?? null
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Check tournament/level completion status
     */
    public function checkCompletion(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'level' => 'required|in:community,county,regional,national,special',
            'group_id' => 'nullable|integer'
        ]);
        
        $result = $this->matchService->checkLevelCompletion(
            $tournamentId,
            $validated['level'],
            $validated['group_id'] ?? null
        );
        
        return response()->json($result);
    }

    /**
     * Get tournament matches
     */
    public function matches(Request $request, $tournamentId)
    {
        $query = PoolMatch::where('tournament_id', $tournamentId)
            ->with(['player1', 'player2', 'winner']);
        
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }
        
        if ($request->has('round_name')) {
            $query->where('round_name', $request->round_name);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }
        
        $matches = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json($matches);
    }

    /**
     * Get tournament statistics
     */
    public function statistics($tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        
        $stats = [
            'total_registered' => $tournament->registeredUsers()->count(),
            'approved_players' => $tournament->approvedPlayers()->count(),
            'total_matches' => $tournament->matches()->count(),
            'completed_matches' => $tournament->matches()->whereIn('status', ['completed', 'forfeit'])->count(),
            'pending_matches' => $tournament->matches()->where('status', 'pending')->count(),
            'in_progress_matches' => $tournament->matches()->where('status', 'in_progress')->count(),
            'levels' => []
        ];
        
        $levels = ['community', 'county', 'regional', 'national'];
        if ($tournament->special) {
            $levels = ['special'];
        }
        
        foreach ($levels as $level) {
            $levelMatches = $tournament->matches()->where('level', $level);
            $stats['levels'][$level] = [
                'total' => $levelMatches->count(),
                'completed' => (clone $levelMatches)->whereIn('status', ['completed', 'forfeit'])->count(),
                'pending' => (clone $levelMatches)->where('status', 'pending')->count()
            ];
        }
        
        return response()->json($stats);
    }

    /**
     * Update tournament automation mode
     */
    public function updateAutomationMode(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'automation_mode' => 'required|in:automatic,manual'
        ]);
        
        $tournament = Tournament::findOrFail($tournamentId);
        $tournament->update(['automation_mode' => $validated['automation_mode']]);
        
        return response()->json([
            'message' => 'Automation mode updated successfully',
            'tournament' => $tournament
        ]);
    }

    /**
     * Get pending approvals (groups that completed their rounds)
     */
    public function pendingApprovals($tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $pendingGroups = [];
        
        // Check each level and group
        $levels = $tournament->special ? ['special'] : ['community', 'county', 'regional'];
        
        foreach ($levels as $level) {
            $groups = PoolMatch::where('tournament_id', $tournamentId)
                ->where('level', $level)
                ->distinct()
                ->pluck('group_id');
            
            foreach ($groups as $groupId) {
                $completion = $this->matchService->checkLevelCompletion($tournamentId, $level, $groupId);
                if ($completion['completed']) {
                    $pendingGroups[] = [
                        'level' => $level,
                        'group_id' => $groupId,
                        'group_name' => $this->getGroupName($level, $groupId),
                        'ready_for_next_level' => true
                    ];
                }
            }
        }
        
        return response()->json($pendingGroups);
    }

    /**
     * Get group name
     */
    private function getGroupName($level, $groupId)
    {
        if (!$groupId) return $level;
        
        switch ($level) {
            case 'community':
                $model = \App\Models\Community::find($groupId);
                break;
            case 'county':
                $model = \App\Models\County::find($groupId);
                break;
            case 'regional':
                $model = \App\Models\Region::find($groupId);
                break;
            default:
                return $level;
        }
        
        return $model ? $model->name : "{$level} #{$groupId}";
    }
}
