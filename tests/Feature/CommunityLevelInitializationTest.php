<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use App\Models\PoolMatch;
use App\Services\MatchAlgorithmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class CommunityLevelInitializationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tournament;
    protected $region;
    protected $county;
    protected $community;
    protected $players = [];
    protected $matchService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->matchService = app(MatchAlgorithmService::class);
        
        // Create geographic structure
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
        
        // Create admin
        $this->admin = User::factory()->create([
            'email' => 'test-admin@cuesports.com',
            'community_id' => $this->community->id,
            'county_id' => $this->county->id,
            'region_id' => $this->region->id,
        ]);
        
        // Create tournament
        $this->tournament = Tournament::create([
            'name' => 'Test Tournament',
            'special' => false,
            'tournament_charge' => 100,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(30),
            'status' => 'upcoming',
            'automation_mode' => 'automatic'
        ]);
    }

    public function test_can_initialize_matches_with_even_number_of_players()
    {
        // Create 8 players and register them
        for ($i = 1; $i <= 8; $i++) {
            $player = User::factory()->create([
                'email' => "player{$i}@test.com",
                'community_id' => $this->community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->players[] = $player;
            
            // Register player for tournament
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
        
        // Initialize matches
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Check that matches were created
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->get();
        
        // With 8 players, should create standard round matches
        $this->assertGreaterThan(0, $matches->count());
        
        // All matches should be pending
        foreach ($matches as $match) {
            $this->assertEquals('pending', $match->status);
            $this->assertNotNull($match->player_1_id);
            $this->assertNotNull($match->player_2_id);
            $this->assertNotEquals($match->player_1_id, $match->player_2_id);
        }
    }

    public function test_can_initialize_matches_with_odd_number_of_players()
    {
        // Create 7 players (odd number)
        for ($i = 1; $i <= 7; $i++) {
            $player = User::factory()->create([
                'email' => "player{$i}@test.com",
                'community_id' => $this->community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->players[] = $player;
            
            // Register player for tournament
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
        
        // Initialize matches
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Check matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->get();
        
        // With 7 players, should create matches
        $this->assertGreaterThan(0, $matches->count());
        
        // Regular matches should be pending, bye matches should be completed
        foreach ($matches as $match) {
            if ($match->player_2_id === null) {
                // Bye match
                $this->assertEquals('completed', $match->status);
                $this->assertNotNull($match->bye_player_id);
            } else {
                // Regular match
                $this->assertEquals('pending', $match->status);
            }
        }
    }

    public function test_can_initialize_with_bye_player()
    {
        // Create 3 players (will result in a bye)
        for ($i = 1; $i <= 3; $i++) {
            $player = User::factory()->create([
                'email' => "player{$i}@test.com",
                'community_id' => $this->community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->players[] = $player;
            
            // Register player for tournament
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
        
        // Initialize matches
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Check matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->get();
        
        // Should have matches created
        $this->assertGreaterThan(0, $matches->count());
        
        // All matches should be pending
        foreach ($matches as $match) {
            $this->assertEquals('pending', $match->status);
        }
    }

    public function test_match_naming_convention()
    {
        // Create 4 players
        for ($i = 1; $i <= 4; $i++) {
            $player = User::factory()->create([
                'email' => "player{$i}@test.com",
                'community_id' => $this->community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->players[] = $player;
            
            // Register player for tournament
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
        
        // Initialize matches
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Check match names
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->orderBy('match_name')
            ->get();
        
        // Should have 2 matches for 4 players
        $this->assertEquals(2, $matches->count());
        
        // Check match names follow the 4-player pattern
        $matchNames = $matches->pluck('match_name')->toArray();
        $this->assertContains('4_player_match1', $matchNames);
        $this->assertContains('4_player_match2', $matchNames);
        
        foreach ($matches as $match) {
            $this->assertEquals('quarter_final', $match->round_name);
        }
    }

    public function test_cannot_initialize_without_registered_players()
    {
        // Try to initialize without any registered players
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'No eligible players found for community level'
        ]);
    }

    public function test_cannot_initialize_already_initialized_tournament()
    {
        // Create and register 4 players
        for ($i = 1; $i <= 4; $i++) {
            $player = User::factory()->create([
                'email' => "player{$i}@test.com",
                'community_id' => $this->community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
        
        // Initialize once
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        $response->assertStatus(200);
        
        // Update tournament status
        $this->tournament->update(['status' => 'ongoing']);
        
        // Try to initialize again
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Tournament already initialized'
        ]);
    }

    public function test_players_grouped_by_community()
    {
        // Create another community
        $community2 = Community::create([
            'name' => 'Test Community 2',
            'county_id' => $this->county->id,
            'region_id' => $this->region->id
        ]);
        
        // Create 4 players in first community
        for ($i = 1; $i <= 4; $i++) {
            $player = User::factory()->create([
                'email' => "community1_player{$i}@test.com",
                'community_id' => $this->community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
        
        // Create 4 players in second community
        for ($i = 1; $i <= 4; $i++) {
            $player = User::factory()->create([
                'email' => "community2_player{$i}@test.com",
                'community_id' => $community2->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
        
        // Initialize matches
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Check that matches were created for both communities
        $allMatches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->get();
        
        $this->assertGreaterThan(0, $allMatches->count());
    }
}
