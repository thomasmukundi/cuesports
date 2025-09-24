<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\Notification;
use App\Models\TournamentRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        
        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function test_user_registration()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'county' => 'Nairobi',
            'community' => 'Test Community'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'county',
                        'community'
                    ],
                    'token'
                ]);
    }

    /** @test */
    public function test_user_login()
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

    /** @test */
    public function test_tournaments_list()
    {
        Tournament::factory()->count(3)->create();

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
                            'description',
                            'entry_fee',
                            'max_participants',
                            'current_participants',
                            'status',
                            'start_date',
                            'is_registered'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function test_featured_tournament()
    {
        Tournament::factory()->create(['special' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/tournaments/featured');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'tournament' => [
                        'id',
                        'name',
                        'description',
                        'entry_fee',
                        'max_participants',
                        'current_participants'
                    ]
                ]);
    }

    /** @test */
    public function test_tournament_registration()
    {
        $tournament = Tournament::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/tournaments/{$tournament->id}/register");

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function test_user_matches()
    {
        PoolMatch::factory()->count(2)->create([
            'player_1_id' => $this->user->id
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
                            'scheduled_date',
                            'user_action',
                            'score',
                            'video_url'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function test_match_details()
    {
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/matches/{$match->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'match' => [
                        'id',
                        'player1',
                        'player2',
                        'tournament',
                        'status',
                        'scheduled_date',
                        'user_action'
                    ]
                ]);
    }

    /** @test */
    public function test_select_match_dates()
    {
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id,
            'status' => 'pending'
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

    /** @test */
    public function test_submit_match_results()
    {
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id,
            'status' => 'in_progress'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/matches/{$match->id}/submit-results", [
            'player_1_score' => 3,
            'player_2_score' => 2
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function test_user_notifications()
    {
        Notification::factory()->count(3)->create([
            'player_id' => $this->user->id
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
                            'title',
                            'message',
                            'read_at',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function test_user_dashboard()
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

    /** @test */
    public function test_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'player_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/notifications/{$notification->id}/mark-read");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function test_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create([
            'player_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }
}
