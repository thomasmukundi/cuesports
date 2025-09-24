<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class FlutterApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::create([
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'phone' => '1234567890'
        ]);
        
        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_authentication_register()
    {
        $userData = [
            'first_name' => 'New',
            'last_name' => 'User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '0987654321'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'token'
                ]);
    }

    public function test_authentication_login()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'user',
                    'token'
                ]);
    }

    public function test_tournaments_list_endpoint()
    {
        // Create test tournaments
        Tournament::create([
            'name' => 'Test Tournament 1',
            'description' => 'Test Description',
            'entry_fee' => 1000,
            'max_participants' => 16,
            'status' => 'open',
            'tournament_charge' => 1000
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/tournaments');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'tournaments' => [
                        '*' => [
                            'id',
                            'name',
                            'entry_fee',
                            'max_participants',
                            'current_participants',
                            'status'
                        ]
                    ]
                ]);
    }

    public function test_tournament_registration()
    {
        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'description' => 'Test Description',
            'entry_fee' => 1000,
            'max_participants' => 16,
            'status' => 'open',
            'tournament_charge' => 1000
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/tournaments/{$tournament->id}/register");

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    public function test_user_matches_endpoint()
    {
        // Create a tournament first
        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'description' => 'Test Description',
            'entry_fee' => 1000,
            'max_participants' => 16,
            'status' => 'open',
            'tournament_charge' => 1000
        ]);

        // Create another user for the match
        $opponent = User::create([
            'name' => 'Opponent User',
            'first_name' => 'Opponent',
            'last_name' => 'User',
            'username' => 'opponent',
            'email' => 'opponent@example.com',
            'password' => bcrypt('password123'),
            'phone' => '1111111111'
        ]);

        // Create a match
        PoolMatch::create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
            'status' => 'pending',
            'level' => 'community',
            'round_name' => 'Round 1'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/matches');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'matches' => [
                        '*' => [
                            'id',
                            'opponent_name',
                            'tournament_name',
                            'status',
                            'user_action'
                        ]
                    ]
                ]);
    }

    public function test_user_dashboard_endpoint()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/user/dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'dashboard' => [
                        'recent_matches',
                        'upcoming_matches',
                        'user_stats',
                        'top_shooters'
                    ]
                ]);
    }

    public function test_notifications_endpoint()
    {
        // Create a notification
        Notification::create([
            'player_id' => $this->user->id,
            'type' => 'match',
            'message' => 'Test notification message'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/notifications');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'message',
                            'read_at',
                            'created_at'
                        ]
                    ]
                ]);
    }

    public function test_match_date_selection()
    {
        // Create tournament and opponent
        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'description' => 'Test Description',
            'entry_fee' => 1000,
            'max_participants' => 16,
            'status' => 'open',
            'tournament_charge' => 1000
        ]);

        $opponent = User::create([
            'name' => 'Opponent User 2',
            'first_name' => 'Opponent',
            'last_name' => 'User',
            'username' => 'opponent2',
            'email' => 'opponent2@example.com',
            'password' => bcrypt('password123'),
            'phone' => '2222222222'
        ]);

        $match = PoolMatch::create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
            'status' => 'pending',
            'level' => 'community',
            'round_name' => 'Round 1'
        ]);

        $dates = [
            date('Y-m-d', strtotime('+1 day')),
            date('Y-m-d', strtotime('+2 days')),
            date('Y-m-d', strtotime('+3 days'))
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/matches/{$match->id}/select-dates", [
            'dates' => $dates
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    public function test_jwt_protected_routes()
    {
        // Test without token
        $response = $this->getJson('/api/tournaments');
        $response->assertStatus(401);

        // Test with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/tournaments');
        $response->assertStatus(401);

        // Test with valid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/tournaments');
        $response->assertStatus(200);
    }
}
