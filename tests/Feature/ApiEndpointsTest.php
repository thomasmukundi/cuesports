<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\PoolMatch;
use App\Models\MatchMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set JWT secret for testing
        config(['jwt.secret' => 'UVJLlM21V6DhoARuKKSRzngpS8RGQMzBrR63FhDIsVgEUssmPm4znfJJ5sz4Doyf']);
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        
        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function test_user_can_login_and_get_token()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'token',
                    'user' => ['id', 'name', 'email']
                ]);
    }

    /** @test */
    public function test_user_can_update_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/user/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '+254712345678',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '+254712345678',
        ]);
    }

    /** @test */
    public function test_user_can_register_for_tournament()
    {
        $tournament = Tournament::factory()->create([
            'status' => 'open',
            'registration_deadline' => now()->addDays(30),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/tournaments/{$tournament->id}/register");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully registered for tournament'
                ]);

        $this->assertDatabaseHas('tournament_registrations', [
            'tournament_id' => $tournament->id,
            'player_id' => $this->user->id,
            'payment_status' => 'pending',
        ]);
    }

    /** @test */
    public function test_user_cannot_register_twice_for_same_tournament()
    {
        $tournament = Tournament::factory()->create([
            'status' => 'open',
            'registration_deadline' => now()->addDays(30),
        ]);

        // First registration
        TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'player_id' => $this->user->id,
            'registration_date' => now(),
            'payment_status' => 'pending',
        ]);

        // Second registration attempt
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/tournaments/{$tournament->id}/register");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => false,
                    'message' => 'Already registered for this tournament',
                    'already_registered' => true
                ]);
    }

    /** @test */
    public function test_user_can_send_match_message()
    {
        $opponent = User::factory()->create();
        $tournament = Tournament::factory()->create();
        
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/matches/{$match->id}/messages", [
            'message' => 'Hello, looking forward to our match!'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Message sent successfully'
                ]);

        $this->assertDatabaseHas('match_messages', [
            'match_id' => $match->id,
            'sender_id' => $this->user->id,
            'message' => 'Hello, looking forward to our match!'
        ]);
    }

    /** @test */
    public function test_user_can_get_match_messages()
    {
        $opponent = User::factory()->create();
        $tournament = Tournament::factory()->create();
        
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
        ]);

        // Create some messages
        MatchMessage::create([
            'match_id' => $match->id,
            'sender_id' => $this->user->id,
            'message' => 'First message'
        ]);

        MatchMessage::create([
            'match_id' => $match->id,
            'sender_id' => $opponent->id,
            'message' => 'Second message'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/matches/{$match->id}/messages");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_user_can_select_match_dates()
    {
        $opponent = User::factory()->create();
        $tournament = Tournament::factory()->create();
        
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/matches/{$match->id}/select-dates", [
            'selected_dates' => ['2024-12-15', '2024-12-16', '2024-12-17']
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    /** @test */
    public function test_unauthorized_requests_return_401()
    {
        $response = $this->putJson('/api/user/profile', [
            'first_name' => 'Test'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_invalid_token_returns_401()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->putJson('/api/user/profile', [
            'first_name' => 'Test'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_user_can_get_tournaments()
    {
        Tournament::factory()->count(3)->create(['status' => 'open']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/tournaments');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_get_their_matches()
    {
        $opponent = User::factory()->create();
        $tournament = Tournament::factory()->create();
        
        PoolMatch::factory()->count(2)->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/matches?user_matches=true');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonCount(2, 'data');
    }
}
