<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use App\Services\MatchAlgorithmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchAlgorithmServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $tournament;
    protected $community;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MatchAlgorithmService();
        
        // Create location hierarchy
        $region = Region::create(['name' => 'Test Region']);
        $county = County::create([
            'name' => 'Test County',
            'region_id' => $region->id
        ]);
        $this->community = Community::create([
            'name' => 'Test Community',
            'county_id' => $county->id,
            'region_id' => $region->id
        ]);
        
        $this->tournament = Tournament::create([
            'name' => 'Test Tournament',
            'tournament_charge' => 0,
            'status' => 'upcoming',
            'automation_mode' => 'manual'
        ]);
    }

    public function test_initialize_creates_matches_for_even_players()
    {
        // Create 6 players
        $players = User::factory()->count(6)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->community->county_id,
            'region_id' => $this->community->region_id,
        ]);
        
        foreach ($players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $result = $this->service->initialize($this->tournament->id, 'community');
        
        $this->assertEquals('success', $result['status']);
        
        // Should create 3 matches for 6 players
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)->get();
        $this->assertCount(3, $matches);
    }

    public function test_initialize_creates_bye_for_odd_players()
    {
        // Create 5 players
        $players = User::factory()->count(5)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->community->county_id,
            'region_id' => $this->community->region_id,
        ]);
        
        foreach ($players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $result = $this->service->initialize($this->tournament->id, 'community');
        
        $this->assertEquals('success', $result['status']);
        
        // Should create 2 regular matches and 1 bye
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)->get();
        $this->assertCount(3, $matches);
        
        $byeMatch = $matches->where('bye_player_id', '!=', null)->first();
        $this->assertNotNull($byeMatch);
        $this->assertEquals('completed', $byeMatch->status);
    }

    public function test_special_case_handling_for_two_players()
    {
        // Create exactly 2 players
        $players = User::factory()->count(2)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->community->county_id,
            'region_id' => $this->community->region_id,
        ]);
        
        foreach ($players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $result = $this->service->initialize($this->tournament->id, 'community');
        
        $this->assertEquals('success', $result['status']);
        
        // Should create 1 final match
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)->get();
        $this->assertCount(1, $matches);
        $this->assertEquals('2_final', $matches->first()->match_name);
        $this->assertEquals('final', $matches->first()->round_name);
    }

    public function test_special_case_handling_for_three_players()
    {
        // Create exactly 3 players
        $players = User::factory()->count(3)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->community->county_id,
            'region_id' => $this->community->region_id,
        ]);
        
        foreach ($players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $result = $this->service->initialize($this->tournament->id, 'community');
        
        $this->assertEquals('success', $result['status']);
        
        // Should create 1 semi-final match
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)->get();
        $this->assertCount(1, $matches);
        $this->assertEquals('3_SF', $matches->first()->match_name);
        $this->assertEquals('semi_final', $matches->first()->round_name);
    }

    public function test_special_case_handling_for_four_players()
    {
        // Create exactly 4 players
        $players = User::factory()->count(4)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->community->county_id,
            'region_id' => $this->community->region_id,
        ]);
        
        foreach ($players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $result = $this->service->initialize($this->tournament->id, 'community');
        
        $this->assertEquals('success', $result['status']);
        
        // Should create 2 quarter-final matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)->get();
        $this->assertCount(2, $matches);
        $this->assertEquals('quarter_final', $matches->first()->round_name);
    }

    public function test_generate_next_round_fails_if_current_not_completed()
    {
        // Create and initialize tournament with 4 players
        $players = User::factory()->count(4)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->community->county_id,
            'region_id' => $this->community->region_id,
        ]);
        
        foreach ($players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $this->service->initialize($this->tournament->id, 'community');
        
        // Try to generate next round without completing current
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Current round is not yet completed');
        
        $this->service->generateNextRound($this->tournament, 'community', $this->community->id);
    }

    public function test_check_level_completion_returns_correct_status()
    {
        // Create 2 players for simple test
        $players = User::factory()->count(2)->create([
            'community_id' => $this->community->id,
            'county_id' => $this->community->county_id,
            'region_id' => $this->community->region_id,
        ]);
        
        foreach ($players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'free'
            ]);
        }
        
        $this->service->initialize($this->tournament->id, 'community');
        
        // Check completion - should not be complete
        $result = $this->service->checkLevelCompletion(
            $this->tournament->id, 
            'community', 
            $this->community->id
        );
        
        $this->assertFalse($result['completed']);
        $this->assertEquals(1, $result['pending_matches']);
        
        // Complete the match
        $match = PoolMatch::where('tournament_id', $this->tournament->id)->first();
        $match->update([
            'status' => 'completed',
            'winner_id' => $players[0]->id,
            'player_1_points' => 100,
            'player_2_points' => 85
        ]);
        
        // Check again - should be complete
        $result = $this->service->checkLevelCompletion(
            $this->tournament->id, 
            'community', 
            $this->community->id
        );
        
        $this->assertTrue($result['completed']);
        $this->assertEquals(0, $result['pending_matches']);
    }
}
