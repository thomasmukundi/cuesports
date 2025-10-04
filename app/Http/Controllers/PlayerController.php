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
        
        // Get all users who have participated in completed matches (excluding admin)
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
            ->where('email', '!=', 'admin@cuesports.com')
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

        // Sort by leaderboard points (descending), then wins (descending), then tournament wins (descending)
        $topPlayers = $topPlayers->sort(function($a, $b) {
            // First compare by leaderboard points (higher is better)
            if ($a->leaderboard_points != $b->leaderboard_points) {
                return $b->leaderboard_points - $a->leaderboard_points;
            }
            
            // Then by wins (higher is better)
            if ($a->wins != $b->wins) {
                return $b->wins - $a->wins;
            }
            
            // Finally by tournament wins (higher is better)
            return $b->tournament_wins - $a->tournament_wins;
        })->values();
        
        \Log::info('Players after sorting:', $topPlayers->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'wins' => $p->wins,
                'leaderboard_points' => $p->leaderboard_points
            ];
        })->toArray());
            
        \Log::info('Top players query result:', ['count' => $topPlayers->count()]);
        
        $topPlayersFormatted = $topPlayers->map(function($player, $index) {
            $winRate = $player->total_matches > 0 ? 
                round(($player->wins / $player->total_matches) * 100, 1) : 0;
                
            // Get most recent tournament placement for this player
            $recentWinner = DB::table('winners')
                ->join('tournaments', 'winners.tournament_id', '=', 'tournaments.id')
                ->where('winners.player_id', $player->id)
                ->select('winners.position', 'winners.prize_amount', 'winners.level', 'tournaments.name as tournament_name', 'winners.created_at')
                ->orderBy('winners.created_at', 'desc')
                ->first();

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
                'performance_rating' => $this->calculatePerformanceRating($player),
                'recent_tournament_placement' => $recentWinner ? [
                    'position' => (int) $recentWinner->position,
                    'tournament_name' => $recentWinner->tournament_name,
                    'level' => $recentWinner->level,
                    'prize_amount' => (float) ($recentWinner->prize_amount ?? 0),
                    'date' => $recentWinner->created_at ? date('Y-m-d', strtotime($recentWinner->created_at)) : null
                ] : null
            ];
        });

        // Ensure we always have exactly 4 players for top shooters
        $playersWithMatches = $topPlayersFormatted->take(4);
        $playersNeeded = 4 - $playersWithMatches->count();
        
        if ($playersNeeded > 0) {
            \Log::info("Need {$playersNeeded} more players to reach 4 total");
            
            // Get existing player IDs to exclude them from random selection
            $existingPlayerIds = $playersWithMatches->pluck('id')->toArray();
            
            $randomPlayers = DB::table('users')
                ->select('id', 'username', 'name', 'profile_image', 'created_at')
                ->whereNotIn('id', $existingPlayerIds)
                ->where('email', '!=', 'admin@cuesports.com')
                ->inRandomOrder()
                ->limit($playersNeeded)
                ->get();
                
            \Log::info('Random players to fill gaps:', ['count' => $randomPlayers->count(), 'data' => $randomPlayers->toArray()]);
            
            $randomPlayersFormatted = $randomPlayers->map(function($player, $index) use ($playersWithMatches) {
                // Get most recent tournament placement for random players too
                $recentWinner = DB::table('winners')
                    ->join('tournaments', 'winners.tournament_id', '=', 'tournaments.id')
                    ->where('winners.player_id', $player->id)
                    ->select('winners.position', 'winners.prize_amount', 'winners.level', 'tournaments.name as tournament_name', 'winners.created_at')
                    ->orderBy('winners.created_at', 'desc')
                    ->first();

                return [
                    'rank' => $playersWithMatches->count() + $index + 1,
                    'id' => $player->id,
                    'name' => $player->name ?: $player->username,
                    'wins' => 0,
                    'total_matches' => 0,
                    'win_rate' => 0,
                    'tournament_wins' => 0,
                    'tournaments_participated' => 0,
                    'total_prize_money' => 0,
                    'avg_points_per_match' => 0,
                    'leaderboard_points' => 0,
                    'profile_image' => $player->profile_image,
                    'member_since' => $player->created_at ? date('Y-m-d', strtotime($player->created_at)) : null,
                    'performance_rating' => 0,
                    'recent_tournament_placement' => $recentWinner ? [
                        'position' => (int) $recentWinner->position,
                        'tournament_name' => $recentWinner->tournament_name,
                        'level' => $recentWinner->level,
                        'prize_amount' => (float) ($recentWinner->prize_amount ?? 0),
                        'date' => $recentWinner->created_at ? date('Y-m-d', strtotime($recentWinner->created_at)) : null
                    ] : null
                ];
            });
            
            // Combine players with matches and random players
            $allPlayers = $playersWithMatches->concat($randomPlayersFormatted);
        } else {
            $allPlayers = $playersWithMatches;
        }

        // Sort final array by rank to ensure correct order (rank 1 first, rank 2 second, etc.)
        $allPlayers = $allPlayers->sortBy('rank')->values();

        \Log::info('Final player order check:', $allPlayers->map(function($p) {
            return [
                'rank' => $p['rank'],
                'name' => $p['name'],
                'wins' => $p['wins'],
                'leaderboard_points' => $p['leaderboard_points']
            ];
        })->toArray());

        $response = [
            'success' => true,
            'data' => $allPlayers->toArray()
        ];
        
        \Log::info('Returning top players response count:', ['count' => count($response['data'])]);
        return response()->json($response);
    }

    /**
     * Get main leaderboard with all players and their recent tournament placements
     */
    public function mainLeaderboard(Request $request)
    {
        \Log::info('Main leaderboard query starting...');
        
        // Get all users who have participated in completed matches (excluding admin)
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
            ->where('email', '!=', 'admin@cuesports.com')
            ->select('id', 'username', 'name', 'profile_image', 'community_id', 'created_at')
            ->get();

        $allPlayers = collect();

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

            // Get most recent tournament placement for this player
            $recentWinner = DB::table('winners')
                ->join('tournaments', 'winners.tournament_id', '=', 'tournaments.id')
                ->where('winners.player_id', $user->id)
                ->select('winners.position', 'winners.prize_amount', 'winners.level', 'tournaments.name as tournament_name', 'winners.created_at')
                ->orderBy('winners.created_at', 'desc')
                ->first();

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
                'leaderboard_points' => $wins * 10,
                'recent_winner' => $recentWinner
            ];

            $allPlayers->push($playerData);
        }

        // Sort by leaderboard points, then wins, then tournament wins
        $allPlayers = $allPlayers->sort(function($a, $b) {
            // First compare by leaderboard points (higher is better)
            if ($a->leaderboard_points != $b->leaderboard_points) {
                return $b->leaderboard_points - $a->leaderboard_points;
            }
            
            // Then by wins (higher is better)
            if ($a->wins != $b->wins) {
                return $b->wins - $a->wins;
            }
            
            // Finally by tournament wins (higher is better)
            return $b->tournament_wins - $a->tournament_wins;
        })->values();

        $leaderboardFormatted = $allPlayers->map(function($player, $index) {
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
                'performance_rating' => $this->calculatePerformanceRating($player),
                'recent_tournament_placement' => $player->recent_winner ? [
                    'position' => (int) $player->recent_winner->position,
                    'tournament_name' => $player->recent_winner->tournament_name,
                    'level' => $player->recent_winner->level,
                    'prize_amount' => (float) ($player->recent_winner->prize_amount ?? 0),
                    'date' => $player->recent_winner->created_at ? date('Y-m-d', strtotime($player->recent_winner->created_at)) : null
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $leaderboardFormatted->toArray(),
            'total_players' => $leaderboardFormatted->count()
        ]);
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
                    'opponent' => $opponent,
                    'tournament' => $match->tournament_name ?: 'Regular Match',
                    'date' => date('Y-m-d', strtotime($match->created_at)),
                    'player_1_points' => (int) ($match->player_1_points ?? 0),
                    'player_2_points' => (int) ($match->player_2_points ?? 0),
                    'player_1_id' => (int) $match->player_1_id,
                    'player_2_id' => (int) $match->player_2_id,
                    'winner_id' => (int) $match->winner_id,
                    'result' => $isWinner ? 'Won' : 'Lost'
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
                'awards_summary' => $awards,
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
     * Get player awards and achievements
     */
    public function awards(Request $request)
    {
        $user = Auth::user();
        
        // Get player stats for award calculations
        $stats = $this->calculatePlayerStats($user);
        
        $achievements = [];
        
        // Win-based awards
        if ($stats['wins'] >= 50) {
            $achievements[] = ['icon' => '🏆', 'name' => 'Master Shooter', 'description' => '50+ match wins'];
        } elseif ($stats['wins'] >= 25) {
            $achievements[] = ['icon' => '🥇', 'name' => 'Expert Player', 'description' => '25+ match wins'];
        } elseif ($stats['wins'] >= 10) {
            $achievements[] = ['icon' => '⭐', 'name' => 'Rising Star', 'description' => '10+ match wins'];
        } elseif ($stats['wins'] >= 1) {
            $achievements[] = ['icon' => '🏆', 'name' => 'First Victory', 'description' => 'Won your first match'];
        }
        
        // Tournament awards
        if ($stats['tournament_wins'] >= 5) {
            $achievements[] = ['icon' => '👑', 'name' => 'Tournament Legend', 'description' => '5+ tournament wins'];
        } elseif ($stats['tournament_wins'] >= 1) {
            $achievements[] = ['icon' => '🏅', 'name' => 'Champion', 'description' => 'Tournament winner'];
        }
        
        // Performance awards
        if ($stats['win_rate'] >= 80 && $stats['total_matches'] >= 10) {
            $achievements[] = ['icon' => '🎯', 'name' => 'Precision Shooter', 'description' => '80%+ win rate with 10+ matches'];
        }
        
        // Activity awards
        if ($stats['total_matches'] >= 50) {
            $achievements[] = ['icon' => '🛡️', 'name' => 'Veteran Player', 'description' => '50+ matches played'];
        }
        
        // Calculate best streak
        $bestStreak = $this->calculateBestStreak($user);
        
        // Get tournament participations
        $tournamentParticipations = DB::table('winners')
            ->where('player_id', $user->id)
            ->distinct('tournament_id')
            ->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'player_stats' => [
                    'total_points' => $stats['wins'] * 10, // Leaderboard points
                    'win_rate' => $stats['win_rate'],
                    'total_matches' => $stats['total_matches'],
                    'tournament_participations' => $tournamentParticipations
                ],
                'performance_summary' => [
                    'rank' => $stats['rankings']['national'],
                    'best_streak' => $bestStreak
                ],
                'achievements' => $achievements
            ]
        ]);
    }
    
    /**
     * Calculate player's best winning streak
     */
    private function calculateBestStreak($user)
    {
        $matches = DB::table('matches')
            ->where(function($q) use ($user) {
                $q->where('player_1_id', $user->id)
                  ->orWhere('player_2_id', $user->id);
            })
            ->where('status', 'completed')
            ->orderBy('created_at', 'asc')
            ->get();
        
        $currentStreak = 0;
        $bestStreak = 0;
        
        foreach ($matches as $match) {
            if ($match->winner_id == $user->id) {
                $currentStreak++;
                $bestStreak = max($bestStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }
        
        return $bestStreak;
    }

    /**
     * Calculate player statistics
     */
    private function calculatePlayerStats($player)
    {
        $matches = DB::table('matches')->where(function($q) use ($player) {
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
        $recentMatches = DB::table('matches')->where(function($q) use ($player) {
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
        
        // Calculate rankings based on wins (leaderboard points)
        $playerLeaderboardPoints = $wins * 10;
        
        // Community ranking
        $communityRank = 1;
        if ($player->community_id) {
            $communityRank = DB::table('users')
                ->where('community_id', $player->community_id)
                ->where('id', '!=', $player->id)
                ->where(function($query) use ($playerLeaderboardPoints) {
                    $query->whereRaw('(SELECT COUNT(*) FROM matches WHERE winner_id = users.id AND status = "completed") * 10 > ?', [$playerLeaderboardPoints]);
                })
                ->count() + 1;
        }
        
        // For county, regional, and national rankings, we'll use a simplified approach
        $countyRank = 1;
        $regionalRank = 1;
        $nationalRank = DB::table('users')
            ->where('id', '!=', $player->id)
            ->where(function($query) use ($playerLeaderboardPoints) {
                $query->whereRaw('(SELECT COUNT(*) FROM matches WHERE winner_id = users.id AND status = "completed") * 10 > ?', [$playerLeaderboardPoints]);
            })
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
            ->leftJoin('regions', 'counties.region_id', '=', 'regions.id')
            ->where('users.email', '!=', 'admin@cuesports.com');

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
        
        // Get all users who have played matches (excluding admin)
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
            ->where('email', '!=', 'admin@cuesports.com')
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
