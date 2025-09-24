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

class CountyLevelInitializationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tournament;
    protected $region;
    protected $county;
    protected $communities = [];
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
        
        // Create multiple communities in the county
        for ($i = 1; $i <= 3; $i++) {
            $this->communities[] = Community::create([
                'name' => "Test Community {$i}",
                'county_id' => $this->county->id,
                'region_id' => $this->region->id
            ]);
        }
        
        // Create admin
        $this->admin = User::factory()->create([
            'email' => 'test-admin@cuesports.com',
            'community_id' => $this->communities[0]->id,
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
            'status' => 'ongoing',
            'automation_mode' => 'automatic'
        ]);
    }

    protected function setupCommunityWinners()
    {
        // Create winners for each community
        foreach ($this->communities as $index => $community) {
            // Position 1 winner
            $winner1 = User::factory()->create([
                'email' => "winner1_community{$index}@test.com",
                'community_id' => $community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            
            Winner::create([
                'player_id' => $winner1->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'community',
                'position' => 1,
                'level_id' => $community->id,
                'prize_amount' => 0
            ]);
            
            // Position 2 winner
            $winner2 = User::factory()->create([
                'email' => "winner2_community{$index}@test.com",
                'community_id' => $community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            
            Winner::create([
                'player_id' => $winner2->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'community',
                'position' => 2,
                'level_id' => $community->id,
                'prize_amount' => 0
            ]);
            
            // Position 3 winner
            $winner3 = User::factory()->create([
                'email' => "winner3_community{$index}@test.com",
                'community_id' => $community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            
            Winner::create([
                'player_id' => $winner3->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'community',
                'position' => 3,
                'level_id' => $community->id,
                'prize_amount' => 0
            ]);
        }
    }

    public function test_county_initialization_from_community_winners()
    {
        // Setup community winners (3 communities x 3 positions = 9 winners)
        $this->setupCommunityWinners();
        
        // Initialize county level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $response->assertStatus(200);
        
        // Check that matches were created at county level
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $this->county->id)
            ->get();
        
        // With 9 winners (odd), should have 5 matches
        $this->assertEquals(5, $matches->count());
        
        // Verify all matches are in round 1
        foreach ($matches as $match) {
            $this->assertEquals('round_1', $match->round_name);
            $this->assertEquals('pending', $match->status);
        }
    }

    public function test_county_pairing_prioritizes_different_communities()
    {
        // Setup community winners
        $this->setupCommunityWinners();
        
        // Initialize county level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        // Get matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $this->county->id)
            ->get();
        
        // Check that players from same community are not paired unless necessary
        $sameCommunityPairings = 0;
        foreach ($matches as $match) {
            if ($match->player_2_id) { // Not a bye
                $player1 = User::find($match->player_1_id);
                $player2 = User::find($match->player_2_id);
                
                if ($player1->community_id == $player2->community_id) {
                    $sameCommunityPairings++;
                }
            }
        }
        
        // Should minimize same community pairings
        $this->assertLessThanOrEqual(3, $sameCommunityPairings);
    }

    public function test_county_pairing_with_odd_position_ones()
    {
        // Create 2 communities with winners (6 total winners)
        $communities = array_slice($this->communities, 0, 2);
        
        foreach ($communities as $index => $community) {
            // Only 2 communities, so we have even number of position 1s
            for ($pos = 1; $pos <= 3; $pos++) {
                $winner = User::factory()->create([
                    'email' => "winner{$pos}_community{$index}@test.com",
                    'community_id' => $community->id,
                    'county_id' => $this->county->id,
                    'region_id' => $this->region->id,
                ]);
                
                Winner::create([
                    'player_id' => $winner->id,
                    'tournament_id' => $this->tournament->id,
                    'level' => 'community',
                    'position' => $pos,
                    'community_id' => $community->id,
                    'county_id' => $this->county->id,
                    'region_id' => $this->region->id,
                    'prize_amount' => 0
                ]);
            }
        }
        
        // Add one more community with only position 1 winner (making odd number of pos 1s)
        $extraCommunity = $this->communities[2];
        $extraWinner = User::factory()->create([
            'email' => "extra_winner@test.com",
            'community_id' => $extraCommunity->id,
            'county_id' => $this->county->id,
            'region_id' => $this->region->id,
        ]);
        
        Winner::create([
            'player_id' => $extraWinner->id,
            'tournament_id' => $this->tournament->id,
            'level' => 'community',
            'position' => 1,
            'level_id' => $extraCommunity->id,
            'prize_amount' => 0
        ]);
        
        // Initialize county level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $response->assertStatus(200);
        
        // Check that odd position 1 is paired with a position 2
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $this->county->id)
            ->get();
        
        // Find match with the extra winner
        $extraWinnerMatch = $matches->first(function ($match) use ($extraWinner) {
            return $match->player_1_id == $extraWinner->id || 
                   $match->player_2_id == $extraWinner->id;
        });
        
        $this->assertNotNull($extraWinnerMatch);
        
        // The opponent should be a position 2 winner
        $opponentId = $extraWinnerMatch->player_1_id == $extraWinner->id 
            ? $extraWinnerMatch->player_2_id 
            : $extraWinnerMatch->player_1_id;
        
        if ($opponentId) {
            $opponentWinner = Winner::where('player_id', $opponentId)
                ->where('tournament_id', $this->tournament->id)
                ->where('level', 'community')
                ->first();
            
            $this->assertEquals(2, $opponentWinner->position);
        }
    }

    public function test_county_initialization_with_single_community()
    {
        // Only one community with winners
        $community = $this->communities[0];
        
        for ($pos = 1; $pos <= 3; $pos++) {
            $winner = User::factory()->create([
                'email' => "winner{$pos}@test.com",
                'community_id' => $community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            
            Winner::create([
                'player_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'community',
                'position' => $pos,
                'level_id' => $community->id,
                'prize_amount' => 0
            ]);
        }
        
        // Initialize county level
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $response->assertStatus(200);
        
        // With 3 players from single community, should create appropriate matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $this->county->id)
            ->get();
        
        // 3 players should result in 2 matches (1 regular + 1 bye)
        $this->assertEquals(2, $matches->count());
    }

    public function test_cannot_initialize_county_without_community_winners()
    {
        // Try to initialize county level without any community winners
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'No winners found from previous level'
        ]);
    }

    public function test_county_match_naming_convention()
    {
        $this->setupCommunityWinners();
        
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $this->county->id)
            ->get();
        
        foreach ($matches as $match) {
            $this->assertStringContainsString('county', $match->match_name);
            $this->assertStringContainsString('R1', $match->match_name);
        }
    }

    public function test_multiple_counties_initialize_independently()
    {
        // Create another county
        $county2 = County::create([
            'name' => 'Test County 2',
            'region_id' => $this->region->id
        ]);
        
        // Create community and winners in county2
        $community2 = Community::create([
            'name' => 'County2 Community',
            'county_id' => $county2->id,
            'region_id' => $this->region->id
        ]);
        
        for ($pos = 1; $pos <= 2; $pos++) {
            $winner = User::factory()->create([
                'email' => "county2_winner{$pos}@test.com",
                'community_id' => $community2->id,
                'county_id' => $county2->id,
                'region_id' => $this->region->id,
            ]);
            
            Winner::create([
                'player_id' => $winner->id,
                'tournament_id' => $this->tournament->id,
                'level' => 'community',
                'position' => $pos,
                'level_id' => $community2->id,
                'prize_amount' => 0
            ]);
        }
        
        // Setup winners for county1
        $this->setupCommunityWinners();
        
        // Initialize both counties
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $response->assertStatus(200);
        
        // Check county1 matches
        $county1Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $this->county->id)
            ->get();
        
        $this->assertEquals(5, $county1Matches->count()); // 9 players
        
        // Check county2 matches
        $county2Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $county2->id)
            ->get();
        
        $this->assertEquals(1, $county2Matches->count()); // 2 players
        
        // Verify no cross-county matches
        foreach ($county1Matches as $match) {
            if ($match->player_1_id) {
                $player1 = User::find($match->player_1_id);
                $this->assertEquals($this->county->id, $player1->county_id);
            }
            if ($match->player_2_id) {
                $player2 = User::find($match->player_2_id);
                $this->assertEquals($this->county->id, $player2->county_id);
            }
        }
    }

    public function test_position_fairness_in_pairing()
    {
        // Create winners with specific positions
        foreach ($this->communities as $index => $community) {
            for ($pos = 1; $pos <= 3; $pos++) {
                $winner = User::factory()->create([
                    'email' => "pos{$pos}_comm{$index}@test.com",
                    'community_id' => $community->id,
                    'county_id' => $this->county->id,
                    'region_id' => $this->region->id,
                ]);
                
                Winner::create([
                    'player_id' => $winner->id,
                    'tournament_id' => $this->tournament->id,
                    'level' => 'community',
                    'position' => $pos,
                    'community_id' => $community->id,
                    'county_id' => $this->county->id,
                    'region_id' => $this->region->id,
                    'prize_amount' => 0
                ]);
            }
        }
        
        // Initialize county level
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'county')
            ->where('group_id', $this->county->id)
            ->get();
        
        // Check pairing fairness
        foreach ($matches as $match) {
            if ($match->player_1_id && $match->player_2_id) {
                $winner1 = Winner::where('player_id', $match->player_1_id)
                    ->where('tournament_id', $this->tournament->id)
                    ->where('level', 'community')
                    ->first();
                
                $winner2 = Winner::where('player_id', $match->player_2_id)
                    ->where('tournament_id', $this->tournament->id)
                    ->where('level', 'community')
                    ->first();
                
                // Position difference should not be too large
                $positionDiff = abs($winner1->position - $winner2->position);
                $this->assertLessThanOrEqual(2, $positionDiff);
            }
        }
    }
}
