<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\MatchMessage;
use App\Models\Notification;
use App\Services\MatchAlgorithmService;
use App\Services\RoundRobinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class MatchController extends Controller
{
    protected $matchAlgorithmService;
    protected $roundRobinService;

    public function __construct(MatchAlgorithmService $matchAlgorithmService, RoundRobinService $roundRobinService)
    {
        $this->matchAlgorithmService = $matchAlgorithmService;
        $this->roundRobinService = $roundRobinService;
    }

    /**
     * Get matches - all matches or user-specific based on parameter
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Check if user wants only their matches
        $userMatches = $request->boolean('user_matches', false);
        
        $query = PoolMatch::with(['player1', 'player2', 'tournament']);
        
        if ($userMatches) {
            $query->where(function ($q) use ($user) {
                $q->where('player_1_id', $user->id)
                  ->orWhere('player_2_id', $user->id);
            });
        }
            
        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $status = $request->status;
            
            // Handle different status mappings
            $statusMap = [
                'pending' => 'pending',
                'scheduled' => 'scheduled', 
                'pending_confirmation' => 'pending_confirmation',
                'completed' => 'completed',
                'forfeit' => 'forfeit'
            ];
            
            if (isset($statusMap[$status])) {
                $query->where('status', $statusMap[$status]);
                \Log::info("Filtering matches by status", [
                    'requested_status' => $status,
                    'mapped_status' => $statusMap[$status],
                    'user_id' => $user->id
                ]);
            } else {
                \Log::warning("Unknown status filter requested", [
                    'requested_status' => $status,
                    'user_id' => $user->id
                ]);
            }
        }
        
        $matches = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $matches->map(function ($match) use ($user) {
                $opponent = $match->player_1_id === $user->id ? $match->player2 : $match->player1;
                
                return [
                    'id' => $match->id,
                    'tournament' => [
                        'id' => $match->tournament ? $match->tournament->id : null,
                        'name' => $match->tournament ? $match->tournament->name : 'Unknown Tournament',
                    ],
                    'opponent' => [
                        'id' => $opponent ? $opponent->id : null,
                        'name' => $opponent ? $opponent->name : 'Unknown Opponent',
                        'profile_image' => $opponent ? $opponent->profile_image : null,
                    ],
                    'status' => $match->status,
                    'level' => $match->level,
                    'scheduled_date' => $match->scheduled_date,
                    'scheduled_time' => $match->scheduled_time,
                    'player_1_points' => $match->player_1_points,
                    'player_2_points' => $match->player_2_points,
                    'submitted_by' => $match->submitted_by,
                    'player_1_preferred_dates' => $match->player_1_preferred_dates,
                    'player_2_preferred_dates' => $match->player_2_preferred_dates,
                    'proposed_dates' => $match->proposed_dates,
                    'no_matching_dates' => $this->hasNoMatchingDates($match),
                    'video_url' => $match->video_url,
                    'created_at' => $match->created_at,
                    'is_my_turn' => $this->isUserTurn($match, $user),
                ];
            })
        ]);
    }

    /**
     * Get user's matches (legacy method)
     */
    public function myMatches(Request $request)
    {
        return $this->index($request->merge(['user_matches' => true]));
    }
    
    /**
     * Propose dates for a match
     */
    public function proposeDates(Request $request, PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to propose dates for this match'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'preferred_dates' => 'required|array|min:1',
            'preferred_dates.*' => 'required|date|after:today'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Update preferred dates for the user
        if ($match->player_1_id === $user->id) {
            $match->player_1_preferred_dates = json_encode($request->preferred_dates);
        } else {
            $match->player_2_preferred_dates = json_encode($request->preferred_dates);
        }
        
        // Update proposed dates (combine both players' preferences)
        $player1Dates = $match->player_1_preferred_dates ? json_decode($match->player_1_preferred_dates, true) : [];
        $player2Dates = $match->player_2_preferred_dates ? json_decode($match->player_2_preferred_dates, true) : [];
        $allDates = array_unique(array_merge($player1Dates, $player2Dates));
        $match->proposed_dates = json_encode($allDates);
        
        $match->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Preferred dates updated successfully',
            'data' => [
                'proposed_dates' => $allDates,
                'player_1_preferred_dates' => $player1Dates,
                'player_2_preferred_dates' => $player2Dates
            ]
        ]);
    }
    
    /**
     * Schedule a match with selected date
     */
    public function scheduleMatch(Request $request, PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to schedule this match'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'scheduled_date' => 'required|date|after:today'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $match->scheduled_date = $request->scheduled_date;
        $match->status = 'scheduled';
        $match->save();
        
        // Send notification to opponent
        $opponent = $match->player_1_id === $user->id ? $match->player2 : $match->player1;
        Notification::create([
            'player_id' => $opponent->id,
            'type' => 'match_scheduled',
            'message' => "{$user->name} has scheduled your match for {$request->scheduled_date}",
            'data' => [
                'match_id' => $match->id,
                'tournament_name' => $match->tournament->name,
                'scheduled_date' => $request->scheduled_date,
                'opponent_name' => $user->name
            ]
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Match scheduled successfully',
            'data' => [
                'scheduled_date' => $match->scheduled_date,
                'status' => $match->status
            ]
        ]);
    }

    /**
     * Get match details
     */
    public function show(PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $opponent = $match->player_1_id === $user->id ? $match->player2 : $match->player1;
        
        return response()->json([
            'success' => true,
            'match' => [
                'id' => $match->id,
                'tournament' => [
                    'id' => $match->tournament->id,
                    'name' => $match->tournament->name,
                ],
                'opponent' => [
                    'id' => $opponent->id,
                    'name' => $opponent->name,
                    'profile_image' => $opponent->profile_image,
                ],
                'status' => $match->status,
                'level' => $match->level,
                'scheduled_date' => $match->scheduled_date,
                'scheduled_time' => $match->scheduled_time,
                'player_1_score' => $match->player_1_score,
                'player_2_score' => $match->player_2_score,
                'player_1_preferred_dates' => $match->player_1_preferred_dates,
                'player_2_preferred_dates' => $match->player_2_preferred_dates,
                'proposed_dates' => $match->proposed_dates,
                'video_url' => $match->video_url,
                'winner_id' => $match->winner_id,
                'created_at' => $match->created_at,
                'is_my_turn' => $this->isUserTurn($match, $user),
            ]
        ]);
    }
    
    /**
     * Select available dates for a match
     */
    public function selectDates(Request $request, PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'dates' => 'required|array|min:1',
            'dates.*' => 'required|date_format:Y-m-d',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $result = $this->matchAlgorithmService->selectPlayerAvailability(
                $match->id,
                $user->id,
                $request->dates
            );
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'status' => $result['status'],
                'scheduled_date' => $result['scheduled_date'] ?? null,
                'match' => $match->fresh()->load(['player1', 'player2', 'tournament'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Submit match results using win/lose format
     */
    public function submitWinLoseResult(Request $request, PoolMatch $match)
    {
        $user = auth()->user();
        
        \Log::info("=== SUBMIT WIN/LOSE RESULT ENDPOINT CALLED ===", [
            'match_id' => $match->id,
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to submit results for this match'
            ], 403);
        }

        // Check if match is in valid state for submission
        if (!in_array($match->status, ['scheduled', 'in_progress', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Match is not ready for result submission'
            ], 400);
        }

        // Check if results already submitted
        if ($match->status === 'pending_confirmation') {
            return response()->json([
                'success' => false,
                'message' => 'Results have already been submitted for this match'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'result' => 'required|in:won,lost',
            'video_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info("=== PROCESSING WIN/LOSE RESULT ===", [
            'user_id' => $user->id,
            'result_submitted' => $request->result,
            'user_is_player_1' => $user->id == $match->player_1_id,
            'user_is_player_2' => $user->id == $match->player_2_id
        ]);

        // Determine points based on win/lose result
        $player1Points = 0;
        $player2Points = 0;

        if ($request->result === 'won') {
            // Current user won - they should get 3 points
            if ($user->id == $match->player_1_id) {
                // User is player_1 and won
                $player1Points = 3;
                $player2Points = 0;
                \Log::info("CASE: User is player_1 and WON: P1=3, P2=0");
            } else {
                // User is player_2 and won
                $player1Points = 0;
                $player2Points = 3;
                \Log::info("CASE: User is player_2 and WON: P1=0, P2=3");
            }
        } elseif ($request->result === 'lost') {
            // Current user lost - opponent should get 3 points
            if ($user->id == $match->player_1_id) {
                // User is player_1 and lost, so player_2 wins
                $player1Points = 0;
                $player2Points = 3;
                \Log::info("CASE: User is player_1 and LOST: P1=0, P2=3");
            } else {
                // User is player_2 and lost, so player_1 wins
                $player1Points = 3;
                $player2Points = 0;
                \Log::info("CASE: User is player_2 and LOST: P1=3, P2=0");
            }
        }

        // Determine winner based on points
        $winnerId = null;
        if ($player1Points > $player2Points) {
            $winnerId = $match->player_1_id;
        } elseif ($player2Points > $player1Points) {
            $winnerId = $match->player_2_id;
        }

        \Log::info("=== FINAL CALCULATION ===", [
            'player_1_points' => $player1Points,
            'player_2_points' => $player2Points,
            'winner_id' => $winnerId,
            'submitter_id' => $user->id
        ]);

        try {
            DB::beginTransaction();

            // Update match with results
            $match->player_1_points = $player1Points;
            $match->player_2_points = $player2Points;
            $match->winner_id = $winnerId;
            $match->submitted_by = $user->id;
            $match->status = 'pending_confirmation';
            
            if ($request->video_url) {
                $match->video_url = $request->video_url;
            }

            $match->save();

            \Log::info("=== MATCH UPDATED ===", [
                'match_id' => $match->id,
                'player_1_points' => $match->player_1_points,
                'player_2_points' => $match->player_2_points,
                'winner_id' => $match->winner_id,
                'submitted_by' => $match->submitted_by,
                'status' => $match->status
            ]);

            // Notify opponent for confirmation
            $opponentId = $match->player_1_id === $user->id ? $match->player_2_id : $match->player_1_id;
            
            Notification::create([
                'player_id' => $opponentId,
                'type' => 'result_confirmation',
                'message' => 'Match results have been submitted. Please confirm.',
                'data' => [
                    'match_id' => $match->id,
                    'submitter_name' => $user->name,
                    'result' => $request->result
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Results submitted successfully. Waiting for opponent confirmation.',
                'match' => $match->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error submitting win/lose results", [
                'match_id' => $match->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit results. Please try again.'
            ], 500);
        }
    }

    /**
     * Submit match results using actual points
     */
    public function submitPointsResult(Request $request, PoolMatch $match)
    {
        $user = auth()->user();
        
        \Log::info("=== SUBMIT POINTS RESULT ENDPOINT CALLED ===", [
            'match_id' => $match->id,
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to submit results for this match'
            ], 403);
        }

        // Check if match is in valid state for submission
        if (!in_array($match->status, ['scheduled', 'in_progress', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Match is not ready for result submission'
            ], 400);
        }

        // Check if results already submitted
        if ($match->status === 'pending_confirmation') {
            return response()->json([
                'success' => false,
                'message' => 'Results have already been submitted for this match'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'my_points' => 'required|integer|min:0|max:5',
            'opponent_points' => 'required|integer|min:0|max:5',
            'video_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $myPoints = $request->my_points;
        $opponentPoints = $request->opponent_points;
        $totalPoints = $myPoints + $opponentPoints;

        // Validate total points (must be 3 or 5)
        if (!in_array($totalPoints, [3, 5])) {
            return response()->json([
                'success' => false,
                'message' => 'Total points must equal 3 or 5'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Determine which player submitted and set points accordingly
            if ($user->id == $match->player_1_id) {
                $match->player_1_points = $myPoints;
                $match->player_2_points = $opponentPoints;
            } else {
                $match->player_1_points = $opponentPoints;
                $match->player_2_points = $myPoints;
            }

            $match->status = 'pending_confirmation';
            $match->submitted_by = $user->id;
            
            if ($request->video_url) {
                $match->video_url = $request->video_url;
            }

            $match->save();

            // Send notification to opponent
            $opponent = $user->id == $match->player_1_id ? $match->player2 : $match->player1;
            
            \Log::info("Sending result confirmation notification", [
                'submitter_id' => $user->id,
                'submitter_name' => $user->name,
                'opponent_id' => $opponent->id,
                'opponent_name' => $opponent->name,
                'match_id' => $match->id,
                'player_1_id' => $match->player_1_id,
                'player_2_id' => $match->player_2_id
            ]);
            
            Notification::create([
                'player_id' => $opponent->id,
                'type' => 'result_confirmation',
                'message' => "{$user->name} has submitted match results. Please confirm or dispute.",
                'data' => [
                    'match_id' => $match->id,
                    'submitter_name' => $user->name,
                    'player_1_points' => $match->player_1_points,
                    'player_2_points' => $match->player_2_points,
                ]
            ]);

            DB::commit();

            \Log::info("Successfully submitted points result", [
                'match_id' => $match->id,
                'player_1_points' => $match->player_1_points,
                'player_2_points' => $match->player_2_points,
                'submitted_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Match results submitted successfully! Waiting for opponent confirmation.',
                'match' => $match->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error submitting points results", [
                'match_id' => $match->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit results. Please try again.'
            ], 500);
        }
    }

    /**
     * Confirm match results
     */
    public function confirmResults(Request $request, PoolMatch $match)
    {
        \Log::info("=== CONFIRM RESULTS ENDPOINT CALLED ===", [
            'match_id' => $match->id,
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'match_status' => $match->status
        ]);
        
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Check if match is pending confirmation
        if ($match->status !== 'pending_confirmation') {
            return response()->json([
                'success' => false,
                'message' => 'Match is not pending confirmation'
            ], 400);
        }
        
        // Check if user is not the one who submitted results
        if ($match->submitted_by === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot confirm your own results'
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'confirm' => 'required|boolean',
            'dispute_reason' => 'required_if:confirm,false|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        if ($request->confirm === true) {
            // Determine winner
            $winnerId = null;
            if ($match->player_1_points > $match->player_2_points) {
                $winnerId = $match->player_1_id;
            } elseif ($match->player_2_points > $match->player_1_points) {
                $winnerId = $match->player_2_id;
            }
            
            $match->update([
                'status' => 'completed',
                'winner_id' => $winnerId,
            ]);
            
            \Log::info("About to call checkRoundCompletionAndPositions", ['match_id' => $match->id]);
            
            // Check if round robin should be triggered before normal progression
            $this->checkRoundRobinTrigger($match);
            
            // Check if round robin matches are completed and generate winners
            $this->checkRoundRobinCompletion($match);
            
            // Check round completion and determine positions
            $this->checkRoundCompletionAndPositions($match);
            
            \Log::info("checkRoundCompletionAndPositions call completed", ['match_id' => $match->id]);
            
            $message = 'Match results confirmed successfully.';
        } else {
            $match->update([
                'status' => 'scheduled',
                'submitted_by' => null,
                'player_1_points' => null,
                'player_2_points' => null,
                'dispute_reason' => $request->dispute_reason,
            ]);
            
            // Notify the player who submitted results that they were rejected
            $submitter = $match->player_1_id === $match->submitted_by ? $match->player1 : $match->player2;
            
            Notification::create([
                'player_id' => $submitter->id,
                'type' => 'match_result_rejected',
                'message' => "{$user->name} has rejected the match results. Please resubmit the correct scores.",
                'data' => ['match_id' => $match->id]
            ]);
            
            $message = 'Match results rejected. The match is now open for new result submission.';
        }
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
    
    /**
     * Forfeit match - user loses automatically
     */
    public function forfeitMatch(Request $request, PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Check if match can be forfeited
        if (in_array($match->status, ['completed', 'disputed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot forfeit a completed or disputed match'
            ], 400);
        }
        
        // Determine winner (opponent of the user who forfeited)
        $winnerId = $match->player_1_id === $user->id ? $match->player_2_id : $match->player_1_id;
        
        // Set points: forfeiting user gets 0, opponent gets 3
        $player1Points = $match->player_1_id === $user->id ? 0 : 3;
        $player2Points = $match->player_2_id === $user->id ? 0 : 3;
        
        $match->update([
            'status' => 'completed',
            'winner_id' => $winnerId,
            'player_1_points' => $player1Points,
            'player_2_points' => $player2Points,
            'completed_at' => now(),
            'submitted_by' => $user->id,
        ]);
        
        // Notify the opponent
        $opponent = $match->player_1_id === $user->id ? $match->player2 : $match->player1;
        
        Notification::create([
            'player_id' => $opponent->id,
            'type' => 'match_forfeit',
            'message' => "{$user->name} has forfeited the match. You win by default!",
            'data' => ['match_id' => $match->id]
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Match forfeited successfully. Opponent wins by default.'
        ]);
    }
    
    /**
     * Get or create chat messages for a match
     */
    public function getMessages(PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $messages = $match->messages()->with('sender')->orderBy('created_at', 'asc')->get();
        
        return response()->json([
            'success' => true,
            'messages' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ],
                    'created_at' => $message->created_at->toISOString(),
                ];
            })
        ]);
    }
    
    /**
     * Send a chat message for a match
     */
    public function sendMessage(Request $request, PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $message = $match->messages()->create([
            'sender_id' => $user->id,
            'message' => $request->message,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'match' => [
                'id' => $match->id,
                'status' => $match->status,
                'submitted_by' => $match->submitted_by,
                'player_1_points' => $match->player_1_points,
                'player_2_points' => $match->player_2_points,
                'winner_id' => $match->winner_id,
            ],
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'created_at' => $message->created_at,
            ]
        ]);
    }

    /**
     * Check if round robin matches are completed and generate winners
     */
    private function checkRoundRobinCompletion($match)
    {
        try {
            $tournament = $match->tournament;
            $level = $match->level;
            
            // Check if this is a round robin match
            $roundRobinRoundName = $level === 'special' 
                ? 'Special Tournament Round Robin' 
                : ucfirst($level) . ' Round Robin';
            if ($match->round_name !== $roundRobinRoundName) {
                \Log::info("Not a round robin match, skipping round robin completion check", [
                    'match_round' => $match->round_name,
                    'expected_round_robin' => $roundRobinRoundName,
                    'level' => $level
                ]);
                return;
            }
            
            \Log::info("Checking round robin completion", [
                'match_id' => $match->id,
                'tournament_id' => $tournament->id,
                'level' => $level,
                'round_name' => $match->round_name
            ]);
            
            // Check if all round robin matches are completed
            if ($this->roundRobinService->areAllRoundRobinMatchesCompleted($tournament, $level)) {
                \Log::info("All round robin matches completed, calculating standings and generating winners", [
                    'tournament_id' => $tournament->id,
                    'level' => $level
                ]);
                
                // Calculate standings and generate winners
                $standings = $this->roundRobinService->calculateRoundRobinStandings($tournament, $level);
                
                \Log::info("Round robin standings calculated", [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'standings_count' => count($standings)
                ]);
                
                // Get winners based on standings
                $winners = $this->roundRobinService->getRoundRobinWinners($tournament, $level);
                
                \Log::info("Round robin winners determined", [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'winners_count' => count($winners),
                    'winners' => $winners
                ]);
                
                // Notify players about round robin completion
                $this->notifyPlayersAboutRoundRobinCompletion($tournament, $level, $standings);
                
            } else {
                \Log::info("Round robin not yet completed", [
                    'tournament_id' => $tournament->id,
                    'level' => $level
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error("Error checking round robin completion", [
                'match_id' => $match->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Notify players about round robin completion
     */
    private function notifyPlayersAboutRoundRobinCompletion($tournament, $level, $standings)
    {
        foreach ($standings as $standing) {
            Notification::create([
                'player_id' => $standing['player_id'] ?? $standing['player']['id'],
                'type' => 'round_robin_complete',
                'message' => "Round robin completed! You finished in position {$standing['position']}.",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'position' => $standing['position'],
                    'points' => $standing['points'] ?? $standing['average_points'],
                    'wins' => $standing['wins']
                ]
            ]);
        }
    }

    /**
     * Check round completion and determine positions
     */
    private function checkRoundCompletionAndPositions($match)
    {
        try {
            \Log::info("=== MATCH CONFIRMATION PROGRESSION START ===", [
                'match_id' => $match->id,
                'match_name' => $match->match_name,
                'tournament_id' => $match->tournament_id,
                'level' => $match->level,
                'level_name' => $match->level_name,
                'round_name' => $match->round_name,
                'winner_id' => $match->winner_id,
                'status' => $match->status,
            ]);
            
            // Skip progression for round robin matches - they are handled by checkRoundRobinCompletion
            $roundRobinRoundName = $match->level === 'special' 
                ? 'Special Tournament Round Robin' 
                : ucfirst($match->level) . ' Round Robin';
                
            if ($match->round_name === $roundRobinRoundName) {
                \Log::info("Skipping progression for round robin match - handled by checkRoundRobinCompletion", [
                    'match_id' => $match->id,
                    'round_name' => $match->round_name
                ]);
                return;
            }
            
            // Create progression controller
            $progressionController = new \App\Http\Controllers\Api\TournamentProgressionController(
                new \App\Services\MatchAlgorithmService()
            );
            
            \Log::info("Progression controller created successfully");
            
            // Check round completion
            $roundRequest = new \Illuminate\Http\Request([
                'tournament_id' => $match->tournament_id,
                'level' => $match->level,
                'level_name' => $match->level_name ?? 'default',
                'round_name' => $match->round_name,
            ]);
            
            \Log::info("Tournament progression: Checking round completion for match {$match->id}", [
                'tournament_id' => $match->tournament_id,
                'level' => $match->level,
                'level_name' => $match->level_name,
                'round_name' => $match->round_name,
            ]);
            
            \Log::info("About to call checkRoundCompletion...");
            $roundResponse = $progressionController->checkRoundCompletion($roundRequest);
            \Log::info("checkRoundCompletion call completed");
            
            $roundData = json_decode($roundResponse->getContent(), true);
            
            \Log::info("Tournament progression: Round completion response", $roundData);
            
            \Log::info("=== MATCH CONFIRMATION PROGRESSION SUCCESS ===");
            
        } catch (\Exception $e) {
            \Log::error("Tournament progression check failed: " . $e->getMessage(), [
                'match_id' => $match->id,
                'trace' => $e->getTraceAsString()
            ]);
            \Log::error("=== MATCH CONFIRMATION PROGRESSION FAILED ===");
        }
    }
    
    /**
     * Check if round robin should be triggered after match completion
     */
    private function checkRoundRobinTrigger($match)
    {
        try {
            $tournament = $match->tournament;
            $level = $match->level;
            
            // Don't trigger round robin if this match is already a round robin match
            $roundRobinRoundName = $level === 'special' 
                ? 'Special Tournament Round Robin' 
                : ucfirst($level) . ' Round Robin';
            
            if ($match->round_name === $roundRobinRoundName) {
                \Log::info("Match is already a round robin match, skipping round robin trigger", [
                    'match_id' => $match->id,
                    'round_name' => $match->round_name,
                    'level' => $level
                ]);
                return;
            }
            
            \Log::info("Checking round robin trigger", [
                'match_id' => $match->id,
                'tournament_id' => $tournament->id,
                'level' => $level,
                'winners_needed' => $tournament->winners,
                'match_round_name' => $match->round_name
            ]);
            
            // Get remaining players at this level who haven't been eliminated
            $remainingPlayers = $this->getRemainingPlayersAtLevel($tournament->id, $level);
            $playerCount = count($remainingPlayers);
            
            \Log::info("Remaining players count", [
                'tournament_id' => $tournament->id,
                'level' => $level,
                'remaining_players' => $playerCount,
                'player_ids' => $remainingPlayers
            ]);
            
            // Check if round robin should be triggered
            if ($this->roundRobinService->shouldTriggerRoundRobin($tournament, $level, $playerCount)) {
                \Log::info("Round robin conditions met, generating matches", [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'players' => $playerCount
                ]);
                
                // Generate round robin matches
                $roundRobinMatches = $this->roundRobinService->generateRoundRobinMatches(
                    $tournament, 
                    $level, 
                    $remainingPlayers
                );
                
                \Log::info("Round robin matches generated", [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                    'matches_count' => count($roundRobinMatches)
                ]);
                
                // Send notifications to players about round robin start
                $this->notifyPlayersAboutRoundRobin($tournament, $level, $remainingPlayers);
            }
            
        } catch (\Exception $e) {
            \Log::error("Error checking round robin trigger", [
                'match_id' => $match->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Get remaining players at a specific level who haven't been eliminated
     */
    private function getRemainingPlayersAtLevel($tournamentId, $level)
    {
        // Get all matches at this level that are completed
        $completedMatches = PoolMatch::where('tournament_id', $tournamentId)
            ->where('level', $level)
            ->where('status', 'completed')
            ->whereNotNull('winner_id')
            ->get();
            
        // Get all winners (remaining players)
        $winners = $completedMatches->pluck('winner_id')->unique()->values()->toArray();
        
        \Log::info("Calculated remaining players", [
            'tournament_id' => $tournamentId,
            'level' => $level,
            'completed_matches' => $completedMatches->count(),
            'winners' => $winners
        ]);
        
        return $winners;
    }
    
    /**
     * Notify players about round robin tournament start
     */
    private function notifyPlayersAboutRoundRobin($tournament, $level, $playerIds)
    {
        foreach ($playerIds as $playerId) {
            Notification::create([
                'player_id' => $playerId,
                'type' => 'other',
                'message' => "Round robin matches have been generated for {$tournament->name} at {$level} level. Check your matches and schedule them!",
                'data' => [
                    'tournament_id' => $tournament->id,
                    'level' => $level,
                ]
            ]);
        }
    }

    /**
     * Check if match has no matching dates
     */
    private function hasNoMatchingDates($match)
    {
        // If both players have selected dates but no proposed dates exist
        $proposedDates = $match->proposed_dates;
        if (is_array($proposedDates)) {
            $proposedDates = json_encode($proposedDates);
        }
        
        return $match->player_1_preferred_dates && 
               $match->player_2_preferred_dates && 
               (!$proposedDates || empty(json_decode($proposedDates, true)));
    }

    /**
     * Check if it's user's turn to take action
     */
    private function isUserTurn(PoolMatch $match, $user)
    {
        switch ($match->status) {
            case 'pending':
                // Check if user needs to select dates
                if ($match->player_1_id === $user->id && !$match->player_1_preferred_dates) {
                    return true;
                }
                if ($match->player_2_id === $user->id && !$match->player_2_preferred_dates) {
                    return true;
                }
                return false;
                
            case 'pending_confirmation':
                // Check if user needs to confirm results
                return $match->result_submitted_by !== $user->id;
                
            default:
                return false;
        }
    }

    /**
     * Get real-time match status for polling
     */
    public function getStatus(PoolMatch $match)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $match->id,
                'status' => $match->status,
                'proposed_dates' => $match->proposed_dates,
                'selected_dates' => $match->selected_dates,
                'player_1_score' => $match->player_1_score,
                'player_2_score' => $match->player_2_score,
                'submitted_by' => $match->submitted_by,
                'winner_id' => $match->winner_id,
                'last_updated' => $match->updated_at->toISOString(),
                'is_my_turn' => $this->isUserTurn($match, $user),
            ]
        ]);
    }
    
    /**
     * Get messages since timestamp for real-time chat
     */
    public function getMessagesSince(PoolMatch $match, $timestamp)
    {
        $user = auth()->user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $since = \Carbon\Carbon::parse($timestamp);
        $messages = $match->messages()
            ->with('sender')
            ->where('created_at', '>', $since)
            ->orderBy('created_at', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ],
                    'created_at' => $message->created_at->toISOString(),
                ];
            })
        ]);
    }
}
