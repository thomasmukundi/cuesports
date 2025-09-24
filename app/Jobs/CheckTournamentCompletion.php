<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\Notification;
use App\Models\User;
use App\Services\MatchAlgorithmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckTournamentCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tournamentId;
    protected $matchService;

    /**
     * Create a new job instance.
     */
    public function __construct($tournamentId)
    {
        $this->tournamentId = $tournamentId;
    }

    /**
     * Execute the job.
     */
    public function handle(MatchAlgorithmService $matchService)
    {
        $this->matchService = $matchService;
        $tournament = Tournament::find($this->tournamentId);
        
        if (!$tournament || $tournament->status !== 'ongoing') {
            return;
        }
        
        Log::info("Checking tournament completion for: {$tournament->name}");
        
        // Get all levels to check
        $levels = $tournament->special ? ['special'] : ['community', 'county', 'regional', 'national'];
        
        foreach ($levels as $level) {
            $this->checkLevelCompletion($tournament, $level);
        }
        
        // Check if entire tournament is completed
        $this->checkOverallCompletion($tournament);
    }

    /**
     * Check completion for a specific level
     */
    private function checkLevelCompletion(Tournament $tournament, $level)
    {
        // Get all distinct round names for this level
        $roundNames = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->distinct()
            ->pluck('round_name');
        
        foreach ($roundNames as $roundName) {
            $this->checkRoundCompletion($tournament, $level, $roundName);
        }
    }
    
    /**
     * Check completion for a specific round within a level
     */
    private function checkRoundCompletion(Tournament $tournament, $level, $roundName)
    {
        // Group matches by community/county based on level
        if ($level === 'community') {
            $this->checkCommunityRoundCompletion($tournament, $level, $roundName);
        } elseif ($level === 'county') {
            $this->checkCountyRoundCompletion($tournament, $level, $roundName);
        } else {
            // For regional/national, check all matches in the round
            $this->checkGeneralRoundCompletion($tournament, $level, $roundName);
        }
    }
    
    /**
     * Check community level round completion
     */
    private function checkCommunityRoundCompletion(Tournament $tournament, $level, $roundName)
    {
        // Get all matches for this round
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->with(['player1', 'player2'])
            ->get();
            
        // Group by community
        $communitiesMatches = $matches->groupBy(function($match) {
            return $match->player1->community_id ?? $match->player2->community_id;
        });
        
        foreach ($communitiesMatches as $communityId => $communityMatches) {
            $completedMatches = $communityMatches->where('status', 'completed')->count();
            $totalMatches = $communityMatches->count();
            
            if ($completedMatches === $totalMatches && $totalMatches > 0) {
                Log::info("Community {$communityId} completed round {$roundName} in {$level} level");
                
                // If automation mode is automatic, generate next round for this community
                if ($tournament->automation_mode === 'automatic') {
                    $this->autoProgressCommunity($tournament, $level, $roundName, $communityId);
                }
            }
        }
    }
    
    /**
     * Check county level round completion
     */
    private function checkCountyRoundCompletion(Tournament $tournament, $level, $roundName)
    {
        // Get all matches for this round
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->with(['player1', 'player2'])
            ->get();
            
        // Group by county
        $countiesMatches = $matches->groupBy(function($match) {
            return $match->player1->county_id ?? $match->player2->county_id;
        });
        
        foreach ($countiesMatches as $countyId => $countyMatches) {
            $completedMatches = $countyMatches->where('status', 'completed')->count();
            $totalMatches = $countyMatches->count();
            
            if ($completedMatches === $totalMatches && $totalMatches > 0) {
                Log::info("County {$countyId} completed round {$roundName} in {$level} level");
                
                // If automation mode is automatic, generate next round for this county
                if ($tournament->automation_mode === 'automatic') {
                    $this->autoProgressCounty($tournament, $level, $roundName, $countyId);
                }
            }
        }
    }
    
    /**
     * Check general round completion (regional/national)
     */
    private function checkGeneralRoundCompletion(Tournament $tournament, $level, $roundName)
    {
        $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->where('status', 'completed')
            ->count();
            
        $totalMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $level)
            ->where('round_name', $roundName)
            ->count();
            
        if ($completedMatches === $totalMatches && $totalMatches > 0) {
            Log::info("Round {$roundName} completed in {$level} level");
            
            // If automation mode is automatic, generate next round
            if ($tournament->automation_mode === 'automatic') {
                $this->autoProgressGeneral($tournament, $level, $roundName);
            }
        }
    }

    /**
     * Auto progress community tournament to next round
     */
    private function autoProgressCommunity(Tournament $tournament, $level, $roundName, $communityId)
    {
        try {
            // Get winners from completed matches in this community and round
            $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('round_name', $roundName)
                ->where('status', 'completed')
                ->with(['player1', 'player2'])
                ->get()
                ->filter(function($match) use ($communityId) {
                    return ($match->player1->community_id ?? $match->player2->community_id) == $communityId;
                });
            
            $winners = User::whereIn('id', $completedMatches->pluck('winner_id'))->get();
            
            if ($winners->count() > 1) {
                // Generate next round for this community
                $this->matchService->initializeTournamentLevel($tournament->id, $level, $winners->toArray());
                Log::info("Auto-generated next round for community {$communityId} in {$level} level");
            } else if ($winners->count() == 1) {
                Log::info("Community {$communityId} champion determined for {$level} level");
                // Check if we should move to next level
                if ($level === 'community') {
                    $this->checkForNextLevel($tournament, $level);
                }
            }
        } catch (\Exception $e) {
            Log::error("Community auto progression failed: " . $e->getMessage());
        }
    }
    
    /**
     * Auto progress county tournament to next round
     */
    private function autoProgressCounty(Tournament $tournament, $level, $roundName, $countyId)
    {
        try {
            // Get winners from completed matches in this county and round
            $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('round_name', $roundName)
                ->where('status', 'completed')
                ->with(['player1', 'player2'])
                ->get()
                ->filter(function($match) use ($countyId) {
                    return ($match->player1->county_id ?? $match->player2->county_id) == $countyId;
                });
            
            $winners = User::whereIn('id', $completedMatches->pluck('winner_id'))->get();
            
            if ($winners->count() > 1) {
                // Generate next round for this county
                $this->matchService->initializeTournamentLevel($tournament->id, $level, $winners->toArray());
                Log::info("Auto-generated next round for county {$countyId} in {$level} level");
            } else if ($winners->count() == 1) {
                Log::info("County {$countyId} champion determined for {$level} level");
                // Check if we should move to next level
                if ($level === 'county') {
                    $this->checkForNextLevel($tournament, $level);
                }
            }
        } catch (\Exception $e) {
            Log::error("County auto progression failed: " . $e->getMessage());
        }
    }
    
    /**
     * Auto progress general tournament to next round
     */
    private function autoProgressGeneral(Tournament $tournament, $level, $roundName)
    {
        try {
            // Get winners from completed matches in this round
            $completedMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', $level)
                ->where('round_name', $roundName)
                ->where('status', 'completed')
                ->get();
            
            $winners = User::whereIn('id', $completedMatches->pluck('winner_id'))->get();
            
            if ($winners->count() > 1) {
                // Generate next round
                $this->matchService->initializeTournamentLevel($tournament->id, $level, $winners->toArray());
                Log::info("Auto-generated next round for {$level} level");
            } else if ($winners->count() == 1) {
                Log::info("Champion determined for {$level} level");
                // Check if we should move to next level
                if ($level !== 'national' && !$tournament->special) {
                    $this->checkForNextLevel($tournament, $level);
                }
            }
        } catch (\Exception $e) {
            Log::error("General auto progression failed: " . $e->getMessage());
        }
    }

    /**
     * Check if ready for next level
     */
    private function checkForNextLevel(Tournament $tournament, $currentLevel)
    {
        $nextLevel = $this->getNextLevel($currentLevel);
        if (!$nextLevel) return;
        
        // Check if all groups in current level are completed
        $incompleteLevels = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', $currentLevel)
            ->whereNotIn('status', ['completed', 'forfeit'])
            ->count();
        
        if ($incompleteLevels === 0) {
            // All matches in current level completed
            // Initialize next level if automatic mode
            if ($tournament->automation_mode === 'automatic') {
                try {
                    $this->matchService->initialize($tournament->id, $nextLevel);
                    Log::info("Auto-initialized {$nextLevel} level for tournament {$tournament->name}");
                } catch (\Exception $e) {
                    Log::error("Failed to auto-initialize next level: " . $e->getMessage());
                }
            } else {
                // Notify admin to initialize next level
                $this->notifyAdmin(
                    $tournament, 
                    $currentLevel, 
                    null, 
                    "All {$currentLevel} matches completed. Ready to initialize {$nextLevel} level."
                );
            }
        }
    }

    /**
     * Check if entire tournament is completed
     */
    private function checkOverallCompletion(Tournament $tournament)
    {
        $pendingMatches = PoolMatch::where('tournament_id', $tournament->id)
            ->whereNotIn('status', ['completed', 'forfeit'])
            ->count();
        
        if ($pendingMatches === 0) {
            // All matches completed
            $tournament->update(['status' => 'completed']);
            
            // Award prizes
            $this->awardPrizes($tournament);
            
            // Notify all participants
            $this->notifyTournamentCompletion($tournament);
            
            Log::info("Tournament {$tournament->name} has been completed!");
        }
    }

    /**
     * Award prizes to winners
     */
    private function awardPrizes(Tournament $tournament)
    {
        $winners = \App\Models\Winner::where('tournament_id', $tournament->id)->get();
        
        foreach ($winners as $winner) {
            $prizeAmount = $this->calculatePrize($tournament, $winner->level, $winner->position);
            
            if ($prizeAmount > 0) {
                $winner->update([
                    'prize_awarded' => true,
                    'prize_amount' => $prizeAmount
                ]);
                
                // Notify winner
                Notification::create([
                    'player_id' => $winner->player_id,
                    'type' => 'prize',
                    'message' => "Congratulations! You've won ${$prizeAmount} in {$tournament->name}",
                    'data' => [
                        'tournament_id' => $tournament->id,
                        'level' => $winner->level,
                        'position' => $winner->position,
                        'amount' => $prizeAmount
                    ]
                ]);
            }
        }
    }

    /**
     * Calculate prize amount
     */
    private function calculatePrize(Tournament $tournament, $level, $position)
    {
        if ($position > 3) return 0;
        
        $prizeField = match($level) {
            'community' => 'community_prize',
            'county' => 'county_prize',
            'regional' => 'regional_prize',
            'national' => 'national_prize',
            'special' => 'national_prize',
            default => null
        };
        
        if (!$prizeField) return 0;
        
        $totalPrize = $tournament->$prizeField ?? 0;
        
        // Prize distribution: 1st: 50%, 2nd: 30%, 3rd: 20%
        return match($position) {
            1 => $totalPrize * 0.5,
            2 => $totalPrize * 0.3,
            3 => $totalPrize * 0.2,
            default => 0
        };
    }

    /**
     * Notify admin about completion
     */
    private function notifyAdmin(Tournament $tournament, $level, $groupId, $message)
    {
        // Get admin user (you might want to configure this)
        $adminEmail = env('ADMIN_EMAIL', 'admin@cuesports.com');
        $admin = User::where('email', $adminEmail)->first();
        
        if ($admin) {
            Notification::create([
                'player_id' => $admin->id,
                'type' => 'admin_alert',
                'message' => $message,
                'data' => [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'group_id' => $groupId
                ]
            ]);
        }
        
        // Also log for monitoring
        Log::info("Admin notification: {$message}");
    }

    /**
     * Notify all participants about tournament completion
     */
    private function notifyTournamentCompletion(Tournament $tournament)
    {
        $participants = $tournament->approvedPlayers;
        
        foreach ($participants as $player) {
            Notification::create([
                'player_id' => $player->id,
                'type' => 'tournament_complete',
                'message' => "{$tournament->name} has been completed! Check the final standings.",
                'data' => ['tournament_id' => $tournament->id]
            ]);
        }
    }

    /**
     * Get next level
     */
    private function getNextLevel($currentLevel)
    {
        return match($currentLevel) {
            'community' => 'county',
            'county' => 'regional',
            'regional' => 'national',
            default => null
        };
    }
}
