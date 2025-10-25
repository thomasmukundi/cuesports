<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    /**
     * Get all available tournaments
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = Tournament::withCount('registrations');
            
            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Filter for user's tournaments if requested
            if ($request->has('my_tournaments') && $request->my_tournaments) {
                $query->whereHas('registrations', function ($q) use ($user) {
                    $q->where('player_id', $user->id);
                });
            } else {
                // Filter tournaments based on user location and tournament scope
                // Only apply filtering if user has location data, otherwise show all tournaments
                if ($user->community_id || $user->county_id || $user->region_id) {
                    $this->applyLocationFilter($query, $user);
                }
                // If user has no location data, show all tournaments
            }
            
            $tournaments = $query->orderBy('created_at', 'desc')->get();
            
            \Log::info('Tournaments found for user', [
                'user_id' => $user->id,
                'tournament_count' => $tournaments->count(),
                'tournaments' => $tournaments->map(function($t) {
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                        'area_scope' => $t->area_scope,
                        'area_name' => $t->area_name
                    ];
                })
            ]);
        
            $tournaments = $tournaments->map(function ($tournament) use ($user) {
                // Check if user is registered
                $isRegistered = $tournament->registrations()
                    ->where('player_id', $user->id)
                    ->exists();
                
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'description' => $tournament->description,
                    'status' => $tournament->status,
                    'entry_fee' => $tournament->entry_fee,
                    'tournament_charge' => $tournament->tournament_charge,
                    'national_prize' => $tournament->national_prize,
                    'max_participants' => $tournament->max_participants,
                    'current_participants' => $tournament->registrations_count,
                    'start_date' => $tournament->start_date,
                    'end_date' => $tournament->end_date,
                    'registration_deadline' => $tournament->registration_deadline,
                    'is_registered' => $isRegistered,
                    'can_register' => !$isRegistered && 
                                    ($tournament->status === 'upcoming' || $tournament->status === 'active') && 
                                    $tournament->registration_deadline > now(),
                    'created_at' => $tournament->created_at,
                ];
            });
        
            return response()->json([
                'success' => true,
                'data' => $tournaments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tournaments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply location-based filtering for tournaments
     */
    private function applyLocationFilter($query, $user)
    {
        // Get user's location information
        $userCommunity = $user->community_id ? Community::find($user->community_id) : null;
        $userCounty = $user->county_id ? County::find($user->county_id) : null;
        $userRegion = $user->region_id ? Region::find($user->region_id) : null;

        \Log::info('Tournament filtering for user', [
            'user_id' => $user->id,
            'user_community' => $userCommunity ? $userCommunity->name : null,
            'user_county' => $userCounty ? $userCounty->name : null,
            'user_region' => $userRegion ? $userRegion->name : null
        ]);

        $query->where(function ($q) use ($user, $userCommunity, $userCounty, $userRegion) {
            // Show tournaments with no area scope (open to all) or national tournaments
            $q->whereNull('area_scope')
              ->orWhere('area_scope', 'national')
              ->orWhere('area_scope', '');

            // Show community tournaments if user is in that community
            if ($userCommunity) {
                $q->orWhere(function ($subQ) use ($userCommunity) {
                    $subQ->where('area_scope', 'community')
                         ->where('area_name', $userCommunity->name);
                });
            }

            // Show county tournaments if user is in that county
            if ($userCounty) {
                $q->orWhere(function ($subQ) use ($userCounty) {
                    $subQ->where('area_scope', 'county')
                         ->where('area_name', $userCounty->name);
                });
            }

            // Show regional tournaments if user is in that region  
            if ($userRegion) {
                $q->orWhere(function ($subQ) use ($userRegion) {
                    $subQ->where('area_scope', 'regional')
                         ->where('area_name', $userRegion->name);
                });
            }
        });
    }

    /**
     * Get featured tournament
     */
    public function featured()
    {
        $user = auth()->user();
        $query = Tournament::whereIn('status', ['registration', 'upcoming', 'ongoing'])
            ->where('registration_deadline', '>', now());
            
        // Apply location filtering for featured tournaments too (only if user has location data)
        if ($user->community_id || $user->county_id || $user->region_id) {
            $this->applyLocationFilter($query, $user);
        }
        
        $tournament = $query->orderBy('start_date', 'asc')->first();
            
        if (!$tournament) {
            return response()->json([
                'success' => false,
                'message' => 'No featured tournament available'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'description' => $tournament->description,
                'entry_fee' => $tournament->entry_fee,
                'max_participants' => $tournament->max_participants,
                'current_participants' => $tournament->registeredUsers->count(),
                'registration_deadline' => $tournament->registration_deadline,
                'start_date' => $tournament->start_date,
            ]
        ]);
    }
    
    /**
     * Register for a tournament
     */
    public function register(Request $request, Tournament $tournament)
    {
        $user = auth()->user();
        
        // Check if already registered
        $existingRegistration = TournamentRegistration::where('tournament_id', $tournament->id)
            ->where('player_id', $user->id)
            ->first();
            
        if ($existingRegistration) {
            return response()->json([
                'success' => false,
                'message' => 'Already registered for this tournament',
                'already_registered' => true
            ], 200);
        }
        
        // Check if tournament is open for registration (registration, upcoming, active or ongoing)
        if (!in_array($tournament->status, ['registration', 'upcoming', 'active', 'ongoing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament is not open for registration'
            ], 400);
        }
        
        // Check if registration deadline has passed
        if ($tournament->registration_deadline < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration deadline has passed'
            ], 400);
        }
        
        // Create registration (bypassing payment for now)
        TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'player_id' => $user->id,
            'registration_date' => now(),
            'payment_status' => 'completed' // Auto-approve registration without payment processing
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully registered for tournament'
        ]);
    }
    
    /**
     * Get user's tournament registrations
     */
    public function myRegistrations()
    {
        $user = auth()->user();
        
        $registrations = TournamentRegistration::with('tournament')
            ->where('player_id', $user->id)
            ->orderBy('registration_date', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $registrations->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'tournament_id' => $registration->tournament->id,
                    'tournament' => [
                        'id' => $registration->tournament->id,
                        'name' => $registration->tournament->name,
                        'status' => $registration->tournament->status,
                        'start_date' => $registration->tournament->start_date,
                        'entry_fee' => $registration->tournament->entry_fee,
                    ],
                    'registration_date' => $registration->registration_date,
                    'payment_status' => $registration->payment_status,
                ];
            })
        ]);
    }
}
