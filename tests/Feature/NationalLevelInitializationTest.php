<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use App\Models\PoolMatch;
use App\Models\Winner;
use App\Services\MatchAlgorithmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class NationalLevelInitializationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tournament;
    protected $regions = [];
    protected $matchService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->matchService = app(MatchAlgorithmService::class);
        
        // Create multiple regions
        for ($i = 1; $i <= 3; $i++) {
            $this->regions[] = Region::create(['name' => "Test Region {$i}"]);
        }
        
        // Create admin
        $county = County::create([
            'name' => 'Admin County',
            'region_id' => $this->regions[0]->id
        ]);
        
        $community = Community::create([
            'name' => 'Admin Community',
            'county_id' => $county->id,
            'region_id' => $this->regions[0]->id
        ]);
        
        $this->admin = User::factory()->create([
            'email' => 'test-admin@cuesports.com',
            'community_id' => $community->id,
            'county_id' => $county->id,
            'region_id' => $this->regions[0]->id,
        ]);
        
        // Create tournament
        $this->tournament = Tournament::create([
            'name' => 'National Test Tournament',
            'special' => false,
            'tournament_charge' => 100,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(30),
            'status' => 'ongoing',
            'automation_mode' => 'automatic'
        ]);
    }

    protected function setupRegionalWinners()
    {
        // Create winners for each region
        foreach ($this->regions as $index => $region) {
            $county = County::create([
                'name' => "Region{$index} County",
                'region_id' => $region->id
            ]);
            
            $community = Community::create([
                'name' => "Region{$index} Community",
                'county_id' => $county->id,
                'region_id' => $region->id
            ]);
            
            // Create 3 winners per region
            for ($pos = 1; $pos <= 3; $pos++) {
                $winner = User::factory()->create([
                    'email' => "winner{$pos}_region{$index}@test.com",
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $region->id,
                ]);
                
                Winner::create([
                    'user_id' => $winner->id,
                    'tournament_id' => $this->tournament->id,
                    'level' => 'regional',
                    'position' => $pos,
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $region->id,
                    'prize_amount' => 0
                ]);
            }
        }
    }

    public function test_national_initialization_from_regional_winners()
    {
        // Setup regional winners (3 regions x 3 positions = 9 winners)
        $this->setupRegionalWinners();
        
        // Initialize national level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        $response->assertStatus(200);
        
        // Check that matches were created at national level
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        // With 9 winners (odd), should have 5 matches
        $this->assertEquals(5, $matches->count());
        
        // Verify all matches are in round 1 at national level
        foreach ($matches as $match) {
            $this->assertEquals(1, $match->round);
            $this->assertEquals('national', $match->level);
            $this->assertEquals('pending', $match->status);
            $this->assertNull($match->community_id);
            $this->assertNull($match->county_id);
            $this->assertNull($match->region_id);
        }
    }

    public function test_national_pairing_avoids_same_region()
    {
        $this->setupRegionalWinners();
        
        // Initialize national level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        // Get matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        // Check that players from same region are not paired unless necessary
        $sameRegionPairings = 0;
        foreach ($matches as $match) {
            if ($match->player_2_id) { // Not a bye
                $player1 = User::find($match->player_1_id);
                $player2 = User::find($match->player_2_id);
                
                if ($player1->region_id == $player2->region_id) {
                    $sameRegionPairings++;
                }
            }
        }
        
        // Should minimize same region pairings
        $this->assertLessThanOrEqual(2, $sameRegionPairings);
    }

    public function test_national_winner_determination()
    {
        // Create 2 regional winners for simplicity
        $regions = array_slice($this->regions, 0, 2);
        
        $players = [];
        foreach ($regions as $index => $region) {
            $county = County::create([
                'name' => "Region{$index} County",
                'region_id' => $region->id
            ]);
            
            $community = Community::create([
                'name' => "Region{$index} Community",
                'county_id' => $county->id,
                'region_id' => $region->id
            ]);
            
            $winner = User::factory()->create([
                'email' => "national_player{$index}@test.com",
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
            ]);
            $players[] = $winner;
            
            Winner::create([
                'user_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'regional',
                'position' => 1,
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
                'prize_amount' => 0
            ]);
        }
        
        // Initialize national level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        // Complete the national final
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->first();
        
        $nationalChampion = $players[0];
        $runnerUp = $players[1];
        
        $match->update([
            'status' => 'completed',
            'winner_id' => $nationalChampion->id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Check tournament completion
        $checkJob = new \App\Jobs\CheckTournamentCompletion($this->tournament);
        $checkJob->handle();
        
        // Verify national winners
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        $this->assertEquals(2, $winners->count());
        
        $champion = $winners->where('position', 1)->first();
        $this->assertEquals($nationalChampion->id, $champion->player_id);
        
        $second = $winners->where('position', 2)->first();
        $this->assertEquals($runnerUp->id, $second->player_id);
        
        // Verify tournament is marked as completed
        $this->tournament->refresh();
        $this->assertEquals('completed', $this->tournament->status);
    }

    public function test_national_match_naming_convention()
    {
        $this->setupRegionalWinners();
        
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        foreach ($matches as $match) {
            $this->assertStringContainsString('national', $match->match_name);
            $this->assertStringContainsString('R1', $match->match_name);
        }
    }

    public function test_cannot_initialize_national_without_regional_winners()
    {
        // Try to initialize national level without any regional winners
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'No winners found from previous level'
        ]);
    }

    public function test_national_final_with_four_players()
    {
        // Create 4 regional winners
        for ($i = 0; $i < 4; $i++) {
            $regionIndex = $i < 3 ? $i : 0; // Use first region for 4th player
            $region = $this->regions[$regionIndex];
            
            $county = County::create([
                'name' => "County{$i}",
                'region_id' => $region->id
            ]);
            
            $community = Community::create([
                'name' => "Community{$i}",
                'county_id' => $county->id,
                'region_id' => $region->id
            ]);
            
            $winner = User::factory()->create([
                'email' => "finalist{$i}@test.com",
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
            ]);
            
            Winner::create([
                'user_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'regional',
                'position' => ($i % 3) + 1,
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
                'prize_amount' => 0
            ]);
        }
        
        // Initialize national level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        // Round 1: Should have 2 matches
        $round1Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->where('round', 1)
            ->get();
        
        $this->assertEquals(2, $round1Matches->count());
        
        // Complete round 1
        foreach ($round1Matches as $match) {
            $match->update([
                'status' => 'completed',
                'winner_id' => $match->player_1_id,
                'player_1_points' => 5,
                'player_2_points' => 3
            ]);
        }
        
        // Generate round 2 (Winners_SF and Losers_SF)
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/generate-next-round", [
            'level' => 'national'
        ]);
        
        $round2Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->where('round', 2)
            ->get();
        
        $this->assertEquals(2, $round2Matches->count());
        
        // Check for Winners_SF and Losers_SF matches
        $winnersSF = $round2Matches->first(fn($m) => str_contains($m->match_name, 'Winners_SF'));
        $losersSF = $round2Matches->first(fn($m) => str_contains($m->match_name, 'Losers_SF'));
        
        $this->assertNotNull($winnersSF);
        $this->assertNotNull($losersSF);
    }

    public function test_prize_distribution_at_national_level()
    {
        // Create 3 regional winners
        $players = [];
        foreach ($this->regions as $index => $region) {
            $county = County::create([
                'name' => "Region{$index} County",
                'region_id' => $region->id
            ]);
            
            $community = Community::create([
                'name' => "Region{$index} Community",
                'county_id' => $county->id,
                'region_id' => $region->id
            ]);
            
            $winner = User::factory()->create([
                'email' => "prize_winner{$index}@test.com",
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
                'total_points' => 1000 // Initial balance
            ]);
            $players[] = $winner;
            
            Winner::create([
                'user_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'regional',
                'position' => 1,
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
                'prize_amount' => 0
            ]);
        }
        
        // Initialize and complete national tournament
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        // Complete matches to determine positions
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->where('round', 1)
            ->get();
        
        // First match - player 0 wins
        $matches[0]->update([
            'status' => 'completed',
            'winner_id' => $players[0]->id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Second match (bye match) - already completed
        
        // Generate and complete final rounds...
        // For simplicity, we'll manually set the final winners
        Winner::create([
            'user_id' => $players[0]->id,
            'tournament_id' => $this->tournament->id,
            'level' => 'national',
            'position' => 1,
            'prize_amount' => 10000
        ]);
        
        Winner::create([
            'user_id' => $players[1]->id,
            'tournament_id' => $this->tournament->id,
            'level' => 'national',
            'position' => 2,
            'prize_amount' => 5000
        ]);
        
        Winner::create([
            'user_id' => $players[2]->id,
            'tournament_id' => $this->tournament->id,
            'level' => 'national',
            'position' => 3,
            'prize_amount' => 3000
        ]);
        
        // Mark tournament as completed
        $this->tournament->update(['status' => 'completed']);
        
        // Award prizes
        $checkJob = new \App\Jobs\CheckTournamentCompletion($this->tournament);
        $checkJob->handle();
        
        // Verify prize distribution
        $players[0]->refresh();
        $players[1]->refresh();
        $players[2]->refresh();
        
        $this->assertEquals(11000, $players[0]->total_points); // 1000 + 10000
        $this->assertEquals(6000, $players[1]->total_points);  // 1000 + 5000
        $this->assertEquals(4000, $players[2]->total_points);  // 1000 + 3000
    }

    public function test_full_tournament_flow_to_national_champion()
    {
        // Setup minimal tournament with 2 regional winners
        $players = [];
        for ($i = 0; $i < 2; $i++) {
            $region = $this->regions[$i];
            $county = County::create([
                'name' => "Region{$i} County",
                'region_id' => $region->id
            ]);
            
            $community = Community::create([
                'name' => "Region{$i} Community",
                'county_id' => $county->id,
                'region_id' => $region->id
            ]);
            
            $winner = User::factory()->create([
                'email' => "champion_candidate{$i}@test.com",
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
            ]);
            $players[] = $winner;
            
            Winner::create([
                'user_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'regional',
                'position' => 1,
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id,
                'prize_amount' => 0
            ]);
        }
        
        // Initialize national level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        $response->assertStatus(200);
        
        // Get the national final match
        $finalMatch = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->first();
        
        $this->assertStringContainsString('2_player_final', $finalMatch->match_name);
        
        // Complete the final
        $finalMatch->update([
            'match_date' => now(),
            'date_confirmed' => true,
            'status' => 'completed',
            'winner_id' => $players[0]->id,
            'player_1_points' => 5,
            'player_2_points' => 4,
            'confirmed_at' => now()
        ]);
        
        // Check tournament completion
        $checkJob = new \App\Jobs\CheckTournamentCompletion($this->tournament);
        $checkJob->handle();
        
        // Verify we have a national champion
        $nationalChampion = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->where('position', 1)
            ->first();
        
        $this->assertNotNull($nationalChampion);
        $this->assertEquals($players[0]->id, $nationalChampion->player_id);
        
        // Verify tournament is completed
        $this->tournament->refresh();
        $this->assertEquals('completed', $this->tournament->status);
    }
}
