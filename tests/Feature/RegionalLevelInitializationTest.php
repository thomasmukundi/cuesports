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

class RegionalLevelInitializationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tournament;
    protected $region;
    protected $counties = [];
    protected $matchService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->matchService = app(MatchAlgorithmService::class);
        
        // Create geographic structure
        $this->region = Region::create(['name' => 'Test Region']);
        
        // Create multiple counties in the region
        for ($i = 1; $i <= 3; $i++) {
            $this->counties[] = County::create([
                'name' => "Test County {$i}",
                'region_id' => $this->region->id
            ]);
        }
        
        // Create admin
        $community = Community::create([
            'name' => 'Admin Community',
            'county_id' => $this->counties[0]->id,
            'region_id' => $this->region->id
        ]);
        
        $this->admin = User::factory()->create([
            'email' => 'test-admin@cuesports.com',
            'community_id' => $community->id,
            'county_id' => $this->counties[0]->id,
            'region_id' => $this->region->id,
        ]);
        
        // Create tournament
        $this->tournament = Tournament::create([
            'name' => 'Test Tournament',
            'special' => false,
            'tournament_charge' => 100,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(30),
            'status' => 'ongoing',
            'automation_mode' => 'automatic'
        ]);
    }

    protected function setupCountyWinners()
    {
        // Create winners for each county
        foreach ($this->counties as $index => $county) {
            // Create a community for this county
            $community = Community::create([
                'name' => "County{$index} Community",
                'county_id' => $county->id,
                'region_id' => $this->region->id
            ]);
            
            // Create 3 winners per county
            for ($pos = 1; $pos <= 3; $pos++) {
                $winner = User::factory()->create([
                    'email' => "winner{$pos}_county{$index}@test.com",
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $this->region->id,
                ]);
                
                Winner::create([
                    'user_id' => $winner->id,
                    'tournament_id' => $this->tournament->id,
                    'level' => 'county',
                    'position' => $pos,
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $this->region->id,
                    'prize_amount' => 0
                ]);
            }
        }
    }

    public function test_regional_initialization_from_county_winners()
    {
        // Setup county winners (3 counties x 3 positions = 9 winners)
        $this->setupCountyWinners();
        
        // Initialize regional level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $response->assertStatus(200);
        
        // Check that matches were created at regional level
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('region_id', $this->region->id)
            ->get();
        
        // With 9 winners (odd), should have 5 matches
        $this->assertEquals(5, $matches->count());
        
        // Verify all matches are in round 1
        foreach ($matches as $match) {
            $this->assertEquals(1, $match->round);
            $this->assertEquals('regional', $match->level);
            $this->assertEquals('pending', $match->status);
        }
    }

    public function test_regional_pairing_avoids_same_county()
    {
        $this->setupCountyWinners();
        
        // Initialize regional level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        // Get matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('region_id', $this->region->id)
            ->get();
        
        // Check that players from same county are not paired unless necessary
        $sameCountyPairings = 0;
        foreach ($matches as $match) {
            if ($match->player_2_id) { // Not a bye
                $player1 = User::find($match->player_1_id);
                $player2 = User::find($match->player_2_id);
                
                if ($player1->county_id == $player2->county_id) {
                    $sameCountyPairings++;
                }
            }
        }
        
        // Should minimize same county pairings
        $this->assertLessThanOrEqual(2, $sameCountyPairings);
    }

    public function test_regional_pairing_with_mixed_positions()
    {
        // Create 2 counties with full winners
        for ($i = 0; $i < 2; $i++) {
            $county = $this->counties[$i];
            $community = Community::create([
                'name' => "County{$i} Community",
                'county_id' => $county->id,
                'region_id' => $this->region->id
            ]);
            
            for ($pos = 1; $pos <= 3; $pos++) {
                $winner = User::factory()->create([
                    'email' => "pos{$pos}_county{$i}@test.com",
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $this->region->id,
                ]);
                
                Winner::create([
                    'user_id' => $winner->id,
                    'tournament_id' => $this->tournament->id,
                    'level' => 'county',
                    'position' => $pos,
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $this->region->id,
                    'prize_amount' => 0
                ]);
            }
        }
        
        // Add one county with only position 1 winner (making odd number)
        $extraCounty = $this->counties[2];
        $extraCommunity = Community::create([
            'name' => "Extra County Community",
            'county_id' => $extraCounty->id,
            'region_id' => $this->region->id
        ]);
        
        $extraWinner = User::factory()->create([
            'email' => "extra_winner@test.com",
            'community_id' => $extraCommunity->id,
            'county_id' => $extraCounty->id,
            'region_id' => $this->region->id,
        ]);
        
        Winner::create([
            'user_id' => $extraWinner->id,
            'tournament_id' => $this->tournament->id,
            'level' => 'county',
            'position' => 1,
            'community_id' => $extraCommunity->id,
            'county_id' => $extraCounty->id,
            'region_id' => $this->region->id,
            'prize_amount' => 0
        ]);
        
        // Initialize regional level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $response->assertStatus(200);
        
        // Check that odd position 1 is paired appropriately
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->get();
        
        $extraWinnerMatch = $matches->first(function ($match) use ($extraWinner) {
            return $match->player_1_id == $extraWinner->id || 
                   $match->player_2_id == $extraWinner->id;
        });
        
        $this->assertNotNull($extraWinnerMatch);
        
        // The opponent should preferably be from a different county
        $opponentId = $extraWinnerMatch->player_1_id == $extraWinner->id 
            ? $extraWinnerMatch->player_2_id 
            : $extraWinnerMatch->player_1_id;
        
        if ($opponentId) {
            $opponent = User::find($opponentId);
            $this->assertNotEquals($extraCounty->id, $opponent->county_id);
        }
    }

    public function test_regional_initialization_with_single_county()
    {
        // Only one county with winners
        $county = $this->counties[0];
        $community = Community::create([
            'name' => 'Single County Community',
            'county_id' => $county->id,
            'region_id' => $this->region->id
        ]);
        
        for ($pos = 1; $pos <= 3; $pos++) {
            $winner = User::factory()->create([
                'email' => "winner{$pos}@test.com",
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $this->region->id,
            ]);
            
            Winner::create([
                'user_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'county',
                'position' => $pos,
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $this->region->id,
                'prize_amount' => 0
            ]);
        }
        
        // Initialize regional level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $response->assertStatus(200);
        
        // With 3 players from single county, should create appropriate matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('region_id', $this->region->id)
            ->get();
        
        // 3 players should result in 2 matches (1 regular + 1 bye)
        $this->assertEquals(2, $matches->count());
    }

    public function test_cannot_initialize_regional_without_county_winners()
    {
        // Try to initialize regional level without any county winners
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'No winners found from previous level'
        ]);
    }

    public function test_regional_match_naming_convention()
    {
        $this->setupCountyWinners();
        
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('region_id', $this->region->id)
            ->get();
        
        foreach ($matches as $match) {
            $this->assertStringContainsString('regional', $match->match_name);
            $this->assertStringContainsString('R1', $match->match_name);
            $this->assertStringContainsString($this->region->name, $match->match_name);
        }
    }

    public function test_multiple_regions_initialize_independently()
    {
        // Create another region
        $region2 = Region::create(['name' => 'Test Region 2']);
        $county2 = County::create([
            'name' => 'Region2 County',
            'region_id' => $region2->id
        ]);
        $community2 = Community::create([
            'name' => 'Region2 Community',
            'county_id' => $county2->id,
            'region_id' => $region2->id
        ]);
        
        // Create winners for region2
        for ($pos = 1; $pos <= 2; $pos++) {
            $winner = User::factory()->create([
                'email' => "region2_winner{$pos}@test.com",
                'community_id' => $community2->id,
                'county_id' => $county2->id,
                'region_id' => $region2->id,
            ]);
            
            Winner::create([
                'user_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'county',
                'position' => $pos,
                'community_id' => $community2->id,
                'county_id' => $county2->id,
                'region_id' => $region2->id,
                'prize_amount' => 0
            ]);
        }
        
        // Setup winners for region1
        $this->setupCountyWinners();
        
        // Initialize both regions
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $response->assertStatus(200);
        
        // Check region1 matches
        $region1Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('region_id', $this->region->id)
            ->get();
        
        $this->assertEquals(5, $region1Matches->count()); // 9 players
        
        // Check region2 matches
        $region2Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('region_id', $region2->id)
            ->get();
        
        $this->assertEquals(1, $region2Matches->count()); // 2 players
        
        // Verify no cross-region matches
        foreach ($region1Matches as $match) {
            if ($match->player_1_id) {
                $player1 = User::find($match->player_1_id);
                $this->assertEquals($this->region->id, $player1->region_id);
            }
            if ($match->player_2_id) {
                $player2 = User::find($match->player_2_id);
                $this->assertEquals($this->region->id, $player2->region_id);
            }
        }
    }

    public function test_regional_progression_to_winners()
    {
        // Create 2 county winners
        $county = $this->counties[0];
        $community = Community::create([
            'name' => 'Test Community',
            'county_id' => $county->id,
            'region_id' => $this->region->id
        ]);
        
        $players = [];
        for ($i = 1; $i <= 2; $i++) {
            $winner = User::factory()->create([
                'email' => "player{$i}@test.com",
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $this->region->id,
            ]);
            $players[] = $winner;
            
            Winner::create([
                'user_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'county',
                'position' => $i,
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $this->region->id,
                'prize_amount' => 0
            ]);
        }
        
        // Initialize regional level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        // Complete the match
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->first();
        
        $match->update([
            'status' => 'completed',
            'winner_id' => $players[0]->id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Check tournament completion
        $checkJob = new \App\Jobs\CheckTournamentCompletion($this->tournament);
        $checkJob->handle();
        
        // Verify regional winners
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('region_id', $this->region->id)
            ->get();
        
        $this->assertEquals(2, $winners->count());
        $this->assertEquals($players[0]->id, $winners->where('position', 1)->first()->player_id);
        $this->assertEquals($players[1]->id, $winners->where('position', 2)->first()->player_id);
    }

    public function test_fairness_in_cross_county_pairing()
    {
        // Create specific winners to test fairness
        foreach ($this->counties as $countyIndex => $county) {
            $community = Community::create([
                'name' => "County{$countyIndex} Community",
                'county_id' => $county->id,
                'region_id' => $this->region->id
            ]);
            
            // Each county has position 1 and position 2 winners
            for ($pos = 1; $pos <= 2; $pos++) {
                $winner = User::factory()->create([
                    'email' => "county{$countyIndex}_pos{$pos}@test.com",
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $this->region->id,
                ]);
                
                Winner::create([
                    'user_id' => $winner->id,
                    'tournament_id' => $this->tournament->id,
                    'level' => 'county',
                    'position' => $pos,
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $this->region->id,
                    'prize_amount' => 0
                ]);
            }
        }
        
        // Initialize regional level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->get();
        
        // Verify position 1 players are not paired with position 2 from same county
        foreach ($matches as $match) {
            if ($match->player_1_id && $match->player_2_id) {
                $player1 = User::find($match->player_1_id);
                $player2 = User::find($match->player_2_id);
                
                $winner1 = Winner::where('user_id', $player1->id)
                    ->where('tournament_id', $this->tournament->id)
                    ->where('level', 'county')
                    ->first();
                    
                $winner2 = Winner::where('user_id', $player2->id)
                    ->where('tournament_id', $this->tournament->id)
                    ->where('level', 'county')
                    ->first();
                
                // If from same county, positions should be similar
                if ($player1->county_id == $player2->county_id) {
                    $this->assertEquals($winner1->position, $winner2->position);
                }
            }
        }
    }
}
