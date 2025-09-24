<?php

namespace App\Http\Controllers;

use App\Models\PoolMatch;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{
    public function __construct()
    {
        // Middleware is applied via routes
    }

    /**
     * Get player's matches
     */
    public function myMatches(Request $request)
    {
        $user = Auth::user();
        $query = $user->allMatches()
            ->with(['player1', 'player2', 'tournament', 'winner']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('limit')) {
            $query->limit($request->limit);
        }
        
        $matches = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json($matches);
    }

    /**
     * Get match details
     */
    public function show($matchId)
    {
        $match = PoolMatch::with(['player1', 'player2', 'tournament', 'winner', 'chatMessages'])
            ->findOrFail($matchId);
        
        // Check if user is part of this match
        $user = Auth::user();
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json($match);
    }

    /**
     * Propose match dates
     */
    public function proposeDates(Request $request, $matchId)
    {
        $validated = $request->validate([
            'dates' => 'required|array|min:3|max:7',
            'dates.*' => 'required|date|after:today'
        ]);
        
        $match = PoolMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Update proposed dates
        $match->proposed_dates = $validated['dates'];
        $match->save();
        
        // Notify other player
        $otherPlayerId = $match->player_1_id === $user->id ? $match->player_2_id : $match->player_1_id;
        Notification::create([
            'player_id' => $otherPlayerId,
            'type' => 'match_scheduled',
            'message' => 'Your opponent has proposed dates for your match',
            'data' => ['match_id' => $matchId, 'dates' => $validated['dates']]
        ]);
        
        return response()->json([
            'message' => 'Dates proposed successfully',
            'match' => $match
        ]);
    }

    /**
     * Select preferred dates
     */
    public function selectDates(Request $request, $matchId)
    {
        $validated = $request->validate([
            'dates' => 'required|array|min:1',
            'dates.*' => 'required|date'
        ]);
        
        $match = PoolMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Store player's preferred dates
        if ($match->player_1_id === $user->id) {
            $match->player_1_preferred_dates = $validated['dates'];
        } else {
            $match->player_2_preferred_dates = $validated['dates'];
        }
        
        // Check for common dates and schedule if both have selected
        if ($match->player_1_preferred_dates && $match->player_2_preferred_dates) {
            $commonDates = array_intersect(
                $match->player_1_preferred_dates,
                $match->player_2_preferred_dates
            );
            
            if (!empty($commonDates)) {
                // Schedule on the first common date
                $match->scheduled_date = reset($commonDates);
                $match->status = 'scheduled';
                
                // Notify both players
                Notification::create([
                    'player_id' => $match->player_1_id,
                    'type' => 'match_scheduled',
                    'message' => "Your match has been scheduled for {$match->scheduled_date}",
                    'data' => ['match_id' => $matchId, 'date' => $match->scheduled_date]
                ]);
                
                Notification::create([
                    'player_id' => $match->player_2_id,
                    'type' => 'match_scheduled',
                    'message' => "Your match has been scheduled for {$match->scheduled_date}",
                    'data' => ['match_id' => $matchId, 'date' => $match->scheduled_date]
                ]);
            }
        }
        
        $match->save();
        
        return response()->json([
            'message' => 'Dates selected successfully',
            'match' => $match
        ]);
    }

    /**
     * Submit match results
     */
    public function submitResults(Request $request, $matchId)
    {
        $validated = $request->validate([
            'player_1_points' => 'required|integer|min:0',
            'player_2_points' => 'required|integer|min:0'
        ]);
        
        $match = PoolMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Check if match is in valid state
        if (!in_array($match->status, ['scheduled', 'in_progress'])) {
            return response()->json(['error' => 'Match is not ready for results'], 400);
        }
        
        DB::beginTransaction();
        try {
            // Update match with results
            $match->player_1_points = $validated['player_1_points'];
            $match->player_2_points = $validated['player_2_points'];
            $match->submitted_by = $user->id;
            $match->status = 'pending_confirmation';
            
            // Determine winner (but don't set yet - wait for confirmation)
            $match->determineWinner();
            
            $match->save();
            
            // Notify other player for confirmation
            $otherPlayerId = $match->player_1_id === $user->id ? $match->player_2_id : $match->player_1_id;
            Notification::create([
                'player_id' => $otherPlayerId,
                'type' => 'result_confirmation',
                'message' => 'Match results have been submitted. Please confirm.',
                'data' => [
                    'match_id' => $matchId,
                    'player_1_points' => $validated['player_1_points'],
                    'player_2_points' => $validated['player_2_points']
                ]
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Results submitted. Waiting for confirmation from opponent.',
                'match' => $match
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to submit results'], 500);
        }
    }

    /**
     * Confirm match results
     */
    public function confirmResults(Request $request, $matchId)
    {
        \Log::info("=== WRONG CONTROLLER CALLED ===", [
            'controller' => 'App\Http\Controllers\MatchController',
            'match_id' => $matchId,
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);
        
        $validated = $request->validate([
            'confirm' => 'required|boolean'
        ]);
        
        $match = PoolMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Check if user is the one who needs to confirm
        if ($match->submitted_by === $user->id) {
            return response()->json(['error' => 'You cannot confirm your own submission'], 403);
        }
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Check if match is pending confirmation
        if ($match->status !== 'pending_confirmation') {
            return response()->json(['error' => 'Match is not pending confirmation'], 400);
        }
        
        DB::beginTransaction();
        try {
            if ($validated['confirm']) {
                // Confirm results
                $match->status = 'completed';
                $match->save();
                
                // Update player total points
                $player1 = User::find($match->player_1_id);
                $player2 = User::find($match->player_2_id);
                
                $player1->updateTotalPoints();
                $player2->updateTotalPoints();
                
                // Notify both players
                Notification::create([
                    'player_id' => $match->player_1_id,
                    'type' => 'result',
                    'message' => 'Match results have been confirmed',
                    'data' => ['match_id' => $matchId]
                ]);
                
                Notification::create([
                    'player_id' => $match->player_2_id,
                    'type' => 'result',
                    'message' => 'Match results have been confirmed',
                    'data' => ['match_id' => $matchId]
                ]);
                
                DB::commit();
                
                return response()->json([
                    'message' => 'Results confirmed successfully',
                    'match' => $match
                ]);
            } else {
                // Reject results - reset match
                $match->player_1_points = null;
                $match->player_2_points = null;
                $match->winner_id = null;
                $match->submitted_by = null;
                $match->status = 'scheduled';
                $match->save();
                
                // Notify submitter
                Notification::create([
                    'player_id' => $match->submitted_by,
                    'type' => 'result',
                    'message' => 'Match results have been rejected. Please resubmit.',
                    'data' => ['match_id' => $matchId]
                ]);
                
                DB::commit();
                
                return response()->json([
                    'message' => 'Results rejected. Match reset to scheduled state.',
                    'match' => $match
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process confirmation'], 500);
        }
    }

    /**
     * Report forfeit
     */
    public function reportForfeit(Request $request, $matchId)
    {
        $match = PoolMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        DB::beginTransaction();
        try {
            // The reporting player wins by forfeit
            $match->winner_id = $user->id;
            $match->status = 'forfeit';
            
            // Set points (forfeit typically gives default win)
            if ($match->player_1_id === $user->id) {
                $match->player_1_points = 100; // Default win points
                $match->player_2_points = 0;
            } else {
                $match->player_1_points = 0;
                $match->player_2_points = 100; // Default win points
            }
            
            $match->save();
            
            // Update total points
            $player1 = User::find($match->player_1_id);
            $player2 = User::find($match->player_2_id);
            
            $player1->updateTotalPoints();
            $player2->updateTotalPoints();
            
            // Notify both players
            $otherPlayerId = $match->player_1_id === $user->id ? $match->player_2_id : $match->player_1_id;
            
            Notification::create([
                'player_id' => $otherPlayerId,
                'type' => 'result',
                'message' => 'You have forfeited the match',
                'data' => ['match_id' => $matchId]
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Forfeit reported successfully',
                'match' => $match
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to report forfeit'], 500);
        }
    }
}
