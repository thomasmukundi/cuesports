<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\PoolMatch;
use App\Models\MatchMessage;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MobileAppEndpointsTest extends TestCase
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
            'name' => 'Test User',
        ]);
        
        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function test_home_dashboard_returns_user_data_and_stats()
    {
        // Create some test matches
        $tournament = Tournament::factory()->create();
        $opponent = User::factory()->create();
        
        // Create completed matches
        $match1 = PoolMatch::factory()->completed()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
            'winner_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'dashboard' => [
                    'user_stats' => [
                        'total_matches',
                        'won_matches',
                        'win_rate',
                    ],
                    'recent_matches',
                    'upcoming_matches',
                    'top_shooters',
                ]
            ]);
    }

    /** @test */
    public function test_tournaments_list_with_registration_status()
    {
        $tournament1 = Tournament::factory()->create(['status' => 'open']);
        $tournament2 = Tournament::factory()->create(['status' => 'open']);
        
        // Register for one tournament using the correct table
        \DB::table('tournament_registrations')->insert([
            'tournament_id' => $tournament1->id,
            'player_id' => $this->user->id,
            'registration_date' => now(),
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/tournaments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'entry_fee',
                        'start_date',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_my_tournaments_returns_registered_tournaments()
    {
        $tournament1 = Tournament::factory()->create();
        $tournament2 = Tournament::factory()->create();
        
        // Register for one tournament using the correct table
        \DB::table('tournament_registrations')->insert([
            'tournament_id' => $tournament1->id,
            'player_id' => $this->user->id,
            'registration_date' => now(),
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/tournaments/my-registrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'registrations' => [
                    '*' => [
                        'id',
                        'tournament' => [
                            'id',
                            'name',
                            'status',
                        ],
                        'registration_date',
                        'payment_status',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_my_matches_returns_user_matches()
    {
        $tournament = Tournament::factory()->create();
        $opponent = User::factory()->create();
        
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/matches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'tournament',
                        'opponent',
                        'status',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_top_shooters_returns_users_with_points()
    {
        // Create users with different win counts
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Create matches to give users wins
        PoolMatch::factory()->create(['winner_id' => $user1->id, 'status' => 'completed']);
        PoolMatch::factory()->create(['winner_id' => $user1->id, 'status' => 'completed']);
        PoolMatch::factory()->create(['winner_id' => $user2->id, 'status' => 'completed']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'points',
                        'wins',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_regions_endpoint_returns_all_regions()
    {
        $response = $this->getJson('/api/regions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_counties_by_region_returns_filtered_counties()
    {
        $response = $this->getJson('/api/counties?region_id=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'region_id',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_communities_by_county_returns_filtered_communities()
    {
        $response = $this->getJson('/api/communities?county_id=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'county_id',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_notifications_endpoint_works()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
            ]);
    }

    /** @test */
    public function test_profile_update_works_correctly()
    {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '+254712345678',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/user/profile', $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'user',
            ]);

        // Verify the user was actually updated
        $this->user->refresh();
        $this->assertEquals('Updated', $this->user->first_name);
        $this->assertEquals('Name', $this->user->last_name);
    }

    /** @test */
    public function test_match_chat_messages_work()
    {
        $tournament = Tournament::factory()->create();
        $opponent = User::factory()->create();
        
        $match = PoolMatch::factory()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
        ]);

        // Send a message
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/matches/{$match->id}/messages", [
            'message' => 'Hello opponent!'
        ]);

        $response->assertStatus(200);

        // Get messages
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/matches/{$match->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'messages' => [
                    '*' => [
                        'id',
                        'message',
                        'sender',
                        'created_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_tournament_registration_prevents_duplicate_registration()
    {
        $tournament = Tournament::factory()->create(['status' => 'open']);
        
        // First registration
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/tournaments/{$tournament->id}/register");

        $response->assertStatus(200);

        // Second registration attempt
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/tournaments/{$tournament->id}/register");

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'already_registered' => true,
            ]);
    }

    /** @test */
    public function test_leaderboard_endpoint_returns_past_matches_and_awards()
    {
        // Create some matches for the user
        $tournament = Tournament::factory()->create();
        $opponent = User::factory()->create();
        
        $match1 = PoolMatch::factory()->completed()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
            'winner_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/players/leaderboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'past_matches' => [
                        '*' => [
                            'id',
                            'tournament',
                            'opponent',
                            'result',
                            'date',
                            'player_1_points',
                            'player_2_points'
                        ]
                    ],
                    'awards' => [
                        'total_points',
                        'total_matches',
                        'won_matches',
                        'win_rate',
                        'achievements'
                    ],
                    'user_stats' => [
                        'name',
                        'total_points',
                        'win_rate',
                        'total_matches',
                        'won_matches'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_awards_endpoint_returns_detailed_player_statistics()
    {
        // Create some matches and tournament registrations for comprehensive stats
        $tournament = Tournament::factory()->create();
        $opponent = User::factory()->create();
        
        // Create multiple matches
        PoolMatch::factory()->completed()->create([
            'player_1_id' => $this->user->id,
            'player_2_id' => $opponent->id,
            'tournament_id' => $tournament->id,
            'winner_id' => $this->user->id,
        ]);

        // Create tournament registration
        \DB::table('tournament_registrations')->insert([
            'tournament_id' => $tournament->id,
            'player_id' => $this->user->id,
            'registration_date' => now(),
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/players/awards');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'player_stats' => [
                        'name',
                        'total_points',
                        'total_matches',
                        'won_matches',
                        'lost_matches',
                        'win_rate',
                        'tournament_participations',
                        'tournament_wins',
                        'recent_win_rate',
                        'recent_matches_count'
                    ],
                    'achievements' => [
                        '*' => [
                            'name',
                            'description',
                            'icon'
                        ]
                    ],
                    'performance_summary' => [
                        'rank',
                        'points_to_next_level',
                        'current_streak',
                        'best_streak'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_user_statistics_endpoint_works()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'matches',
                    'tournaments',
                    'recent_performance',
                ]
            ]);
    }
}
