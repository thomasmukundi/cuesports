<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\User;
use App\Models\Notification;
use App\Models\Transaction;
use App\Services\TinyPesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;

class TournamentRegistrationController extends Controller
{
    public function __construct()
    {
        // Remove middleware from constructor - will be handled by routes
    }

    /**
     * Get available tournaments for registration
     */
    public function available(Request $request)
    {
        $user = Auth::user();
        $query = Tournament::where('status', 'upcoming')
            ->where(function($q) {
                $q->whereNull('registration_deadline')
                  ->orWhere('registration_deadline', '>', now());
            });
        
        // Filter by area scope if applicable
        if ($request->has('scope')) {
            $query->where('area_scope', $request->scope);
        }
        
        // Get tournaments with registration info
        $tournaments = $query->with(['registeredUsers' => function($q) use ($user) {
            $q->where('player_id', $user->id);
        }])->paginate(10);
        
        // Add registration status for each tournament
        $tournaments->getCollection()->transform(function($tournament) use ($user) {
            $registration = $tournament->registeredUsers->first();
            $tournament->is_registered = !is_null($registration);
            $tournament->registration_status = $registration->status ?? null;
            $tournament->payment_status = $registration->payment_status ?? null;
            unset($tournament->registeredUsers);
            return $tournament;
        });
        
        return response()->json($tournaments);
    }

