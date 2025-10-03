<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PoolMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    /**
     * Update player profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'community_id' => 'sometimes|exists:communities,id',
            'county_id' => 'sometimes|exists:counties,id',
            'region_id' => 'sometimes|exists:regions,id',
        ]);
        
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $validated['name'] = ($validated['first_name'] ?? $user->first_name) . ' ' . 
                                ($validated['last_name'] ?? $user->last_name);
        }
        
        $user->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Get top shooters leaderboard (for mobile app dashboard)
     */
    public function leaderboard(Request $request)
    {
        // Log the query for debugging
        \Log::info('Leaderboard query starting...');
        
        // Use a more reliable approach to avoid JOIN issues
        $topPlayers = collect();
        
        // Get all users who have participated in completed matches
        $users = DB::table('users')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('matches')
                    ->where('status', 'completed')
                    ->where(function($q) {
                        $q->whereColumn('matches.player_1_id', 'users.id')
                          ->orWhereColumn('matches.player_2_id', 'users.id');
                    });
            })
            ->select('id', 'username', 'name', 'profile_image', 'community_id', 'created_at')
            ->get();

        foreach ($users as $user) {
            // Count wins - matches where this user is the winner
            $wins = DB::table('matches')
                ->where('winner_id', $user->id)
                ->where('status', 'completed')
                ->count();

            // Count total matches - matches where this user participated
            $totalMatches = DB::table('matches')
                ->where('status', 'completed')
                ->where(function($query) use ($user) {
                    $query->where('player_1_id', $user->id)
                          ->orWhere('player_2_id', $user->id);
                })
                ->count();

            // Count tournament wins
            $tournamentWins = DB::table('winners')
                ->where('player_id', $user->id)
                ->where('position', 1)
                ->count();

            // Count tournaments participated
            $tournamentsParticipated = DB::table('winners')
                ->where('player_id', $user->id)
                ->distinct('tournament_id')
                ->count();

            // Calculate total prize money
            $totalPrizeMoney = DB::table('winners')
                ->where('player_id', $user->id)
                ->sum('prize_amount') ?? 0;

            // Calculate average points per match
            $avgPointsQuery = DB::table('matches')
                ->where('status', 'completed')
                ->where(function($query) use ($user) {
                    $query->where('player_1_id', $user->id)
                          ->orWhere('player_2_id', $user->id);
                })
                ->selectRaw('AVG(CASE 
                    WHEN player_1_id = ? THEN player_1_points 
                    WHEN player_2_id = ? THEN player_2_points 
                    END) as avg_points', [$user->id, $user->id])
                ->first();
            
            $avgPointsPerMatch = $avgPointsQuery->avg_points ?? 0;

            $playerData = (object) [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'profile_image' => $user->profile_image,
                'community_id' => $user->community_id,
                'created_at' => $user->created_at,
                'wins' => $wins,
                'total_matches' => $totalMatches,
                'tournament_wins' => $tournamentWins,
                'tournaments_participated' => $tournamentsParticipated,
                'total_prize_money' => $totalPrizeMoney,
                'avg_points_per_match' => $avgPointsPerMatch,
                'leaderboard_points' => $wins * 10
            ];

            $topPlayers->push($playerData);
        }

        // Sort by leaderboard points, then wins, then tournament wins
        $topPlayers = $topPlayers->sortByDesc(function($player) {
            return [$player->leaderboard_points, $player->wins, $player->tournament_wins];
        })->take(10);
            
        \Log::info('Top players query result:', ['count' => $topPlayers->count(), 'data' => $topPlayers->toArray()]);
        
        $topPlayersFormatted = $topPlayers->map(function($player, $index) {
            $winRate = $player->total_matches > 0 ? 
                round(($player->wins / $player->total_matches) * 100, 1) : 0;
                
            return [
                'rank' => $index + 1,
                'id' => $player->id,
                'name' => $player->name ?: $player->username,
                'wins' => (int) $player->wins,
                'total_matches' => (int) $player->total_matches,
                'win_rate' => $winRate,
                'tournament_wins' => (int) $player->tournament_wins,
                'tournaments_participated' => (int) $player->tournaments_participated,
                'total_prize_money' => (float) ($player->total_prize_money ?? 0),
                'avg_points_per_match' => round((float) ($player->avg_points_per_match ?? 0), 1),
                'leaderboard_points' => (int) $player->leaderboard_points,
                'profile_image' => $player->profile_image,
                'member_since' => $player->created_at ? date('Y-m-d', strtotime($player->created_at)) : null,
                'performance_rating' => $this->calculatePerformanceRating($player)
            ];
        });

        // If no players have points, get random players as fallback
        if ($topPlayersFormatted->isEmpty() || $topPlayersFormatted->every(fn($p) => $p['leaderboard_points'] == 0)) {
            \Log::info('No players with points found, getting random users...');
            
            $randomPlayers = DB::table('users')
                ->select('id', 'username', 'name', 'profile_image')
                ->inRandomOrder()
                ->limit(4)
                ->get();
                
            \Log::info('Random players query result:', ['count' => $randomPlayers->count(), 'data' => $randomPlayers->toArray()]);
            
            $randomPlayersFormatted = $randomPlayers->map(function($player, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $player->id,
                    'name' => $player->name ?: $player->username,
                    'wins' => 0,
                    'points' => 0,
                    'profile_image' => $player->profile_image
                ];
            });
            
            $response = [
                'success' => true,
                'data' => $randomPlayersFormatted->values()->toArray()
            ];
            
            \Log::info('Returning random players response:', $response);
            return response()->json($response);
        }

        $response = [
            'success' => true,
            'data' => $topPlayersFormatted->values()->toArray()
        ];
        
        \Log::info('Returning top players response:', $response);
        return response()->json($response);
    }

    /**
     * Get user's personal leaderboard data (past matches and awards)
     */
    public function personalLeaderboard(Request $request)
    {
        $user = Auth::user();
        
        // Get user's past matches
        $pastMatches = DB::table('matches')
            ->leftJoin('users as p1', 'matches.player_1_id', '=', 'p1.id')
            ->leftJoin('users as p2', 'matches.player_2_id', '=', 'p2.id')
            ->leftJoin('tournaments', 'matches.tournament_id', '=', 'tournaments.id')
            ->where(function ($q) use ($user) {
                $q->where('matches.player_1_id', $user->id)
                  ->orWhere('matches.player_2_id', $user->id);
            })
            ->where('matches.status', 'completed')
            ->whereNotNull('matches.winner_id') // Only include matches with a determined winner
            ->orderBy('matches.created_at', 'desc')
            ->limit(10)
            ->select('matches.*', 'p1.name as player1_name', 'p2.name as player2_name', 'tournaments.name as tournament_name')
            ->get()
            ->map(function ($match) use ($user) {
                // Determine opponent name correctly
                $opponent = $match->player_1_id == $user->id ? $match->player2_name : $match->player1_name;
                
                // Convert database values to integers for proper comparison
                $winnerId = (int) $match->winner_id;
                $userId = (int) $user->id;
                $isWinner = $winnerId === $userId;
                
                return [
                    'id' => $match->id,
                    'tournament' => $match->tournament_name,
                    'opponent' => $opponent,
                    'result' => $isWinner ? 'Won' : 'Lost',
                    'date' => date('Y-m-d', strtotime($match->created_at)),
                    'player_1_points' => $match->player_1_points,
                    'player_2_points' => $match->player_2_points,
                ];
            });

        // Get user awards/achievements
        $totalMatches = DB::table('matches')->where(function ($q) use ($user) {
            $q->where('player_1_id', $user->id)
              ->orWhere('player_2_id', $user->id);
        })->where('status', 'completed')->count();

        $wonMatches = DB::table('matches')->where('winner_id', $user->id)->count();
        $winRate = $totalMatches > 0 ? round(($wonMatches / $totalMatches) * 100, 1) : 0;
        $totalPoints = $wonMatches * 10;

        $awards = [
            'total_points' => $totalPoints,
            'total_matches' => $totalMatches,
            'won_matches' => $wonMatches,
            'win_rate' => $winRate,
            'achievements' => []
        ];

        // Add achievements based on performance
        if ($wonMatches >= 10) {
            $awards['achievements'][] = ['name' => 'Veteran Player', 'description' => 'Won 10+ matches'];
        }
        if ($winRate >= 70) {
            $awards['achievements'][] = ['name' => 'Champion', 'description' => 'Win rate above 70%'];
        }
        if ($totalMatches >= 50) {
            $awards['achievements'][] = ['name' => 'Active Player', 'description' => 'Played 50+ matches'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'past_matches' => $pastMatches,
                'awards' => $awards,
                'user_stats' => [
                    'name' => $user->name,
                    'total_points' => $totalPoints,
                    'win_rate' => $winRate,
                    'total_matches' => $totalMatches,
                    'won_matches' => $wonMatches
                ]
            ]
        ]);
    }

    /**
     * Get awards page with detailed player statistics
     */
    public function awards(Request $request)
    {
        $user = Auth::user();
        
        // Calculate comprehensive statistics
        $totalMatches = DB::table('matches')->where(function ($q) use ($user) {
            $q->where('player_1_id', $user->id)
              ->orWhere('player_2_id', $user->id);
        })->where('status', 'completed')->count();

        $wonMatches = DB::table('matches')->where('winner_id', $user->id)->count();
        $lostMatches = $totalMatches - $wonMatches;
        $winRate = $totalMatches > 0 ? round(($wonMatches / $totalMatches) * 100, 1) : 0;
        $totalPoints = $wonMatches * 10;

        // Tournament statistics
        $tournamentParticipations = DB::table('tournament_registrations')
            ->where('player_id', $user->id)
            ->count();

        // Get awards from winners table with tournament details
        $awards = DB::table('winners')
            ->leftJoin('tournaments', 'winners.tournament_id', '=', 'tournaments.id')
            ->where('winners.player_id', $user->id)
            ->whereIn('winners.position', [1, 2, 3])
            ->select([
                'winners.*',
                'tournaments.name as tournament_name',
                'tournaments.area_scope',
                'tournaments.special',
                'tournaments.start_date',
                'tournaments.end_date'
            ])
            ->orderBy('winners.created_at', 'desc')
            ->get();

        $tournamentWins = $awards->where('position', 1)->count();
        $totalPodiumFinishes = $awards->count();

        // Recent performance (last 10 matches)
        $recentMatches = DB::table('matches')->where(function ($q) use ($user) {
            $q->where('player_1_id', $user->id)
              ->orWhere('player_2_id', $user->id);
        })
        ->where('status', 'completed')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        $recentWins = $recentMatches->where('winner_id', $user->id)->count();
        $recentWinRate = $recentMatches->count() > 0 ? round(($recentWins / $recentMatches->count()) * 100, 1) : 0;

        // Achievements
        $achievements = [];
        $firstPlaces = $awards->where('position', 1)->count();
        $secondPlaces = $awards->where('position', 2)->count();
        $thirdPlaces = $awards->where('position', 3)->count();
        
        if ($firstPlaces >= 1) $achievements[] = ['name' => 'Champion', 'description' => 'Won 1st place in tournament', 'icon' => '🥇'];
        if ($firstPlaces >= 3) $achievements[] = ['name' => 'Triple Champion', 'description' => 'Won 3+ tournaments', 'icon' => '👑'];
        if ($secondPlaces >= 1) $achievements[] = ['name' => 'Runner-up', 'description' => 'Achieved 2nd place', 'icon' => '🥈'];
        if ($thirdPlaces >= 1) $achievements[] = ['name' => 'Podium Finisher', 'description' => 'Achieved 3rd place', 'icon' => '🥉'];
        if ($totalPodiumFinishes >= 5) $achievements[] = ['name' => 'Consistent Performer', 'description' => '5+ podium finishes', 'icon' => '🏆'];
        
        if ($wonMatches >= 1) $achievements[] = ['name' => 'First Victory', 'description' => 'Won your first match', 'icon' => '⭐'];
        if ($wonMatches >= 10) $achievements[] = ['name' => 'Veteran Player', 'description' => 'Won 10+ matches', 'icon' => '🎖️'];
        if ($winRate >= 70) $achievements[] = ['name' => 'Dominator', 'description' => 'Win rate above 70%', 'icon' => '🔥'];
        if ($totalMatches >= 20) $achievements[] = ['name' => 'Active Player', 'description' => 'Played 20+ matches', 'icon' => '⚡'];
        if ($tournamentParticipations >= 3) $achievements[] = ['name' => 'Tournament Regular', 'description' => 'Participated in 3+ tournaments', 'icon' => '🏅'];

        return response()->json([
            'success' => true,
            'data' => [
                'player_stats' => [
                    'name' => $user->name,
                    'profile_image' => $user->profile_image,
                    'total_points' => $totalPoints,
                    'total_matches' => $totalMatches,
                    'won_matches' => $wonMatches,
                    'lost_matches' => $lostMatches,
                    'win_rate' => $winRate,
                    'tournament_participations' => $tournamentParticipations,
                    'tournament_wins' => $tournamentWins,
                    'recent_win_rate' => $recentWinRate,
                    'recent_matches_count' => $recentMatches->count(),
                    'podium_finishes' => $totalPodiumFinishes,
                    'first_places' => $firstPlaces,
                    'second_places' => $secondPlaces,
                    'third_places' => $thirdPlaces
                ],
                'achievements' => $achievements,
                'tournament_awards' => $awards->map(function($award) {
                    $positionText = match($award->position) {
                        1 => '1st Place - Champion',
                        2 => '2nd Place - Runner Up',
                        3 => '3rd Place - Third Place',
                        default => $award->position . 'th Place'
                    };
                    
                    $levelText = '';
                    if ($award->special) {
                        $levelText = 'Special Tournament';
                    } else {
                        $levelText = match($award->area_scope) {
                            'community' => 'Community Level',
                            'county' => 'County Level', 
                            'regional' => 'Regional Level',
                            'national' => 'National Level',
                            default => ucfirst($award->level ?? $award->area_scope) . ' Level'
                        };
                    }
                    
                    return [
                        'position' => $award->position,
                        'position_text' => $positionText,
                        'level' => $award->level,
                        'level_text' => $levelText,
                        'tournament_id' => $award->tournament_id,
                        'tournament_name' => $award->tournament_name ?? 'Unknown Tournament',
                        'area_scope' => $award->area_scope,
                        'is_special' => (bool) $award->special,
                        'prize_amount' => $award->prize_amount,
                        'points' => $award->points ?? 0,
                        'wins' => $award->wins ?? 0,
                        'date' => $award->start_date ? date('M j, Y', strtotime($award->start_date)) : date('M j, Y', strtotime($award->created_at)),
                        'created_at' => $award->created_at
                    ];
                }),
                'performance_summary' => [
                    'rank' => $this->getUserRank($user->id, $totalPoints),
                    'points_to_next_level' => $this->getPointsToNextLevel($totalPoints),
                    'current_streak' => $this->getCurrentStreak($user->id),
                    'best_streak' => $this->getBestStreak($user->id)
                ]
            ]
        ]);
    }

    /**
     * Get player statistics
     */
    public function playerStats($playerId)
    {
        $player = User::findOrFail($playerId);
        
        $stats = $this->calculatePlayerStats($player);
        
        return response()->json([
            'success' => true,
            'player' => $player,
            'stats' => $stats
        ]);
    }

    // Helper methods for awards calculation
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
    
    private function getPointsToNextLevel($currentPoints)
    {
        $levels = [0, 50, 100, 250, 500, 1000];
        foreach ($levels as $level) {
            if ($currentPoints < $level) {
                return $level - $currentPoints;
            }
        }
        return 0; // Already at max level
    }
    
    private function getCurrentStreak($userId)
    {
        $matches = DB::table('matches')->where(function ($q) use ($userId) {
            $q->where('player_1_id', $userId)
              ->orWhere('player_2_id', $userId);
        })
        ->where('status', 'completed')
        ->orderBy('created_at', 'desc')
        ->get();
        
        $streak = 0;
        $lastResult = null;
        
        foreach ($matches as $match) {
            $won = $match->winner_id === $userId;
            if ($lastResult === null) {
                $lastResult = $won;
                $streak = 1;
            } elseif ($lastResult === $won) {
                $streak++;
            } else {
                break;
            }
        }
        
        return ['type' => $lastResult ? 'win' : 'loss', 'count' => $streak];
    }
    
    private function getBestStreak($userId)
    {
        $matches = DB::table('matches')->where(function ($q) use ($userId) {
            $q->where('player_1_id', $userId)
              ->orWhere('player_2_id', $userId);
        })
        ->where('status', 'completed')
        ->orderBy('created_at', 'asc')
        ->get();
        
        $bestWinStreak = 0;
        $currentWinStreak = 0;
        
        foreach ($matches as $match) {
            if ($match->winner_id === $userId) {
                $currentWinStreak++;
                $bestWinStreak = max($bestWinStreak, $currentWinStreak);
            } else {
                $currentWinStreak = 0;
            }
        }
        
        return $bestWinStreak;
    }

    /**
     * Get current user's stats
     */
    public function myStats()
    {
        $user = Auth::user();
        
        $stats = $this->calculatePlayerStats($user);
        
        return response()->json([
            'player' => $user,
            'stats' => $stats
        ]);
    }

    /**
     * Calculate player statistics
     */
    private function calculatePlayerStats($player)
    {
        $matches = PoolMatch::where(function($q) use ($player) {
            $q->where('player_1_id', $player->id)
              ->orWhere('player_2_id', $player->id);
        })->where('status', 'completed');
        
        $totalMatches = $matches->count();
        $wins = (clone $matches)->where('winner_id', $player->id)->count();
        $losses = $totalMatches - $wins;
        
        // Calculate average points
        $avgPoints = 0;
        if ($totalMatches > 0) {
            $totalPoints = 0;
            $matchRecords = $matches->get();
            
            foreach ($matchRecords as $match) {
                if ($match->player_1_id == $player->id) {
                    $totalPoints += $match->player_1_points ?? 0;
                } else {
                    $totalPoints += $match->player_2_points ?? 0;
                }
            }
            
            $avgPoints = round($totalPoints / $totalMatches, 2);
        }
        
        // Get tournament wins
        $tournamentWins = DB::table('winners')
            ->where('player_id', $player->id)
            ->where('position', 1)
            ->count();
        
        // Get recent form (last 5 matches)
        $recentMatches = PoolMatch::where(function($q) use ($player) {
            $q->where('player_1_id', $player->id)
              ->orWhere('player_2_id', $player->id);
        })->where('status', 'completed')
          ->orderBy('updated_at', 'desc')
          ->limit(5)
          ->get();
        
        $recentForm = [];
        foreach ($recentMatches as $match) {
            $recentForm[] = $match->winner_id == $player->id ? 'W' : 'L';
        }
        
        // Rankings
        $communityRank = User::where('community_id', $player->community_id)
            ->where('total_points', '>', $player->total_points)
            ->count() + 1;
            
        $countyRank = User::where('county_id', $player->county_id)
            ->where('total_points', '>', $player->total_points)
            ->count() + 1;
            
        $regionalRank = User::where('region_id', $player->region_id)
            ->where('total_points', '>', $player->total_points)
            ->count() + 1;
            
        $nationalRank = User::where('total_points', '>', $player->total_points)
            ->count() + 1;
        
        return [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 2) : 0,
            'average_points' => $avgPoints,
            'tournament_wins' => $tournamentWins,
            'recent_form' => $recentForm,
            'rankings' => [
                'community' => $communityRank,
                'county' => $countyRank,
                'regional' => $regionalRank,
                'national' => $nationalRank
            ]
        ];
    }

    /**
     * Calculate performance rating based on multiple factors
     */
    private function calculatePerformanceRating($player)
    {
        $rating = 0;
        
        // Base points from wins (40% weight)
        $rating += ($player->wins ?? 0) * 4;
        
        // Win rate bonus (30% weight)
        if ($player->total_matches > 0) {
            $winRate = ($player->wins / $player->total_matches) * 100;
            $rating += $winRate * 0.3;
        }
        
        // Tournament success bonus (20% weight)
        $rating += ($player->tournament_wins ?? 0) * 20;
        
        // Activity bonus (10% weight)
        $rating += min(($player->tournaments_participated ?? 0) * 2, 20);
        
        // Average points per match bonus
        $rating += ($player->avg_points_per_match ?? 0) * 5;
        
        return round($rating, 1);
    }

    /**
     * Get enhanced top shooters data with filtering options
     */
    public function topShootersDetailed(Request $request)
    {
        $limit = $request->get('limit', 20);
        $timeframe = $request->get('timeframe', 'all'); // all, month, week
        $level = $request->get('level'); // community, county, regional, national
        $location = $request->get('location'); // community_id, county_id, etc.

        $query = DB::table('users')
            ->leftJoin('matches as matches', 'users.id', '=', 'matches.winner_id')
            ->leftJoin('matches as all_matches', function($join) {
                $join->on('users.id', '=', 'all_matches.player_1_id')
                     ->orOn('users.id', '=', 'all_matches.player_2_id');
            })
            ->leftJoin('winners', 'users.id', '=', 'winners.player_id')
            ->leftJoin('communities', 'users.community_id', '=', 'communities.id')
            ->leftJoin('counties', 'communities.county_id', '=', 'counties.id')
            ->leftJoin('regions', 'counties.region_id', '=', 'regions.id');

        // Apply timeframe filter
        if ($timeframe === 'month') {
            $query->where('all_matches.created_at', '>=', now()->subMonth());
        } elseif ($timeframe === 'week') {
            $query->where('all_matches.created_at', '>=', now()->subWeek());
        }

        // Apply level filter
        if ($level) {
            $query->where('winners.level', $level);
        }

        // Apply location filter
        if ($location) {
            $query->where('users.community_id', $location);
        }

        $topPlayers = $query
            ->select(
                'users.id',
                'users.username',
                'users.name',
                'users.profile_image',
                'users.created_at',
                'communities.name as community_name',
                'counties.name as county_name',
                'regions.name as region_name',
                DB::raw('COUNT(DISTINCT matches.id) as wins'),
                DB::raw('COUNT(DISTINCT all_matches.id) as total_matches'),
                DB::raw('COUNT(DISTINCT CASE WHEN winners.position = 1 THEN winners.id END) as tournament_wins'),
                DB::raw('COUNT(DISTINCT winners.tournament_id) as tournaments_participated'),
                DB::raw('SUM(DISTINCT winners.prize_amount) as total_prize_money'),
                DB::raw('AVG(CASE 
                    WHEN all_matches.player_1_id = users.id THEN all_matches.player_1_points 
                    WHEN all_matches.player_2_id = users.id THEN all_matches.player_2_points 
                    END) as avg_points_per_match'),
                DB::raw('COUNT(DISTINCT matches.id) * 10 as leaderboard_points')
            )
            ->where('all_matches.status', 'completed')
            ->groupBy('users.id', 'users.username', 'users.name', 'users.profile_image', 'users.created_at', 'communities.name', 'counties.name', 'regions.name')
            ->orderBy('leaderboard_points', 'desc')
            ->orderBy('wins', 'desc')
            ->limit($limit)
            ->get();

        $formattedPlayers = $topPlayers->map(function($player, $index) {
            $winRate = $player->total_matches > 0 ? 
                round(($player->wins / $player->total_matches) * 100, 1) : 0;

            return [
                'rank' => $index + 1,
                'id' => $player->id,
                'name' => $player->name ?: $player->username,
                'wins' => (int) $player->wins,
                'total_matches' => (int) $player->total_matches,
                'win_rate' => $winRate,
                'tournament_wins' => (int) $player->tournament_wins,
                'tournaments_participated' => (int) $player->tournaments_participated,
                'total_prize_money' => (float) ($player->total_prize_money ?? 0),
                'avg_points_per_match' => round((float) ($player->avg_points_per_match ?? 0), 1),
                'leaderboard_points' => (int) $player->leaderboard_points,
                'performance_rating' => $this->calculatePerformanceRating($player),
                'profile_image' => $player->profile_image,
                'member_since' => $player->created_at ? date('Y-m-d', strtotime($player->created_at)) : null,
                'location' => [
                    'community' => $player->community_name,
                    'county' => $player->county_name,
                    'region' => $player->region_name
                ],
                'achievements' => $this->getPlayerAchievements($player)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedPlayers,
            'filters_applied' => [
                'timeframe' => $timeframe,
                'level' => $level,
                'location' => $location,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Get player achievements/badges
     */
    private function getPlayerAchievements($player)
    {
        $achievements = [];

        // Win-based achievements
        if ($player->wins >= 50) {
            $achievements[] = ['name' => 'Master Shooter', 'icon' => '🏆', 'description' => '50+ match wins'];
        } elseif ($player->wins >= 25) {
            $achievements[] = ['name' => 'Expert Player', 'icon' => '🥇', 'description' => '25+ match wins'];
        } elseif ($player->wins >= 10) {
            $achievements[] = ['name' => 'Rising Star', 'icon' => '⭐', 'description' => '10+ match wins'];
        }

        // Tournament achievements
        if ($player->tournament_wins >= 5) {
            $achievements[] = ['name' => 'Tournament Legend', 'icon' => '👑', 'description' => '5+ tournament wins'];
        } elseif ($player->tournament_wins >= 1) {
            $achievements[] = ['name' => 'Champion', 'icon' => '🏅', 'description' => 'Tournament winner'];
        }

        // Win rate achievements
        $winRate = $player->total_matches > 0 ? ($player->wins / $player->total_matches) * 100 : 0;
        if ($winRate >= 80 && $player->total_matches >= 10) {
            $achievements[] = ['name' => 'Precision Shooter', 'icon' => '🎯', 'description' => '80%+ win rate'];
        }

        // Activity achievements
        if ($player->tournaments_participated >= 10) {
            $achievements[] = ['name' => 'Tournament Regular', 'icon' => '📅', 'description' => '10+ tournaments'];
        }

        return $achievements;
    }

    /**
     * Debug leaderboard data to understand the issue
     */
    public function debugLeaderboard(Request $request)
    {
        \Log::info('=== LEADERBOARD DEBUG START ===');
        
        // First, let's check what matches exist
        $matches = DB::table('matches')
            ->select('id', 'player_1_id', 'player_2_id', 'winner_id', 'status', 'player_1_points', 'player_2_points')
            ->where('status', 'completed')
            ->whereNotNull('winner_id')
            ->get();
            
        \Log::info('Completed matches with winners:', $matches->toArray());
        
        // Check users and their match participation
        $userStats = DB::table('users')
            ->leftJoin('matches as won_matches', 'users.id', '=', 'won_matches.winner_id')
            ->leftJoin('matches as all_matches', function($join) {
                $join->on('users.id', '=', 'all_matches.player_1_id')
                     ->orOn('users.id', '=', 'all_matches.player_2_id');
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(DISTINCT won_matches.id) as wins'),
                DB::raw('COUNT(DISTINCT all_matches.id) as total_matches'),
                DB::raw('GROUP_CONCAT(DISTINCT won_matches.id) as won_match_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT all_matches.id) as all_match_ids')
            )
            ->where('all_matches.status', 'completed')
            ->groupBy('users.id', 'users.name')
            ->get();
                    \Log::info('User statistics:', $userStats->toArray());
            
        // Check for potential data issues
        $potentialIssues = [];
        
        // Check for matches with no winner but marked as completed
        $matchesWithoutWinner = DB::table('matches')
            ->where('status', 'completed')
            ->whereNull('winner_id')
            ->count();
        if ($matchesWithoutWinner > 0) {
            $potentialIssues[] = "Found {$matchesWithoutWinner} completed matches without winner_id";
        }
        
        // Check for matches with winner_id but no points
        $matchesWithWinnerNoPoints = DB::table('matches')
            ->where('status', 'completed')
            ->whereNotNull('winner_id')
            ->where(function($query) {
                $query->whereNull('player_1_points')
                      ->orWhereNull('player_2_points');
            })
            ->count();
        if ($matchesWithWinnerNoPoints > 0) {
            $potentialIssues[] = "Found {$matchesWithWinnerNoPoints} matches with winner but no points";
        }
        
        // Check for inconsistent winner_id (winner_id not matching the player with higher points)
        $inconsistentWinners = DB::table('matches')
            ->where('status', 'completed')
            ->whereNotNull('winner_id')
            ->whereNotNull('player_1_points')
            ->whereNotNull('player_2_points')
            ->where(function($query) {
                $query->where(function($q) {
                    // Player 1 has more points but player 2 is marked as winner
                    $q->whereColumn('player_1_points', '>', 'player_2_points')
                      ->whereColumn('winner_id', '=', 'player_2_id');
                })->orWhere(function($q) {
                    // Player 2 has more points but player 1 is marked as winner
                    $q->whereColumn('player_2_points', '>', 'player_1_points')
                      ->whereColumn('winner_id', '=', 'player_1_id');
                });
            })
            ->get();
        if ($inconsistentWinners->count() > 0) {
            $potentialIssues[] = "Found {$inconsistentWinners->count()} matches with inconsistent winner_id";
            \Log::warning('Inconsistent winners found:', $inconsistentWinners->toArray());
        }
        
        \Log::info('Potential data issues:', $potentialIssues);
        
        // Check for specific match details
        $detailedMatches = DB::table('matches')
            ->join('users as p1', 'matches.player_1_id', '=', 'p1.id')
            ->join('users as p2', 'matches.player_2_id', '=', 'p2.id')
            ->leftJoin('users as winner', 'matches.winner_id', '=', 'winner.id')
            ->select(
                'matches.id',
                'p1.name as player_1_name',
                'p2.name as player_2_name',
                'winner.name as winner_name',
                'matches.player_1_points',
                'matches.player_2_points',
                'matches.status'
            )
            ->where('matches.status', 'completed')
            ->get();
            
        \Log::info('Detailed match information:', $detailedMatches->toArray());
        
        return response()->json([
            'success' => true,
            'debug_data' => [
                'completed_matches' => $matches,
                'user_statistics' => $userStats,
                'detailed_matches' => $detailedMatches,
                'potential_issues' => $potentialIssues,
                'data_quality_summary' => [
                    'total_completed_matches' => $matches->count(),
                    'matches_without_winner' => $matchesWithoutWinner,
                    'matches_with_winner_no_points' => $matchesWithWinnerNoPoints,
                    'inconsistent_winners' => $inconsistentWinners->count()
                ]
            ]
        ]);
    }

    /**
     * Simple leaderboard calculation to test the logic
     */
    public function simpleLeaderboard(Request $request)
    {
        \Log::info('=== SIMPLE LEADERBOARD START ===');
        
        // Get all users who have played matches
        $users = DB::table('users')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('matches')
                    ->where('status', 'completed')
                    ->where(function($q) {
                        $q->whereColumn('matches.player_1_id', 'users.id')
                          ->orWhereColumn('matches.player_2_id', 'users.id');
                    });
            })
            ->select('id', 'name', 'username', 'profile_image')
            ->get();

        $leaderboardData = [];

        foreach ($users as $user) {
            // Count wins (matches where this user is the winner)
            $wins = DB::table('matches')
                ->where('winner_id', $user->id)
                ->where('status', 'completed')
                ->count();

            // Count total matches (matches where this user participated)
            $totalMatches = DB::table('matches')
                ->where('status', 'completed')
                ->where(function($query) use ($user) {
                    $query->where('player_1_id', $user->id)
                          ->orWhere('player_2_id', $user->id);
                })
                ->count();

            // Get specific match details for this user
            $userMatches = DB::table('matches')
                ->where('status', 'completed')
                ->where(function($query) use ($user) {
                    $query->where('player_1_id', $user->id)
                          ->orWhere('player_2_id', $user->id);
                })
                ->select('id', 'player_1_id', 'player_2_id', 'winner_id', 'player_1_points', 'player_2_points')
                ->get();

            $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;

            $leaderboardData[] = [
                'user_id' => $user->id,
                'name' => $user->name ?: $user->username,
                'wins' => $wins,
                'total_matches' => $totalMatches,
                'win_rate' => $winRate,
                'leaderboard_points' => $wins * 10,
                'profile_image' => $user->profile_image,
                'match_details' => $userMatches->toArray()
            ];
        }

        // Sort by wins descending
        usort($leaderboardData, function($a, $b) {
            if ($a['wins'] == $b['wins']) {
                return $b['total_matches'] - $a['total_matches'];
            }
            return $b['wins'] - $a['wins'];
        });

        // Add ranks
        foreach ($leaderboardData as $index => &$player) {
            $player['rank'] = $index + 1;
        }

        \Log::info('Simple leaderboard calculation:', $leaderboardData);

        return response()->json([
            'success' => true,
            'data' => array_slice($leaderboardData, 0, 10), // Top 10
            'total_players' => count($leaderboardData)
        ]);
    }
}
