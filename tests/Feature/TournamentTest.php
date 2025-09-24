<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $player;
    protected $region;
    protected $county;
    protected $community;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->region = Region::create(['name' => 'Test Region']);
        $this->county = County::create([
            'name' => 'Test County',
            'region_id' => $this->region->id
        ]);
        $this->community = Community::create([
            'name' => 'Test Community',
            'county_id' => $this->county->id,
            'region_id' => $this->region->id
        ]);
        
        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'test-admin@cuesports.com',
            'community_id' => $this->community->id,
            'county_id' => $this->county->id,
            'region_id' => $this->region->id,
        ]);
        
        // Create regular player
        $this->player = User::factory()->create([
            'community_id' => $this->community->id,
            'county_id' => $this->county->id,
            'region_id' => $this->region->id,
        ]);
    }

    public function test_admin_can_create_tournament()
    {
        Sanctum::actingAs($this->admin);
        
        $tournamentData = [
            'name' => 'Test Tournament',
            'special' => false,
            'community_prize' => 1000,
            'county_prize' => 2000,
            'regional_prize' => 3000,
            'national_prize' => 5000,
            'tournament_charge' => 50,
            'automation_mode' => 'manual'
        ];
        
        $response = $this->postJson('/api/admin/tournaments', $tournamentData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'tournament' => ['id', 'name', 'special', 'tournament_charge']
            ]);
        
        $this->assertDatabaseHas('tournaments', [
            'name' => 'Test Tournament',
            'tournament_charge' => 50
        ]);
    }

    public function test_player_cannot_create_tournament()
    {
        Sanctum::actingAs($this->player);
        
        $tournamentData = [
            'name' => 'Test Tournament',
            'tournament_charge' => 50,
            'automation_mode' => 'manual'
        ];
        
        $response = $this->postJson('/api/admin/tournaments', $tournamentData);
        
        $response->assertStatus(403);
    }

    public function test_player_can_register_for_tournament()
    {
        Sanctum::actingAs($this->player);
        
        $tournament = Tournament::create([
            'name' => 'Free Tournament',
            'special' => false,
            'tournament_charge' => 0,
            'status' => 'upcoming',
            'automation_mode' => 'manual'
        ]);
        
        $response = $this->postJson("/api/tournaments/{$tournament->id}/register");
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully registered for tournament'
            ]);
        
        $this->assertDatabaseHas('registered_users', [
            'player_id' => $this->player->id,
            'tournament_id' => $tournament->id,
            'status' => 'approved',
            'payment_status' => 'free'
        ]);
    }

    public function test_cannot_register_twice_for_same_tournament()
    {
        Sanctum::actingAs($this->player);
        
        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'tournament_charge' => 0,
            'status' => 'upcoming',
            'automation_mode' => 'manual'
        ]);
        
        // First registration
        $this->postJson("/api/tournaments/{$tournament->id}/register");
        
        // Second registration attempt
        $response = $this->postJson("/api/tournaments/{$tournament->id}/register");
        
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Already registered for this tournament'
            ]);
    }

    public function test_admin_can_initialize_tournament()
    {
        Sanctum::actingAs($this->admin);
        
        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'tournament_charge' => 0,
            'status' => 'upcoming',
            'automation_mode' => 'manual'
        ]);
        
        // Register multiple players
        $players = User::factory()->count(4)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->county->id,
            'region_id' => $this->region->id,
        ]);
        
        foreach ($players as $player) {
            $tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $response = $this->postJson("/api/admin/tournaments/{$tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
        
        // Check that matches were created
        $this->assertDatabaseHas('matches', [
            'tournament_id' => $tournament->id,
            'level' => 'community'
        ]);
    }

    public function test_can_get_tournament_statistics()
    {
        Sanctum::actingAs($this->admin);
        
        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'tournament_charge' => 0,
            'status' => 'ongoing',
            'automation_mode' => 'manual'
        ]);
        
        $response = $this->getJson("/api/admin/tournaments/{$tournament->id}/statistics");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_registered',
                'approved_players',
                'total_matches',
                'completed_matches',
                'pending_matches',
                'in_progress_matches',
                'levels'
            ]);
    }
}