    /**
     * Register for a tournament
     */
    public function register(Request $request, $tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $user = Auth::user();
        
        // Check if registration is still open
        if ($tournament->registration_deadline && $tournament->registration_deadline < now()) {
            return response()->json(['error' => 'Registration deadline has passed'], 400);
        }
        
        // Check if already registered
        if ($tournament->registeredUsers()->where('player_id', $user->id)->exists()) {
            return response()->json(['error' => 'Already registered for this tournament'], 400);
        }
        
        // Check eligibility based on area scope
        if (!$this->checkEligibility($user, $tournament)) {
            return response()->json(['error' => 'Not eligible for this tournament'], 403);
        }
        
        DB::beginTransaction();
        try {
            // Create registration
            $tournament->registeredUsers()->attach($user->id, [
                'status' => 'pending',
                'payment_status' => $tournament->tournament_charge > 0 ? 'pending' : 'free',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Process payment if required
            if ($tournament->tournament_charge > 0) {
                $paymentIntent = $this->processPayment($user, $tournament);
                
                // Update registration with payment intent
                $tournament->registeredUsers()->updateExistingPivot($user->id, [
                    'payment_intent_id' => $paymentIntent->id
                ]);
                
                DB::commit();
                
                return response()->json([
                    'message' => 'Registration initiated. Complete payment to confirm.',
                    'payment_intent' => $paymentIntent->client_secret,
                    'tournament' => $tournament
                ]);
            } else {
                // Free tournament - auto approve
                $tournament->registeredUsers()->updateExistingPivot($user->id, [
                    'status' => 'approved',
                    'payment_status' => 'free'
                ]);
                
                DB::commit();
                
                // Send confirmation notification
                Notification::create([
                    'player_id' => $user->id,
                    'type' => 'admin_message',
                    'message' => "Successfully registered for {$tournament->name}",
                    'data' => ['tournament_id' => $tournamentId]
                ]);
                
                return response()->json([
                    'message' => 'Successfully registered for tournament',
                    'tournament' => $tournament
                ]);
            }
        } catch (IncompletePayment $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Payment requires additional action',
                'payment_intent' => $e->payment->asStripePaymentIntent()->client_secret
            ], 402);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirm payment for tournament registration
     */
    public function confirmPayment(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string'
        ]);
        
        $tournament = Tournament::findOrFail($tournamentId);
        $user = Auth::user();
        
        // Get registration
        $registration = $tournament->registeredUsers()
            ->where('player_id', $user->id)
            ->where('payment_intent_id', $validated['payment_intent_id'])
            ->first();
        
        if (!$registration) {
        }
        
        // Verify payment with Stripe
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($validated['payment_intent_id']);
                        if ($paymentIntent->status === 'succeeded') {
                    // Update registration
                    $tournament->registeredUsers()->updateExistingPivot($user->id, [
                        'status' => 'approved',
                        'payment_status' => 'paid'
                    ]);
                
                // Send confirmation notification
                Notification::create([
                    'player_id' => $user->id,
                    'type' => 'admin_message',
                    'message' => "Payment confirmed. Successfully registered for {$tournament->name}",
                    'data' => ['tournament_id' => $tournamentId]
                ]);
                
                return response()->json([
{{ ... }}
                    'message' => 'Payment confirmed. Registration complete.',
                    'tournament' => $tournament
                ]);
            } else {
                return response()->json(['error' => 'Payment not completed'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment verification failed'], 500);
        }
    }

    /**
     * Cancel tournament registration
     */
    public function cancel($tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $user = Auth::user();
        
        // Check if registered
        $registration = $tournament->registeredUsers()
            ->where('player_id', $user->id)
            ->first();
        
        if (!$registration) {
            return response()->json(['error' => 'Not registered for this tournament'], 404);
        }
        
        // Check if tournament has started
        if ($tournament->status !== 'upcoming') {
            return response()->json(['error' => 'Cannot cancel after tournament has started'], 400);
        }
        
        DB::beginTransaction();
        try {
            // Process refund if payment was made
            if ($registration->pivot->payment_status === 'paid' && $registration->pivot->payment_intent_id) {
                $this->processRefund($registration->pivot->payment_intent_id);
            }
            
            // Remove registration
            $tournament->registeredUsers()->detach($user->id);
            
            DB::commit();
            
            // Send notification
            Notification::create([
                'player_id' => $user->id,
                'type' => 'registration',
                'message' => "Registration cancelled for {$tournament->name}",
                'data' => ['tournament_id' => $tournamentId]
            ]);
            
            return response()->json([
                'message' => 'Registration cancelled successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to cancel registration'], 500);
        }
    }

    /**
     * Get user's tournament registrations
     */
    public function myRegistrations()
    {
        $user = Auth::user();
        
        $registrations = $user->registeredTournaments()
            ->withPivot('status', 'payment_status')
            ->orderBy('start_date', 'desc')
            ->paginate(10);
        
        return response()->json($registrations);
    }

    /**
     * Check eligibility based on area scope
     */
    private function checkEligibility(User $user, Tournament $tournament)
    {
        if ($tournament->special) {
            return true; // Special tournaments are open to all
        }
        
        if (!$tournament->area_scope) {
            return true; // No area restriction
        }
        
        switch ($tournament->area_scope) {
            case 'community':
                return $user->community->name === $tournament->area_name;
            case 'county':
                return $user->county->name === $tournament->area_name;
            case 'region':
                return $user->region->name === $tournament->area_name;
            case 'national':
                return true; // National is open to all
            default:
                return false;
        }
    }

    /**
     * Process payment for tournament
     */
    private function processPayment(User $user, Tournament $tournament)
    {
        // Ensure user has Stripe customer ID
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        // Create payment intent
        return $user->pay($tournament->tournament_charge * 100, [
            'metadata' => [
                'tournament_id' => $tournament->id,
                'player_id' => $user->id
            ]
        ])->asStripePaymentIntent();
    }

    /**
     * Initialize TinyPesa payment for tournament registration
     */
    public function initializePayment(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|regex:/^0[0-9]{9}$/'
        ]);

        $tournament = Tournament::findOrFail($tournamentId);
        $user = Auth::user();

        // Check if tournament requires payment
        if ($tournament->tournament_charge <= 0) {
            return response()->json(['error' => 'This tournament is free'], 400);
        }

        // Check if already registered
        if ($tournament->registeredUsers()->where('player_id', $user->id)->exists()) {
            return response()->json(['error' => 'Already registered for this tournament'], 400);
        }

        $tinyPesaService = new TinyPesaService();
        $result = $tinyPesaService->initializeTransaction(
            $user->id,
            $tournament->id,
            $tournament->tournament_charge,
            $validated['phone_number']
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'request_id' => $result['request_id'],
                'transaction_id' => $result['transaction_id']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    /**
     * Test TinyPesa service (for debugging)
     */
    public function testTinyPesa(Request $request)
    {
        $user = Auth::user();
        
        Log::info('TinyPesa test endpoint called', [
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

        // Test with dummy data
        $tinyPesaService = new TinyPesaService();
        $result = $tinyPesaService->initializeTransaction(
            $user->id,
            999, // dummy tournament ID
            100.0, // dummy amount
            '0712345678' // dummy phone
        );

        return response()->json([
            'test_result' => $result,
            'user_id' => $user->id,
            'timestamp' => now()
        ]);
    }

    /**
     * Check TinyPesa payment status
     */
    public function checkPaymentStatus(Request $request, $tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $user = Auth::user();

        Log::info('Checking payment status', [
            'user_id' => $user->id,
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name
        ]);

        $tinyPesaService = new TinyPesaService();
        $result = $tinyPesaService->checkTransactionStatus($user->id, $tournament->id);

        Log::info('Payment status check result', [
            'user_id' => $user->id,
            'tournament_id' => $tournament->id,
            'result' => $result
        ]);

        if ($result['success'] && $result['is_complete']) {
            if ($result['is_successful']) {
                // Payment successful - complete registration
                Log::info('Payment successful, processing registration', [
                    'user_id' => $user->id,
                    'tournament_id' => $tournament->id
                ]);

                DB::beginTransaction();
                try {
                    $registrationExists = $tournament->registeredUsers()->where('player_id', $user->id)->exists();
                    
                    Log::info('Registration status', [
                        'user_id' => $user->id,
                        'tournament_id' => $tournament->id,
                        'registration_exists' => $registrationExists
                    ]);

                    if (!$registrationExists) {
                        // Create new registration
                        $tournament->registeredUsers()->attach($user->id, [
                            'status' => 'approved',
                            'payment_status' => 'paid',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        Log::info('New registration created', [
                            'user_id' => $user->id,
                            'tournament_id' => $tournament->id
                        ]);
                    } else {
                        // Update existing registration
                        $updated = $tournament->registeredUsers()->updateExistingPivot($user->id, [
                            'status' => 'approved',
                            'payment_status' => 'paid',
                            'updated_at' => now()
                        ]);
                        
                        Log::info('Registration updated', [
                            'user_id' => $user->id,
                            'tournament_id' => $tournament->id,
                            'update_result' => $updated
                        ]);
                    }

                    // Explicitly commit the transaction BEFORE sending notifications
                    DB::commit();
                    Log::info('Database transaction committed successfully (before notification)', [
                        'user_id' => $user->id,
                        'tournament_id' => $tournament->id
                    ]);

                    // Send confirmation notification (non-blocking for DB persistence)
                    try {
                        $notification = Notification::create([
                            'player_id' => $user->id,
                            'type' => 'admin_message',
                            'message' => "Payment confirmed. Successfully registered for {$tournament->name}",
                            'data' => ['tournament_id' => $tournamentId]
                        ]);

                        Log::info('Notification created', [
                            'user_id' => $user->id,
                            'tournament_id' => $tournament->id,
                            'notification_id' => $notification->id
                        ]);
                    } catch (\Exception $notifyEx) {
                        Log::error('Notification creation failed post-commit', [
                            'user_id' => $user->id,
                            'tournament_id' => $tournament->id,
                            'error' => $notifyEx->getMessage()
                        ]);
                        // Do not rethrow; DB changes are already committed
                    }

                    // Verify the registration was saved
                    $finalRegistration = $tournament->registeredUsers()->where('player_id', $user->id)->first();
                    
                    Log::info('Final registration verification', [
                        'user_id' => $user->id,
                        'tournament_id' => $tournament->id,
                        'registration_found' => $finalRegistration ? 'yes' : 'no',
                        'payment_status' => $finalRegistration ? $finalRegistration->pivot->payment_status : 'N/A',
                        'status' => $finalRegistration ? $finalRegistration->pivot->status : 'N/A'
                    ]);

                    return response()->json([
                        'success' => true,
                        'is_complete' => true,
                        'is_successful' => true,
                        'message' => 'Payment successful! Registration complete.',
                        'tournament' => $tournament,
                        'registration_verified' => $finalRegistration ? true : false
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Log::error('Registration failed after payment', [
                        'user_id' => $user->id,
                        'tournament_id' => $tournament->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Registration failed after payment: ' . $e->getMessage()
                    ], 500);
                }
            } else {
                // Payment failed
                Log::info('Payment failed', [
                    'user_id' => $user->id,
                    'tournament_id' => $tournament->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'is_complete' => true,
                    'is_successful' => false,
                    'message' => 'Payment failed. Please try again.'
                ]);
            }
        }

        Log::info('Payment not complete or unsuccessful', [
            'user_id' => $user->id,
            'tournament_id' => $tournament->id,
            'result' => $result
        ]);

        return response()->json($result);
    }

    /**
     * Process refund
     */
    private function processRefund($paymentIntentId)
    {
        try {
            \Stripe\Refund::create([
                'payment_intent' => $paymentIntentId,
                'reason' => 'requested_by_customer'
            ]);
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }
}
