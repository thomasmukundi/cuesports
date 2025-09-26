<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Match;
use App\Models\Tournament;

class UserController extends Controller
{
    /**
     * Get all users (for leaderboard and general listing)
     */
    public function index()
    {
        $users = User::select('id', 'name', 'first_name', 'last_name', 'email', 'profile_image', 'created_at')
            ->withCount(['wonMatches'])
            ->orderBy('won_matches_count', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image_url,
                    'points' => $user->won_matches_count * 10, // 10 points per win
                    'wins' => $user->won_matches_count,
                    'joined_date' => $user->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get user dashboard data
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        // Get incomplete matches first (upcoming/in-progress)
        $incompleteMatches = DB::table('matches')
            ->leftJoin('users as player1', 'matches.player_1_id', '=', 'player1.id')
            ->leftJoin('users as player2', 'matches.player_2_id', '=', 'player2.id')
            ->leftJoin('tournaments', 'matches.tournament_id', '=', 'tournaments.id')
            ->where(function ($q) use ($user) {
                $q->where('matches.player_1_id', $user->id)
                  ->orWhere('matches.player_2_id', $user->id);
            })
            ->whereIn('matches.status', ['scheduled', 'pending', 'in_progress', 'pending_confirmation'])
            ->orderBy('matches.created_at', 'desc')
            ->select([
                'matches.*',
                'player1.name as player1_name',
                'player2.name as player2_name',
                'tournaments.name as tournament_name'
            ])
            ->get();
            
        // Get recent completed matches as fallback
        $completedMatches = DB::table('matches')
            ->leftJoin('users as player1', 'matches.player_1_id', '=', 'player1.id')
            ->leftJoin('users as player2', 'matches.player_2_id', '=', 'player2.id')
            ->leftJoin('tournaments', 'matches.tournament_id', '=', 'tournaments.id')
            ->where(function ($q) use ($user) {
                $q->where('matches.player_1_id', $user->id)
                  ->orWhere('matches.player_2_id', $user->id);
            })
            ->where('matches.status', 'completed')
            ->orderBy('matches.updated_at', 'desc')
            ->select([
                'matches.*',
                'player1.name as player1_name',
                'player2.name as player2_name',
                'tournaments.name as tournament_name'
            ])
            ->limit(3)
            ->get();
            
        // Get user statistics using correct matches table
        $totalMatches = DB::table('matches')->where(function ($q) use ($user) {
            $q->where('player_1_id', $user->id)
              ->orWhere('player_2_id', $user->id);
        })->where('status', 'completed')->count();
        
        $wonMatches = DB::table('matches')->where('winner_id', $user->id)->count();
        $winRate = $totalMatches > 0 ? round(($wonMatches / $totalMatches) * 100, 1) : 0;
        
        // Get top shooters (leaderboard)
        $topShooters = User::withCount(['wonMatches'])
            ->having('won_matches_count', '>', 0)
            ->orderBy('won_matches_count', 'desc')
            ->limit(10)
            ->get();
            
        // If no users have wins, get random 3 users
        if ($topShooters->isEmpty()) {
            $topShooters = User::inRandomOrder()->limit(3)->get();
        }
            
        // Calculate user's rank
        $userPoints = $wonMatches * 10;
        $userRank = $this->getUserRank($user->id, $userPoints);

        return response()->json([
            'success' => true,
            'dashboard' => [
                'user_stats' => [
                    'total_matches' => $totalMatches,
                    'won_matches' => $wonMatches,
                    'win_rate' => $winRate,
                    'points' => $userPoints,
                    'rank' => $userRank,
                ],
                'recent_matches' => $this->formatMatchesForDashboard($incompleteMatches, $completedMatches, $user),
                'top_shooters' => $topShooters->map(function ($shooter, $index) {
                    return [
                        'rank' => $index + 1,
                        'name' => $shooter->name,
                        'wins' => $shooter->won_matches_count ?? 0,
                        'points' => ($shooter->won_matches_count ?? 0) * 10,
                        'profile_image' => $shooter->profile_image_url,
                    ];
                }),
            ]
        ]);
    }
    
    /**
     * Format matches for dashboard display
     */
    private function formatMatchesForDashboard($incompleteMatches, $completedMatches, $user)
    {
        // Prioritize incomplete matches, fallback to completed matches
        $matchesToShow = $incompleteMatches->isNotEmpty() ? $incompleteMatches : $completedMatches;
        
        // Console logging for debugging
        \Log::info('Dashboard Recent Matches Debug:', [
            'user_id' => $user->id,
            'incomplete_matches_count' => $incompleteMatches->count(),
            'completed_matches_count' => $completedMatches->count(),
            'matches_to_show_count' => $matchesToShow->count(),
            'incomplete_matches' => $incompleteMatches->toArray(),
            'completed_matches' => $completedMatches->toArray()
        ]);
        
        if ($matchesToShow->isEmpty()) {
            \Log::info('Recent Matches: No results found for user ' . $user->id);
            return collect([]);
        }
        
        $formattedMatches = $matchesToShow->map(function ($match) use ($user) {
            $opponentName = $match->player_1_id === $user->id ? $match->player2_name : $match->player1_name;
            $isCompleted = $match->status === 'completed';
            
            return [
                'id' => $match->id,
                'opponent_name' => $opponentName ?? 'Unknown Opponent',
                'tournament' => $match->tournament_name ?? 'Unknown Tournament',
                'status' => $match->status,
                'score' => $isCompleted ? "{$match->player_1_points}-{$match->player_2_points}" : null,
                'won' => $isCompleted ? ($match->winner_id === $user->id) : null,
                'date' => $match->updated_at ?? $match->created_at,
            ];
        });
        
        \Log::info('Recent Matches: Found ' . $formattedMatches->count() . ' matches for user ' . $user->id, [
            'matches' => $formattedMatches->toArray()
        ]);
        
        return $formattedMatches;
    }

    /**
     * Get user's rank based on points
     */
    private function getUserRank($userId, $points)
    {
        $betterPlayers = DB::table('users')
            ->join('matches', function($join) {
                $join->on('users.id', '=', 'matches.winner_id');
            })
            ->selectRaw('users.id, COUNT(matches.id) * 10 as total_points')
            ->groupBy('users.id')
            ->havingRaw('total_points > ?', [$points])
            ->count();
        
        return $betterPlayers + 1;
    }
    
    /**
     * Get user statistics
     */
    public function statistics()
    {
        $user = auth()->user();
        
        $matches = PoolMatch::where(function ($q) use ($user) {
            $q->where('player_1_id', $user->id)
              ->orWhere('player_2_id', $user->id);
        })->where('status', 'completed')->get();
        
        $totalMatches = $matches->count();
        $wonMatches = $matches->where('winner_id', $user->id)->count();
        $lostMatches = $totalMatches - $wonMatches;
        $winRate = $totalMatches > 0 ? round(($wonMatches / $totalMatches) * 100, 1) : 0;
        
        // Tournament participation
        $tournaments = Tournament::whereHas('registrations', function ($q) use ($user) {
            $q->where('player_id', $user->id);
        })->get();
        
        return response()->json([
            'success' => true,
            'statistics' => [
                'matches' => [
                    'total' => $totalMatches,
                    'won' => $wonMatches,
                    'lost' => $lostMatches,
                    'win_rate' => $winRate,
                ],
                'tournaments' => [
                    'participated' => $tournaments->count(),
                    'completed' => $tournaments->where('status', 'completed')->count(),
                ],
                'recent_performance' => $matches->sortByDesc('updated_at')->take(10)->map(function ($match) use ($user) {
                    return [
                        'date' => $match->updated_at,
                        'won' => $match->winner_id === $user->id,
                        'score' => "{$match->player_1_score}-{$match->player_2_score}",
                    ];
                })->values(),
            ]
        ]);
    }

    /**
     * Update user's community location
     */
    public function updateCommunity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'community_id' => 'required|integer|exists:communities,id',
            'county_id' => 'required|integer|exists:counties,id',
            'region_id' => 'required|integer|exists:regions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            
            // Verify that the community belongs to the county and region
            $community = DB::table('communities')
                ->join('counties', 'communities.county_id', '=', 'counties.id')
                ->join('regions', 'counties.region_id', '=', 'regions.id')
                ->where('communities.id', $request->community_id)
                ->where('counties.id', $request->county_id)
                ->where('regions.id', $request->region_id)
                ->select('communities.*', 'counties.name as county_name', 'regions.name as region_name')
                ->first();

            if (!$community) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid location selection. Please ensure the community belongs to the selected county and region.'
                ], 400);
            }

            // Update user's location
            $user->update([
                'community_id' => $request->community_id,
                'county_id' => $request->county_id,
                'region_id' => $request->region_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Community updated successfully',
                'data' => [
                    'community_id' => $user->community_id,
                    'county_id' => $user->county_id,
                    'region_id' => $user->region_id,
                    'community_name' => $community->name,
                    'county_name' => $community->county_name,
                    'region_name' => $community->region_name,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Update community error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update community. Please try again.'
            ], 500);
        }
    }

    /**
     * Update user's profile image
     */
    public function updateProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();

            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');

                // Create unique filename
                $filename = 'profile_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();

                $defaultDisk = config('filesystems.default', 'public');
                
                \Log::info('Profile image upload - disk config', [
                    'default_disk' => $defaultDisk,
                    'filesystem_disk_env' => env('FILESYSTEM_DISK'),
                    'config_filesystems_default' => config('filesystems.default'),
                    'user_id' => $user->id
                ]);
                
                // Force use private disk if FILESYSTEM_DISK=private
                if (env('FILESYSTEM_DISK') === 'private') {
                    $defaultDisk = 'private';
                    \Log::info('Forcing private disk usage');
                }

                if ($defaultDisk === 'public') {
                    // Local/public storage: keep legacy behavior
                    $path = $image->storeAs('profile_images', $filename, 'public');

                    // Delete old file if it exists (handles legacy '/storage/' prefix)
                    if ($user->profile_image) {
                        $oldPublicPath = ltrim(str_replace('/storage/', '', $user->profile_image), '/');
                        if (\Storage::disk('public')->exists($oldPublicPath)) {
                            \Storage::disk('public')->delete($oldPublicPath);
                        }
                    }

                    // Store with '/storage/' prefix in DB for backward compatibility
                    $storedValue = '/storage/' . $path;
                } else {
                    // Cloud/object storage (e.g., s3-compatible on Laravel Cloud)
                    $path = $image->storeAs('profile_images', $filename, $defaultDisk);
                    // Ensure public visibility where supported
                    try { \Storage::disk($defaultDisk)->setVisibility($path, 'public'); } catch (\Throwable $e) { /* ignore if not supported */ }

                    // Delete old file on current disk if a relative key was stored previously
                    if ($user->profile_image) {
                        if (!str_starts_with($user->profile_image, 'http') && !str_starts_with($user->profile_image, '/storage/')) {
                            $oldKey = ltrim($user->profile_image, '/');
                            if (\Storage::disk($defaultDisk)->exists($oldKey)) {
                                \Storage::disk($defaultDisk)->delete($oldKey);
                            }
                        }
                    }

                    // Store only the relative key in DB; URL will be resolved by accessor via Storage::url()
                    $storedValue = $path;
                }

                \Log::info('Updating profile image for user ' . $user->id . ' with value: ' . $storedValue);

                $updateResult = $user->update([
                    'profile_image' => $storedValue
                ]);

                \Log::info('Update result: ' . ($updateResult ? 'success' : 'failed'));

                // Refresh user instance to get updated data
                $user->refresh();

                \Log::info('User profile_image after update: ' . ($user->profile_image ?? 'null'));
                
                // Log the full URL that will be returned by the accessor
                $fullImageUrl = $user->profile_image_url;
                \Log::info('Full profile image URL (via accessor): ' . ($fullImageUrl ?? 'null'));
                
                // Also log what Storage::url() returns for this path
                try {
                    $storageUrl = \Storage::disk($defaultDisk)->url($user->profile_image);
                    \Log::info('Direct Storage::url() result: ' . $storageUrl);
                } catch (\Exception $e) {
                    \Log::error('Storage::url() failed: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Profile image updated successfully',
                    'data' => [
                        'profile_image' => $user->profile_image_url
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No image file provided'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Update profile image error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile image. Please try again.'
            ], 500);
        }
    }
}
