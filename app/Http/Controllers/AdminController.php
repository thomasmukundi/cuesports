<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Message;
use App\Models\Community;
use App\Models\Region;
use App\Models\County;
use App\Models\Winner;
use App\Models\Notification;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function __construct()
    {
        // No middleware in constructor - handle in routes instead
    }

    public function showLogin()
    {
        if (auth()->check() && auth()->user() && auth()->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            if ($user && $user->is_admin) {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            } else {
                auth()->logout();
                return back()->withErrors(['email' => 'Access denied. Admin privileges required.']);
            }
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function dashboard()
    {
        // Check admin privileges in controller since middleware was removed
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $stats = [
                'total_tournaments' => Tournament::count() ?? 0,
                'total_users' => User::count() ?? 0,
                'total_matches' => PoolMatch::count() ?? 0,
                'total_enrollments' => DB::table('registered_users')->count() ?? 0,
                'active_tournaments' => Tournament::where('status', 'active')->count() ?? 0,
                'completed_tournaments' => Tournament::where('status', 'completed')->count() ?? 0,
                'pending_tournaments' => Tournament::where('status', 'pending')->count() ?? 0,
                'upcoming_tournaments' => Tournament::where('status', 'upcoming')->count() ?? 0,
                'ongoing_matches' => PoolMatch::whereIn('status', ['pending', 'in_progress', 'scheduled'])->count() ?? 0,
                'completed_matches' => PoolMatch::where('status', 'completed')->count() ?? 0,
            ];

            // Generate real user growth data for the last 7 days
            $userGrowth = $this->getUserGrowthData();

            return view('admin.dashboard', compact('stats', 'userGrowth'));
        } catch (\Exception $e) {
            // Fallback with default data if database queries fail
            $stats = [
                'total_tournaments' => 0,
                'total_users' => 0,
                'total_matches' => 0,
                'total_enrollments' => 0,
                'active_tournaments' => 0,
                'completed_tournaments' => 0,
                'pending_tournaments' => 0,
            ];

            // Fallback user growth data
            $userGrowth = [
                'labels' => ['6 days ago', '5 days ago', '4 days ago', '3 days ago', '2 days ago', 'Yesterday', 'Today'],
                'data' => [0, 0, 0, 0, 0, 0, 0]
            ];

            return view('admin.dashboard', compact('stats', 'userGrowth'));
        }
    }

    /**
     * Get user growth data for the last 7 days
     */
    private function getUserGrowthData($days = 7)
    {
        $labels = [];
        $data = [];
        
        // Generate data for the specified number of days
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            // Create label
            if ($i === 0) {
                $labels[] = 'Today';
            } elseif ($i === 1) {
                $labels[] = 'Yesterday';
            } else {
                $labels[] = $date->format('M j'); // e.g., "Oct 1"
            }
            
            // Count users created on this date
            $userCount = User::whereDate('created_at', $date->format('Y-m-d'))->count();
            $data[] = $userCount;
            
            // Log for debugging
            \Log::info('User growth data point', [
                'date' => $date->format('Y-m-d'),
                'label' => end($labels),
                'user_count' => $userCount
            ]);
        }
        
        // Log summary
        \Log::info('User growth data generated', [
            'total_days' => count($labels),
            'labels' => $labels,
            'data' => $data,
            'total_new_users' => array_sum($data)
        ]);
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    public function tournaments(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $query = Tournament::query()
                ->addSelect([
                    'registrations_count' => DB::table('registered_users')
                        ->whereColumn('registered_users.tournament_id', 'tournaments.id')
                        ->selectRaw('COUNT(*)')
                ]);

            // Apply search filter
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('location', 'like', '%' . $request->search . '%');
            }

            // Apply status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $tournaments = $query->orderBy('created_at', 'desc')->paginate(10);
            $tournaments->appends($request->query());
            
            return view('admin.tournaments', compact('tournaments'));
        } catch (\Exception $e) {
            $tournaments = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            return view('admin.tournaments', compact('tournaments'));
        }
    }

    public function matches(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $query = PoolMatch::with(['tournament', 'player1', 'player2']);

            // Apply search filter
            if ($request->filled('search')) {
                $query->where('match_name', 'like', '%' . $request->search . '%')
                      ->orWhereHas('tournament', function($q) use ($request) {
                          $q->where('name', 'like', '%' . $request->search . '%');
                      });
            }

            // Apply status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Apply winners filter (only completed matches with winners)
            if ($request->filled('winners_only') && $request->winners_only == '1') {
                $query->where('status', 'completed')->whereNotNull('winner_id');
            }

            $matches = $query->orderBy('created_at', 'desc')->paginate(10);
            $matches->appends($request->query());
            
            return view('admin.matches', compact('matches'));
        } catch (\Exception $e) {
            $matches = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            return view('admin.matches', compact('matches'));
        }
    }

    /**
     * Update match status
     */
    public function updateMatchStatus(Request $request, $matchId)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $request->validate([
                'status' => 'required|in:pending,scheduled,in_progress,pending_confirmation,completed,forfeit'
            ]);

            $match = PoolMatch::findOrFail($matchId);
            $oldStatus = $match->status;
            $newStatus = $request->status;

            \Log::info('Admin updating match status', [
                'match_id' => $match->id,
                'admin_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'match_name' => $match->match_name,
                'tournament_id' => $match->tournament_id
            ]);

            // Update the status
            $match->status = $newStatus;
            $match->save();

            \Log::info('Match status updated successfully', [
                'match_id' => $match->id,
                'status_changed_from' => $oldStatus,
                'status_changed_to' => $newStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Match status updated successfully',
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status provided',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update match status', [
                'match_id' => $matchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update match status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function players(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $query = User::query()
                ->addSelect([
                    'tournaments_count' => DB::table('registered_users')
                        ->whereColumn('registered_users.player_id', 'users.id')
                        ->selectRaw('COUNT(DISTINCT tournament_id)')
                ]);

            // Apply search filter
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('phone', 'like', '%' . $request->search . '%');
            }

            // Apply region filter
            if ($request->filled('region')) {
                $query->whereHas('community', function($q) use ($request) {
                    $q->where('region_id', $request->region);
                });
            }

            // Apply county filter
            if ($request->filled('county')) {
                $query->whereHas('community', function($q) use ($request) {
                    $q->where('county_id', $request->county);
                });
            }

            // Apply community filter
            if ($request->filled('community')) {
                $query->where('community_id', $request->community);
            }

            // Apply status filter
            if ($request->filled('status')) {
                if ($request->status == 'active') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->status == 'inactive') {
                    $query->whereNull('email_verified_at');
                }
            }

            $players = $query->orderBy('created_at', 'desc')->paginate(10);
            $players->appends($request->query());
        
            // Get filter options from database
            $regions = Region::all();
            $counties = County::all();
            $communities = Community::all();
        
            return view('admin.players', compact('players', 'regions', 'counties', 'communities'));
        } catch (\Exception $e) {
            $players = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            $regions = Region::all();
            $counties = County::all();
            $communities = Community::all();
            return view('admin.players', compact('players', 'regions', 'counties', 'communities'));
        }
    }

    public function messages(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $query = Message::with('user');

            // Apply search filter
            if ($request->filled('search')) {
                $query->where('subject', 'like', '%' . $request->search . '%')
                      ->orWhere('message', 'like', '%' . $request->search . '%')
                      ->orWhereHas('user', function($q) use ($request) {
                          $q->where('name', 'like', '%' . $request->search . '%');
                      });
            }

            // Apply status filter
            if ($request->filled('status')) {
                if ($request->status == 'read') {
                    $query->whereNotNull('read_at');
                } elseif ($request->status == 'unread') {
                    $query->whereNull('read_at');
                }
            }

            $messages = $query->orderBy('created_at', 'desc')->paginate(10);
            $messages->appends($request->query());
            
            return view('admin.messages', compact('messages'));
        } catch (\Exception $e) {
            $messages = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            return view('admin.messages', compact('messages'));
        }
    }

    public function communities(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $query = Community::with(['county', 'region'])
                ->withCount('users as members_count')
                ->addSelect([
                    'tournaments_count' => DB::table('registered_users')
                        ->join('users', 'registered_users.player_id', '=', 'users.id')
                        ->whereColumn('users.community_id', 'communities.id')
                        ->selectRaw('COUNT(DISTINCT registered_users.tournament_id)')
                ]);

            // Apply search filter
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhereHas('county', function($q) use ($request) {
                          $q->where('name', 'like', '%' . $request->search . '%');
                      })
                      ->orWhereHas('region', function($q) use ($request) {
                          $q->where('name', 'like', '%' . $request->search . '%');
                      });
            }

            // Apply region filter
            if ($request->filled('region')) {
                $query->where('region_id', $request->region);
            }

            // Apply county filter
            if ($request->filled('county')) {
                $query->where('county_id', $request->county);
            }

            $communities = $query->orderBy('created_at', 'desc')->paginate(10);
            $communities->appends($request->query());
        
            // Get filter options from database
            $regions = Region::all();
            $counties = County::all();
        
            return view('admin.communities', compact('communities', 'regions', 'counties'));
        } catch (\Exception $e) {
            $communities = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            $regions = Region::all();
            $counties = County::all();
            return view('admin.communities', compact('communities', 'regions', 'counties'));
        }
    }

    public function createTournament()
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        return view('admin.tournaments.create');
    }

    public function storeTournament(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'special' => 'required|boolean',
            'community_prize' => 'nullable|numeric|min:0',
            'county_prize' => 'nullable|numeric|min:0',
            'regional_prize' => 'nullable|numeric|min:0',
            'national_prize' => 'nullable|numeric|min:0',
            'area_scope' => 'nullable|in:community,county,regional,national',
            'area_name' => 'nullable|string|max:255',
            'tournament_charge' => 'nullable|numeric|min:0',
            'entry_fee' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'required|date|before_or_equal:start_date',
            'status' => 'required|in:upcoming,pending,active,completed',
            'automation_mode' => 'required|in:automatic,manual',
            'winners' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $data = $request->all();
            
            // Set defaults for nullable fields
            $data['community_prize'] = $data['community_prize'] ?? 0.00;
            $data['county_prize'] = $data['county_prize'] ?? 0.00;
            $data['regional_prize'] = $data['regional_prize'] ?? 0.00;
            $data['national_prize'] = $data['national_prize'] ?? 0.00;
            $data['tournament_charge'] = $data['tournament_charge'] ?? 0.00;
            $data['entry_fee'] = $data['entry_fee'] ?? 0.00;
            $data['created_by'] = auth()->id();
            
            // Handle nullable area_scope and area_name
            if (empty($data['area_scope'])) {
                $data['area_scope'] = null;
            }
            if (empty($data['area_name'])) {
                $data['area_name'] = null;
            }
            
            // Handle nullable winners field
            if (empty($data['winners'])) {
                $data['winners'] = null;
            }
            
            $tournament = Tournament::create($data);
            
            // Log tournament creation
            \Log::info('🏆 New tournament created via web interface', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'created_by' => 'Admin (ID: ' . auth()->id() . ')',
                'entry_fee' => $tournament->entry_fee,
                'max_participants' => $tournament->max_participants,
                'registration_deadline' => $tournament->registration_deadline,
                'start_date' => $tournament->start_date,
                'area_scope' => $tournament->area_scope,
                'area_name' => $tournament->area_name,
                'special' => $tournament->special
            ]);
            
            // Send tournament notifications efficiently without jobs
            $this->sendTournamentNotificationsOptimized($tournament);
            
            return redirect()->route('admin.tournaments')->with('success', 'Tournament created successfully! Notifications have been sent to eligible players.');
        } catch (\Exception $e) {
            \Log::error('Tournament creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create tournament. Please try again.'])->withInput();
        }
    }

    public function editTournament(Tournament $tournament)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        return view('admin.tournaments.edit', compact('tournament'));
    }

    public function updateTournament(Request $request, Tournament $tournament)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'special' => 'required|boolean',
            'community_prize' => 'nullable|numeric|min:0',
            'county_prize' => 'nullable|numeric|min:0',
            'regional_prize' => 'nullable|numeric|min:0',
            'national_prize' => 'nullable|numeric|min:0',
            'area_scope' => 'nullable|in:community,county,regional,national',
            'area_name' => 'nullable|string|max:255',
            'tournament_charge' => 'nullable|numeric|min:0',
            'entry_fee' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'required|date|before_or_equal:start_date',
            'status' => 'required|in:upcoming,pending,active,completed',
            'automation_mode' => 'required|in:automatic,manual',
            'winners' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $data = $request->all();
            
            // Set defaults for nullable fields
            $data['community_prize'] = $data['community_prize'] ?? 0.00;
            $data['county_prize'] = $data['county_prize'] ?? 0.00;
            $data['regional_prize'] = $data['regional_prize'] ?? 0.00;
            $data['national_prize'] = $data['national_prize'] ?? 0.00;
            $data['tournament_charge'] = $data['tournament_charge'] ?? 0.00;
            $data['entry_fee'] = $data['entry_fee'] ?? 0.00;
            
            // Handle nullable area_scope and area_name
            if (empty($data['area_scope'])) {
                $data['area_scope'] = null;
            }
            if (empty($data['area_name'])) {
                $data['area_name'] = null;
            }
            
            // Handle nullable winners field
            if (empty($data['winners'])) {
                $data['winners'] = null;
            }
            
            $tournament->update($data);
            return redirect()->route('admin.tournaments')->with('success', 'Tournament updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Tournament update failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update tournament. Please try again.'])->withInput();
        }
    }

    public function deleteTournament(Tournament $tournament)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $tournament->delete();
            return redirect()->route('admin.tournaments')->with('success', 'Tournament deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete tournament. Please try again.']);
        }
    }

    public function viewCommunity(Community $community)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        \Log::info('Viewing community: ' . $community->id . ' - ' . $community->name);

        try {
            // Load community with relationships
            \Log::info('Loading community relationships');
            $community->load(['county.region']);
            \Log::info('Community relationships loaded');
            
            // Get community statistics
            \Log::info('Calculating community statistics');
            $stats = [
                'total_players' => $community->users()->count(),
                'active_players' => $community->users()->whereNotNull('email_verified_at')->count(),
                'tournaments_participated' => DB::table('registered_users')
                    ->join('users', 'registered_users.player_id', '=', 'users.id')
                    ->where('users.community_id', $community->id)
                    ->distinct('registered_users.tournament_id')
                    ->count(),
                'total_matches' => PoolMatch::whereHas('player1', function($q) use ($community) {
                        $q->where('community_id', $community->id);
                    })->orWhereHas('player2', function($q) use ($community) {
                        $q->where('community_id', $community->id);
                    })->count(),
                'matches_won' => PoolMatch::where('status', 'completed')
                    ->where(function($query) use ($community) {
                        $query->whereHas('player1', function($q) use ($community) {
                            $q->where('community_id', $community->id);
                        })->where('winner_id', '!=', null)
                        ->whereColumn('winner_id', 'player_1_id');
                    })->orWhere(function($query) use ($community) {
                        $query->whereHas('player2', function($q) use ($community) {
                            $q->where('community_id', $community->id);
                        })->where('winner_id', '!=', null)
                        ->whereColumn('winner_id', 'player_2_id');
                    })->count()
            ];
            \Log::info('Statistics calculated: ' . json_encode($stats));
            
            // Get recent players
            \Log::info('Fetching recent players');
            $recentPlayers = $community->users()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            \Log::info('Recent players fetched: ' . $recentPlayers->count());
            
            // Get tournaments the community has participated in
            \Log::info('Fetching tournaments');
            $tournaments = Tournament::whereHas('registeredUsers', function($q) use ($community) {
                $q->where('community_id', $community->id);
            })->withCount(['registeredUsers as community_participants' => function($q) use ($community) {
                $q->where('community_id', $community->id);
            }])->orderBy('created_at', 'desc')->paginate(10);
            \Log::info('Tournaments fetched: ' . $tournaments->count());
            
            // Get community awards/winners
            \Log::info('Fetching awards for community: ' . $community->id);
            $awards = Winner::whereHas('player', function($q) use ($community) {
                $q->where('community_id', $community->id);
            })->with(['player', 'tournament'])->orderBy('created_at', 'desc')->paginate(10);
            \Log::info('Awards fetched successfully: ' . $awards->count());
            
            return view('admin.communities.view', compact('community', 'stats', 'recentPlayers', 'tournaments', 'awards'));
            
        } catch (\Exception $e) {
            \Log::error('Community view error: ' . $e->getMessage());
            return redirect()->route('admin.communities')->withErrors(['error' => 'Failed to load community details: ' . $e->getMessage()]);
        }
    }

    public function createCommunity()
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        $regions = Region::all();
        $counties = County::all();
        return view('admin.communities.create', compact('regions', 'counties'));
    }

    public function storeCommunity(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'county_id' => 'required|exists:counties,id'
        ]);

        try {
            Community::create($request->all());
            return redirect()->route('admin.communities')->with('success', 'Community created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create community. Please try again.'])->withInput();
        }
    }

    public function editCommunity(Community $community)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        $regions = Region::all();
        $counties = County::all();
        return view('admin.communities.edit', compact('community', 'regions', 'counties'));
    }

    public function updateCommunity(Request $request, Community $community)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'county_id' => 'required|exists:counties,id'
        ]);

        try {
            $community->update($request->all());
            return redirect()->route('admin.communities')->with('success', 'Community updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update community. Please try again.'])->withInput();
        }
    }

    public function deleteCommunity(Community $community)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $community->delete();
            return redirect()->route('admin.communities')->with('success', 'Community deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete community. Please try again.']);
        }
    }

    public function replaceCommunitiesWithWards(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();
            
            // Read wards.txt file from root directory
            $wardsFilePath = base_path('wards.txt');
            if (!file_exists($wardsFilePath)) {
                return response()->json(['success' => false, 'message' => 'wards.txt file not found in root directory'], 404);
            }
            
            $wardsContent = file_get_contents($wardsFilePath);
            $lines = explode("\n", $wardsContent);
            
            // Parse wards data
            $wardsData = [];
            $currentCounty = null;
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line === '--------------------------------------------------' || $line === 'Wards:') {
                    continue;
                }
                
                if (strpos($line, 'County:') === 0) {
                    $currentCounty = trim(str_replace('County:', '', $line));
                } elseif (strpos($line, '- ') === 0 && $currentCounty) {
                    $ward = trim(str_replace('- ', '', $line));
                    if (!isset($wardsData[$currentCounty])) {
                        $wardsData[$currentCounty] = [];
                    }
                    $wardsData[$currentCounty][] = $ward;
                }
            }
            
            // Get communities that have players
            $communitiesWithPlayers = Community::whereHas('users')->pluck('id')->toArray();
            
            // Delete communities without players
            $deletedCount = Community::whereNotIn('id', $communitiesWithPlayers)->delete();
            
            // Create new communities from wards
            $createdCount = 0;
            $preservedCount = count($communitiesWithPlayers);
            
            foreach ($wardsData as $countyName => $wards) {
                // Find county by name (case-insensitive and handle variations)
                $county = County::whereRaw('UPPER(name) = ?', [strtoupper($countyName)])->first();
                
                // Handle common name variations
                if (!$county) {
                    $variations = [
                        'TAITA TAVETA' => 'Taita Taveta',
                        'TRANS NZOIA' => 'Trans-Nzoia',
                        'ELGEYO/MARAKWET' => 'Elgeyo-Marakwet',
                        'THARAKA-NITHI' => 'Tharaka-Nithi',
                        'WEST POKOT' => 'West Pokot',
                        'UASIN GISHU' => 'Uasin Gishu',
                        'MURANG\'A' => 'Murang\'a',
                        'HOMA BAY' => 'Homa Bay',
                        'TANA RIVER' => 'Tana River'
                    ];
                    
                    if (isset($variations[$countyName])) {
                        $county = County::where('name', $variations[$countyName])->first();
                    }
                }
                
                if (!$county) {
                    \Log::warning("County not found: {$countyName}");
                    continue;
                }
                
                foreach ($wards as $ward) {
                    // Check if ward community already exists
                    $existingCommunity = Community::where('name', $ward)
                        ->where('county_id', $county->id)
                        ->first();
                    
                    if (!$existingCommunity) {
                        Community::create([
                            'name' => $ward,
                            'county_id' => $county->id,
                            'region_id' => $county->region_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $createdCount++;
                    }
                }
            }
            
            DB::commit();
            
            // Log the action
            \Log::info('Communities replaced with wards', [
                'admin_user' => auth()->user()->email,
                'deleted_count' => $deletedCount,
                'created_count' => $createdCount,
                'preserved_count' => $preservedCount,
                'total_counties_processed' => count($wardsData),
                'counties_found' => array_keys($wardsData)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Communities successfully replaced with wards',
                'deleted_count' => $deletedCount,
                'created_count' => $createdCount,
                'preserved_count' => $preservedCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error replacing communities with wards: ' . $e->getMessage(), [
                'admin_user' => auth()->user()->email ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to replace communities with wards: ' . $e->getMessage()
            ], 500);
        }
    }

    public function transactions(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $query = \App\Models\Transaction::with(['user', 'tournament']);

            // Apply search filter
            if ($request->filled('search')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                })->orWhere('transaction_id', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%');
            }

            // Apply status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Apply payment method filter
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Apply date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate(15);
            $transactions->appends($request->query());
            
            return view('admin.transactions', compact('transactions'));
        } catch (\Exception $e) {
            $transactions = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
            return view('admin.transactions', compact('transactions'));
        }
    }

    public function showTransaction($id)
    {
        $transaction = \App\Models\Transaction::with(['user', 'tournament'])->findOrFail($id);
        return response()->json($transaction);
    }

    public function updateTransactionStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,cancelled'
        ]);

        $transaction = \App\Models\Transaction::findOrFail($id);
        $transaction->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function winners(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            $query = Winner::with(['player', 'tournament', 'player.community.county', 'player.community.region']);

            // Apply search filter
            if ($request->filled('search')) {
                $query->whereHas('player', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                })->orWhereHas('tournament', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                });
            }

            // Apply position filter
            if ($request->filled('position')) {
                $query->where('position', $request->position);
            }

            // Apply region filter
            if ($request->filled('region')) {
                $query->whereHas('player.community.region', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->region . '%');
                });
            }

            // Apply county filter
            if ($request->filled('county')) {
                $query->whereHas('player.community.county', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->county . '%');
                });
            }

            // Apply community filter
            if ($request->filled('community')) {
                $query->whereHas('player.community', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->community . '%');
                });
            }

            // Apply special matches filter
            if ($request->filled('special_matches') && $request->special_matches == '1') {
                $query->whereHas('tournament', function($q) {
                    $q->where('special', true);
                });
            }

            $winners = $query->orderBy('created_at', 'desc')->paginate(10);
            $winners->appends($request->query());
            
            // Calculate points and wins for each winner from matches table
            foreach ($winners as $winner) {
                // Get all matches for this player in this tournament
                $matches = \DB::table('matches')
                    ->where('tournament_id', $winner->tournament_id)
                    ->where('status', 'completed')
                    ->where(function($q) use ($winner) {
                        $q->where('player_1_id', $winner->player_id)
                          ->orWhere('player_2_id', $winner->player_id);
                    })
                    ->get();
                
                // Calculate total points scored by this player
                $totalPoints = 0;
                $totalWins = 0;
                
                foreach ($matches as $match) {
                    if ($match->player_1_id == $winner->player_id) {
                        $totalPoints += $match->player_1_points ?? 0;
                    } else {
                        $totalPoints += $match->player_2_points ?? 0;
                    }
                    
                    // Count wins
                    if ($match->winner_id == $winner->player_id) {
                        $totalWins++;
                    }
                }
                
                // Add calculated values to winner object
                $winner->calculated_points = $totalPoints;
                $winner->calculated_wins = $totalWins;
            }
            
            return view('admin.winners', compact('winners'));
        } catch (\Exception $e) {
            $winners = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            return view('admin.winners', compact('winners'));
        }
    }

    public function getCountiesByRegion($regionId)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $counties = County::where('region_id', $regionId)->orderBy('name')->get(['id', 'name']);
            return response()->json($counties);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch counties'], 500);
        }
    }

    public function getCommunitiesByCounty($countyId)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $communities = Community::where('county_id', $countyId)->orderBy('name')->get(['id', 'name']);
            return response()->json($communities);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch communities'], 500);
        }
    }

    /**
     * Get tournaments for API (used in communications)
     */
    public function getTournamentsApi()
    {
        try {
            $tournaments = Tournament::select('id', 'name', 'area_scope', 'area_name', 'entry_fee', 'registration_deadline', 'start_date')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'tournaments' => $tournaments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tournaments'
            ], 500);
        }
    }

    /**
     * Get all regions for API
     */
    public function getRegionsApi()
    {
        try {
            $regions = Region::orderBy('name')->get(['id', 'name']);
            return response()->json($regions);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch regions'], 500);
        }
    }

    /**
     * Get all counties for API
     */
    public function getCountiesApi()
    {
        try {
            $counties = County::orderBy('name')->get(['id', 'name']);
            return response()->json($counties);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch counties'], 500);
        }
    }

    /**
     * Get all communities for API
     */
    public function getCommunitiesApi()
    {
        try {
            $communities = Community::orderBy('name')->get(['id', 'name']);
            return response()->json($communities);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch communities'], 500);
        }
    }

    /**
     * Send tournament announcement emails automatically
     */
    private function sendTournamentAnnouncement(Tournament $tournament)
    {
        try {
            $emailService = new EmailService();
            
            // Get eligible recipients based on tournament scope
            $recipients = $this->getTournamentRecipients($tournament);
            
            if (empty($recipients)) {
                \Log::info('📧 No eligible recipients for tournament announcement', [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'area_scope' => $tournament->area_scope,
                    'area_name' => $tournament->area_name
                ]);
                return;
            }

            \Log::info('📧 Starting tournament announcement email send', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'total_recipients' => count($recipients),
                'area_scope' => $tournament->area_scope,
                'area_name' => $tournament->area_name
            ]);

            // Prepare tournament data for email
            $tournamentData = [
                'tournament_name' => $tournament->name,
                'tournament_description' => $tournament->description ?? 'Join this exciting tournament!',
                'registration_deadline' => $tournament->registration_deadline ? 
                    \Carbon\Carbon::parse($tournament->registration_deadline)->format('M j, Y g:i A') : 'TBD',
                'tournament_date' => $tournament->start_date ? 
                    \Carbon\Carbon::parse($tournament->start_date)->format('M j, Y') : null,
                'entry_fee' => $tournament->entry_fee ?? 0,
                'prize_pool' => ($tournament->community_prize ?? 0) + ($tournament->county_prize ?? 0) + ($tournament->regional_prize ?? 0) + ($tournament->national_prize ?? 0),
                'tournament_level' => $tournament->area_scope ?? 'open',
                'max_participants' => $tournament->max_participants ?? 100,
            ];

            // Send bulk emails using the optimized method
            $results = $emailService->sendBulkEmailsQueued(
                $recipients,
                'tournament_announcement',
                $tournamentData
            );

            \Log::info('📧 Tournament announcement emails completed', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'total_recipients' => $results['total'],
                'emails_sent' => $results['queued'],
                'emails_failed' => $results['failed']
            ]);

            // Send push notifications to eligible users
            $this->sendTournamentPushNotifications($tournament);

        } catch (\Exception $e) {
            \Log::error('❌ Failed to send tournament announcement emails', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get recipients for tournament announcement
     */
    private function getTournamentRecipients(Tournament $tournament): array
    {
        $query = User::select('email', 'name')
            ->whereNotNull('email')
            ->where(function($q) {
                $q->where('is_admin', '!=', true)
                  ->orWhereNull('is_admin');
            });

        // Special tournaments and national tournaments go to all users
        if ($tournament->special || $tournament->area_scope === 'national') {
            // Send to all verified users
        } else {
            // Filter based on tournament scope and area name
            if ($tournament->area_scope === 'community' && $tournament->area_name) {
                // Find community by exact name and get users from that community
                $community = Community::where('name', $tournament->area_name)->first();
                if ($community) {
                    $query->where('community_id', $community->id);
                } else {
                    // No matching community found, return empty array
                    return [];
                }
            } elseif ($tournament->area_scope === 'county' && $tournament->area_name) {
                // Find county by exact name and get users from that county
                $county = County::where('name', $tournament->area_name)->first();
                if ($county) {
                    $query->where('county_id', $county->id);
                } else {
                    // No matching county found, return empty array
                    return [];
                }
            } elseif ($tournament->area_scope === 'regional' && $tournament->area_name) {
                // Find region by exact name and get users from that region
                $region = Region::where('name', $tournament->area_name)->first();
                if ($region) {
                    $query->where('region_id', $region->id);
                } else {
                    // No matching region found, return empty array
                    return [];
                }
            }
        }

        $recipients = $query->get()->map(function ($user) {
            return [
                'email' => $user->email,
                'name' => $user->name
            ];
        })->toArray();

        \Log::info('📧 Tournament recipients selected', [
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name,
            'area_scope' => $tournament->area_scope,
            'area_name' => $tournament->area_name,
            'special' => $tournament->special,
            'total_recipients' => count($recipients),
            'recipients_preview' => array_slice($recipients, 0, 3) // Show first 3 for debugging
        ]);

        return $recipients;
    }

    /**
     * Send tournament notifications efficiently using bulk operations
     */
    private function sendTournamentNotificationsOptimized(Tournament $tournament)
    {
        try {
            \Log::info('🚀 Starting optimized tournament notifications', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'area_scope' => $tournament->area_scope,
                'area_name' => $tournament->area_name
            ]);

            // Get eligible users with a single optimized query
            $eligibleUsers = $this->getEligibleUsersOptimized($tournament);
            
            \Log::info('📱 Eligible users found', [
                'tournament_id' => $tournament->id,
                'area_scope' => $tournament->area_scope,
                'area_name' => $tournament->area_name,
                'user_count' => $eligibleUsers->count(),
                'first_few_users' => $eligibleUsers->take(3)->pluck('id', 'name')->toArray()
            ]);
            
            if ($eligibleUsers->isEmpty()) {
                \Log::info('📱 No eligible users found for tournament notifications', [
                    'tournament_id' => $tournament->id,
                    'area_scope' => $tournament->area_scope,
                    'area_name' => $tournament->area_name
                ]);
                return;
            }

            // Prepare notification data once
            $notificationData = [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'area_scope' => $tournament->area_scope,
                'area_name' => $tournament->area_name,
                'registration_deadline' => $tournament->registration_deadline,
                'start_date' => $tournament->start_date,
                'entry_fee' => $tournament->entry_fee ?? 0
            ];

            // Create all notifications with a single bulk insert
            $notifications = $eligibleUsers->map(function($user) use ($tournament, $notificationData) {
                return [
                    'player_id' => $user->id,
                    'type' => 'new_tournament',
                    'message' => "New tournament '{$tournament->name}' is now open for registration!",
                    'data' => json_encode($notificationData),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            // Single bulk insert - much faster than loops
            \Log::info('🔄 About to insert notifications', [
                'tournament_id' => $tournament->id,
                'notification_count' => count($notifications),
                'sample_notification' => $notifications[0] ?? null
            ]);
            
            \DB::table('notifications')->insert($notifications);

            \Log::info('✅ Tournament notifications sent successfully', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'notifications_sent' => count($notifications),
                'total_users' => $eligibleUsers->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Failed to send tournament notifications', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get eligible users with optimized single query
     */
    private function getEligibleUsersOptimized(Tournament $tournament)
    {
        $query = User::select('id', 'name', 'email')
            ->where(function($q) {
                $q->where('is_admin', '!=', true)
                  ->orWhereNull('is_admin');
            });

        // Apply area scope filtering efficiently
        if (!$tournament->special && $tournament->area_scope && $tournament->area_scope !== 'national') {
            switch ($tournament->area_scope) {
                case 'community':
                    if ($tournament->area_name) {
                        $community = Community::where('name', $tournament->area_name)->first();
                        if ($community) {
                            $query->where('community_id', $community->id);
                        } else {
                            // No community found, return empty collection
                            return collect();
                        }
                    }
                    break;
                    
                case 'county':
                    if ($tournament->area_name) {
                        $county = County::where('name', $tournament->area_name)->first();
                        if ($county) {
                            $query->where('county_id', $county->id);
                        } else {
                            // No county found, return empty collection
                            return collect();
                        }
                    }
                    break;
                    
                case 'regional':
                    if ($tournament->area_name) {
                        $region = Region::where('name', $tournament->area_name)->first();
                        if ($region) {
                            $query->where('region_id', $region->id);
                        } else {
                            // No region found, return empty collection
                            return collect();
                        }
                    }
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Send push notifications for tournament announcement
     */
    private function sendTournamentPushNotifications(Tournament $tournament)
    {
        try {
            // Get eligible users for push notifications (similar logic to email recipients)
            $query = User::select('id', 'name', 'fcm_token')
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->where(function($q) {
                    $q->where('is_admin', '!=', true)
                      ->orWhereNull('is_admin');
                });

            // Apply area scope filtering
            if (!$tournament->special && $tournament->area_scope && $tournament->area_scope !== 'national') {
                if ($tournament->area_scope === 'community' && $tournament->area_name) {
                    $community = Community::where('name', $tournament->area_name)->first();
                    if ($community) {
                        $query->where('community_id', $community->id);
                    } else {
                        \Log::warning('Community not found for tournament push notifications', [
                            'tournament_id' => $tournament->id,
                            'area_name' => $tournament->area_name
                        ]);
                        return;
                    }
                } elseif ($tournament->area_scope === 'county' && $tournament->area_name) {
                    $county = County::where('name', $tournament->area_name)->first();
                    if ($county) {
                        $query->where('county_id', $county->id);
                    } else {
                        \Log::warning('County not found for tournament push notifications', [
                            'tournament_id' => $tournament->id,
                            'area_name' => $tournament->area_name
                        ]);
                        return;
                    }
                } elseif ($tournament->area_scope === 'regional' && $tournament->area_name) {
                    $region = Region::where('name', $tournament->area_name)->first();
                    if ($region) {
                        $query->where('region_id', $region->id);
                    } else {
                        \Log::warning('Region not found for tournament push notifications', [
                            'tournament_id' => $tournament->id,
                            'area_name' => $tournament->area_name
                        ]);
                        return;
                    }
                }
            }

            $eligibleUsers = $query->get();

            if ($eligibleUsers->isEmpty()) {
                \Log::info('📱 No eligible users with FCM tokens for tournament push notifications', [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'area_scope' => $tournament->area_scope,
                    'area_name' => $tournament->area_name
                ]);
                return;
            }

            \Log::info('📱 Starting tournament push notifications', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'total_users' => $eligibleUsers->count(),
                'area_scope' => $tournament->area_scope,
                'area_name' => $tournament->area_name
            ]);

            $notificationsSent = 0;
            $notificationsFailed = 0;

            foreach ($eligibleUsers as $user) {
                try {
                    // Create notification in database
                    Notification::create([
                        'player_id' => $user->id,
                        'type' => 'admin_message',
                        'message' => "New tournament '{$tournament->name}' is now open for registration!",
                        'data' => [
                            'tournament_id' => $tournament->id,
                            'tournament_name' => $tournament->name,
                            'area_scope' => $tournament->area_scope,
                            'area_name' => $tournament->area_name,
                            'registration_deadline' => $tournament->registration_deadline,
                            'start_date' => $tournament->start_date,
                            'entry_fee' => $tournament->entry_fee ?? 0
                        ]
                    ]);

                    $notificationsSent++;
                } catch (\Exception $e) {
                    $notificationsFailed++;
                    \Log::error('Failed to create tournament notification for user', [
                        'user_id' => $user->id,
                        'tournament_id' => $tournament->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            \Log::info('📱 Tournament push notifications completed', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'notifications_sent' => $notificationsSent,
                'notifications_failed' => $notificationsFailed,
                'total_users' => $eligibleUsers->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Failed to send tournament push notifications', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }

    public function viewTournament(Request $request, Tournament $tournament)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            // Determine if tournament is special
            $isSpecial = $tournament->special ?? false;
            
            // Get current tournament level and progress
            $tournamentLevel = $this->getTournamentCurrentLevel($tournament);
            $levelProgress = $this->getTournamentLevelProgress($tournament);
            
            // Get matches with filtering
            $matchesQuery = PoolMatch::where('tournament_id', $tournament->id)
                ->with(['player1', 'player2', 'tournament']);
            
            // Apply level filter for non-special tournaments
            if (!$isSpecial && $request->filled('level')) {
                $matchesQuery->where('level', $request->level);
            }
            
            // Apply status filter
            if ($request->filled('status')) {
                $matchesQuery->where('status', $request->status);
            }
            
            // Apply round filter
            if ($request->filled('round')) {
                $matchesQuery->where('round_name', $request->round);
            }
            
            // Apply location filters independently (can be used with or without level)
            
            // Apply region filter
            if ($request->filled('region')) {
                $matchesQuery->where(function($q) use ($request) {
                    $q->whereHas('player1.community', function($subQ) use ($request) {
                        $subQ->where('region_id', $request->region);
                    })->orWhereHas('player2.community', function($subQ) use ($request) {
                        $subQ->where('region_id', $request->region);
                    });
                });
            }
            
            // Apply county filter
            if ($request->filled('county')) {
                $matchesQuery->where(function($q) use ($request) {
                    $q->whereHas('player1.community', function($subQ) use ($request) {
                        $subQ->where('county_id', $request->county);
                    })->orWhereHas('player2.community', function($subQ) use ($request) {
                        $subQ->where('county_id', $request->county);
                    });
                });
            }
            
            // Apply community filter
            if ($request->filled('community')) {
                $matchesQuery->where(function($q) use ($request) {
                    $q->whereHas('player1', function($subQ) use ($request) {
                        $subQ->where('community_id', $request->community);
                    })->orWhereHas('player2', function($subQ) use ($request) {
                        $subQ->where('community_id', $request->community);
                    });
                });
            }
            
            $matches = $matchesQuery->orderBy('created_at', 'desc')->paginate(15);
            $matches->appends($request->query());
            
            // Get available rounds for filtering
            $availableRounds = PoolMatch::where('tournament_id', $tournament->id)
                ->whereNotNull('round_name')
                ->distinct()
                ->pluck('round_name')
                ->filter()
                ->sort()
                ->values();
            
            // Get unplayed scheduled matches
            $unplayedMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('status', 'scheduled')
                ->whereDate('created_at', '<', now()->subDays(1))
                ->count();
            
            // Get filter options
            $regions = Region::all();
            $counties = County::all();
            $communities = Community::all();
            
            return view('admin.tournaments.view', compact(
                'tournament', 'matches', 'isSpecial', 'tournamentLevel', 'levelProgress',
                'availableRounds', 'unplayedMatches', 'regions', 'counties', 'communities'
            ));
            
        } catch (\Exception $e) {
            return redirect()->route('admin.tournaments')->withErrors(['error' => 'Failed to load tournament details.']);
        }
    }
    
    private function getTournamentCurrentLevel(Tournament $tournament)
    {
        // Check winners table to determine current level
        $levels = ['community', 'county', 'regional', 'national'];
        
        foreach ($levels as $level) {
            $hasWinners = Winner::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->exists();
                
            if (!$hasWinners) {
                return $level;
            }
        }
        
        return 'completed';
    }
    
    private function getTournamentLevelProgress(Tournament $tournament)
    {
        $progress = [
            'community' => ['completed' => false, 'can_initialize' => false, 'has_matches' => false, 'all_matches_completed' => false],
            'county' => ['completed' => false, 'can_initialize' => false, 'has_matches' => false, 'all_matches_completed' => false],
            'regional' => ['completed' => false, 'can_initialize' => false, 'has_matches' => false, 'all_matches_completed' => false],
            'national' => ['completed' => false, 'can_initialize' => false, 'has_matches' => false, 'all_matches_completed' => false],
        ];
        
        // Check if tournament start date is today or has passed
        $canStart = $tournament->start_date && $tournament->start_date <= now()->startOfDay();
        $hasStarted = $tournament->start_date && $tournament->start_date <= now();
        
        // Community level
        $communityMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->count();
        $communityCompletedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->where('status', 'completed')
            ->count();
        
        // Check if ALL communities with matches have winners
        $communitiesWithMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->distinct()
            ->pluck('group_id');
        $communitiesWithWinners = Winner::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->distinct()
            ->pluck('level_id');
        
        $allCommunitiesHaveWinners = $communitiesWithMatches->count() > 0 && 
            $communitiesWithMatches->diff($communitiesWithWinners)->isEmpty();
            
        $progress['community']['has_matches'] = $communityMatches > 0;
        $progress['community']['all_matches_completed'] = $communityMatches > 0 && $communityMatches === $communityCompletedMatches;
        $progress['community']['completed'] = $allCommunitiesHaveWinners;
        $progress['community']['can_initialize'] = $canStart && !$progress['community']['has_matches'];
        
        // County level
        $countyMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'county')
            ->count();
        $countyCompletedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'county')
            ->where('status', 'completed')
            ->count();
        
        // Check if ALL counties with matches have winners
        $countiesWithMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'county')
            ->distinct()
            ->pluck('group_id');
        $countiesWithWinners = Winner::where('tournament_id', $tournament->id)
            ->where('level', 'county')
            ->distinct()
            ->pluck('level_id');
        
        $allCountiesHaveWinners = $countiesWithMatches->count() > 0 && 
            $countiesWithMatches->diff($countiesWithWinners)->isEmpty();
            
        $progress['county']['has_matches'] = $countyMatches > 0;
        $progress['county']['all_matches_completed'] = $countyMatches > 0 && $countyMatches === $countyCompletedMatches;
        $progress['county']['completed'] = $allCountiesHaveWinners;
        $progress['county']['can_initialize'] = $allCommunitiesHaveWinners && !$progress['county']['has_matches'];
        
        // Regional level
        $regionalMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'regional')
            ->count();
        $regionalCompletedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'regional')
            ->where('status', 'completed')
            ->count();
        
        // Check if ALL regions with matches have winners
        $regionsWithMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'regional')
            ->distinct()
            ->pluck('group_id');
        $regionsWithWinners = Winner::where('tournament_id', $tournament->id)
            ->where('level', 'regional')
            ->distinct()
            ->pluck('level_id');
        
        $allRegionsHaveWinners = $regionsWithMatches->count() > 0 && 
            $regionsWithMatches->diff($regionsWithWinners)->isEmpty();
            
        $progress['regional']['has_matches'] = $regionalMatches > 0;
        $progress['regional']['all_matches_completed'] = $regionalMatches > 0 && $regionalMatches === $regionalCompletedMatches;
        $progress['regional']['completed'] = $allRegionsHaveWinners;
        $progress['regional']['can_initialize'] = $allCountiesHaveWinners && !$progress['regional']['has_matches'];
        
        // National level
        $nationalMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'national')
            ->count();
        $nationalCompletedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'national')
            ->where('status', 'completed')
            ->count();
        $nationalWinners = Winner::where('tournament_id', $tournament->id)
            ->where('level', 'national')
            ->exists();
            
        $progress['national']['has_matches'] = $nationalMatches > 0;
        $progress['national']['all_matches_completed'] = $nationalMatches > 0 && $nationalMatches === $nationalCompletedMatches;
        $progress['national']['completed'] = $nationalWinners;
        $progress['national']['can_initialize'] = $allRegionsHaveWinners && !$progress['national']['has_matches'];
        
        return $progress;
    }
    
    public function initializeTournamentLevel(Request $request, Tournament $tournament, $level)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }
        
        try {
            // Handle special tournaments
            if ($tournament->special && $level === 'special') {
                // Check if tournament can start
                $canStart = $tournament->start_date && $tournament->start_date <= now()->startOfDay();
                $hasMatches = \App\Models\PoolMatch::where('tournament_id', $tournament->id)->exists();
                
                if (!$canStart) {
                    return back()->withErrors(['error' => 'Special tournament can only be initialized on or after the start date.']);
                }
                
                if ($hasMatches) {
                    return back()->withErrors(['error' => 'Special tournament has already been initialized.']);
                }
                
                // Initialize special tournament using MatchAlgorithmService
                $matchService = new \App\Services\MatchAlgorithmService();
                $result = $matchService->initialize($tournament->id, 'special');
                
                // Update tournament status if needed
                if ($tournament->status === 'upcoming') {
                    $tournament->update(['status' => 'active']);
                }
                
                return redirect()->route('admin.tournaments.view', $tournament)
                    ->with('success', 'Special tournament initialized successfully! Created ' . ($result['matches_created'] ?? 0) . ' matches.');
            }
            
            // Handle regular level-based tournaments
            $validLevels = ['community', 'county', 'regional', 'national'];
            if (!in_array($level, $validLevels)) {
                return back()->withErrors(['error' => 'Invalid tournament level.']);
            }
            
            // Check if this is a special tournament trying to use level-based initialization
            if ($tournament->special) {
                return back()->withErrors(['error' => 'Special tournaments do not use level-based initialization.']);
            }
            
            // Check if level can be initialized
            $levelProgress = $this->getTournamentLevelProgress($tournament);
            if (!$levelProgress[$level]['can_initialize']) {
                return back()->withErrors(['error' => 'Cannot initialize this level at this time.']);
            }
            
            // Initialize tournament level using MatchAlgorithmService
            $matchService = new \App\Services\MatchAlgorithmService();
            $result = $matchService->initialize($tournament->id, $level);
            
            // Update tournament status if needed
            if ($tournament->status === 'upcoming') {
                $tournament->update(['status' => 'active']);
            }
            
            return redirect()->route('admin.tournaments.view', $tournament)
                ->with('success', ucfirst($level) . ' level initialized successfully! Created ' . ($result['matches_created'] ?? 0) . ' matches.');
                
        } catch (\Exception $e) {
            \Log::error('Tournament initialization failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to initialize tournament level.']);
        }
    }

    public function viewPlayer(Request $request, User $player)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            // Load player with relationships
            $player->load(['community.county.region']);
            
            // Get player's matches
            $matchesQuery = PoolMatch::where(function($query) use ($player) {
                $query->where('player_1_id', $player->id)
                      ->orWhere('player_2_id', $player->id);
            })->with(['player1', 'player2', 'tournament']);
            
            // Apply status filter
            if ($request->filled('match_status')) {
                $matchesQuery->where('status', $request->match_status);
            }
            
            $matches = $matchesQuery->orderBy('created_at', 'desc')->paginate(10, ['*'], 'matches_page');
            $matches->appends($request->query());
            
            // Get player's tournament registrations
            $tournamentsQuery = DB::table('registered_users')
                ->join('tournaments', 'registered_users.tournament_id', '=', 'tournaments.id')
                ->where('registered_users.player_id', $player->id)
                ->select('tournaments.*', 'registered_users.payment_status', 'registered_users.status as registration_status', 'registered_users.registration_date');
            
            // Apply tournament status filter
            if ($request->filled('tournament_status')) {
                $tournamentsQuery->where('tournaments.status', $request->tournament_status);
            }
            
            $tournaments = $tournamentsQuery->orderBy('registered_users.registration_date', 'desc')->paginate(10, ['*'], 'tournaments_page');
            
            // Get player statistics
            $stats = [
                'total_matches' => PoolMatch::where(function($query) use ($player) {
                    $query->where('player_1_id', $player->id)->orWhere('player_2_id', $player->id);
                })->count(),
                'matches_won' => PoolMatch::where('winner_id', $player->id)->count(),
                'matches_completed' => PoolMatch::where(function($query) use ($player) {
                    $query->where('player_1_id', $player->id)->orWhere('player_2_id', $player->id);
                })->where('status', 'completed')->count(),
                'tournaments_registered' => DB::table('registered_users')->where('player_id', $player->id)->count(),
                'total_points' => PoolMatch::where('winner_id', $player->id)->sum('player_1_points') + 
                                PoolMatch::where('winner_id', $player->id)->sum('player_2_points'),
            ];
            
            // Calculate win rate
            $stats['win_rate'] = $stats['matches_completed'] > 0 ? 
                round(($stats['matches_won'] / $stats['matches_completed']) * 100, 1) : 0;
            
            // Get awards/positions from winners table
            $awards = Winner::where('player_id', $player->id)
                ->with('tournament')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('admin.players.view', compact('player', 'matches', 'tournaments', 'stats', 'awards'));
            
        } catch (\Exception $e) {
            return redirect()->route('admin.players')->withErrors(['error' => 'Failed to load player details.']);
        }
    }

    public function deletePlayer(User $player)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        try {
            // Check if player is an admin to prevent deletion
            if ($player->is_admin) {
                return redirect()->route('admin.players')->withErrors(['error' => 'Cannot delete admin users.']);
            }

            $playerName = $player->name;
            $player->delete();
            
            return redirect()->route('admin.players')->with('success', "Player '{$playerName}' has been deleted successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.players')->withErrors(['error' => 'Failed to delete player. Please try again.']);
        }
    }

    public function bulkDeletePlayers(Request $request)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.login');
        }

        // Handle "select all" functionality
        if ($request->has('select_all_pages') && $request->select_all_pages == 'true') {
            $request->validate([
                'select_all_pages' => 'required|in:true'
            ]);
        } else {
            $request->validate([
                'player_ids' => 'required|array|min:1',
                'player_ids.*' => 'exists:users,id'
            ]);
        }

        try {
            if ($request->has('select_all_pages') && $request->select_all_pages == 'true') {
                // Delete all non-admin users with applied filters
                $query = User::where('is_admin', false);
                
                // Apply the same filters as in the players() method
                if ($request->filled('search')) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                          ->orWhere('email', 'like', '%' . $request->search . '%')
                          ->orWhere('phone', 'like', '%' . $request->search . '%');
                }

                if ($request->filled('region')) {
                    $query->whereHas('community', function($q) use ($request) {
                        $q->where('region_id', $request->region);
                    });
                }

                if ($request->filled('county')) {
                    $query->whereHas('community', function($q) use ($request) {
                        $q->where('county_id', $request->county);
                    });
                }

                if ($request->filled('community')) {
                    $query->where('community_id', $request->community);
                }

                if ($request->filled('status')) {
                    if ($request->status == 'active') {
                        $query->whereNotNull('email_verified_at');
                    } elseif ($request->status == 'inactive') {
                        $query->whereNull('email_verified_at');
                    }
                }

                $deletedCount = $query->count();
                
                if ($deletedCount === 0) {
                    return redirect()->route('admin.players')->withErrors(['error' => 'No players found matching the current filters.']);
                }

                $query->delete();
                $message = "Successfully deleted all {$deletedCount} player(s) matching the current filters.";
                
            } else {
                // Handle specific player IDs selection
                $playerIds = $request->player_ids;
                
                // Get players to delete (exclude admin users)
                $playersToDelete = User::whereIn('id', $playerIds)
                    ->where('is_admin', false)
                    ->get();

                if ($playersToDelete->isEmpty()) {
                    return redirect()->route('admin.players')->withErrors(['error' => 'No valid players selected for deletion.']);
                }

                $deletedCount = $playersToDelete->count();
                $adminCount = count($playerIds) - $deletedCount;

                // Delete the players
                User::whereIn('id', $playersToDelete->pluck('id'))->delete();

                $message = "Successfully deleted {$deletedCount} player(s).";
                if ($adminCount > 0) {
                    $message .= " {$adminCount} admin user(s) were skipped.";
                }
            }

            return redirect()->route('admin.players')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.players')->withErrors(['error' => 'Failed to delete selected players. Please try again.']);
        }
    }

    /**
     * Delete all matches for a tournament
     */
    public function deleteAllMatches(Tournament $tournament)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('admin.tournaments.view', $tournament)->withErrors(['error' => 'Access denied.']);
        }

        try {
            $matchCount = $tournament->matches()->count();
            
            if ($matchCount === 0) {
                return redirect()->route('admin.tournaments.view', $tournament)->withErrors(['error' => 'No matches found to delete.']);
            }

            // Log the action for audit purposes
            \Log::info('Admin deleting all matches', [
                'admin_id' => auth()->id(),
                'admin_email' => auth()->user()->email,
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'match_count' => $matchCount,
                'timestamp' => now()
            ]);

            // Use database transaction for safety
            DB::beginTransaction();
            
            try {
                // Delete all matches for this tournament
                // This will also cascade delete related data if foreign keys are set up properly
                $tournament->matches()->delete();
                
                // Also delete any winners for this tournament to reset progress
                $tournament->winners()->delete();
                
                DB::commit();
                
                \Log::info('Successfully deleted all matches', [
                    'tournament_id' => $tournament->id,
                    'deleted_matches' => $matchCount
                ]);

                return redirect()->route('admin.tournaments.view', $tournament)->with('success', "Successfully deleted all {$matchCount} matches and reset tournament progress for '{$tournament->name}'.");
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Failed to delete tournament matches', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.tournaments.view', $tournament)->withErrors(['error' => 'Failed to delete matches. Please try again.']);
        }
    }

    public function deleteMatch(Request $request, Tournament $tournament, PoolMatch $match)
    {
        // Check admin privileges
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            // Verify the match belongs to this tournament
            if ($match->tournament_id !== $tournament->id) {
                return response()->json(['success' => false, 'message' => 'Match does not belong to this tournament'], 400);
            }

            // Log the deletion for audit purposes
            \Log::info("Admin deleting individual match", [
                'admin_id' => auth()->user()->id,
                'admin_email' => auth()->user()->email,
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'match_id' => $match->id,
                'match_name' => $match->match_name,
                'player_1_id' => $match->player_1_id,
                'player_2_id' => $match->player_2_id,
                'match_status' => $match->status,
                'timestamp' => now()
            ]);

            // Delete any related winner records if this match had a winner
            if ($match->winner_id) {
                Winner::where('tournament_id', $tournament->id)
                    ->where('player_id', $match->winner_id)
                    ->where('level', $match->level)
                    ->delete();
                
                \Log::info("Deleted related winner record", [
                    'winner_id' => $match->winner_id,
                    'match_id' => $match->id
                ]);
            }

            // Delete the match
            $match->delete();

            DB::commit();

            \Log::info("Individual match deleted successfully", [
                'match_id' => $match->id,
                'tournament_id' => $tournament->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Match deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to delete individual match", [
                'match_id' => $match->id ?? 'unknown',
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete match: ' . $e->getMessage()
            ], 500);
        }
    }

}
