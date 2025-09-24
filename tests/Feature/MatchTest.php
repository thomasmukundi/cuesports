<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    protected $player1;
    protected $player2;
    protected $tournament;
    protected $match;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create location hierarchy
        $region = Region::create(['name' => 'Test Region']);
        $county = County::create([
            'name' => 'Test County',
            'region_id' => $region->id
        ]);
        $community = Community::create([
            'name' => 'Test Community',
            'county_id' => $county->id,
            'region_id' => $region->id
        ]);
        
        // Create players
        $this->player1 = User::factory()->create([
            'community_id' => $community->id,
            'county_id' => $county->id,
            'region_id' => $region->id,
        ]);
        
        $this->player2 = User::factory()->create([
            'community_id' => $community->id,
            'county_id' => $county->id,
            'region_id' => $region->id,
        ]);
        
        // Create tournament
        $this->tournament = Tournament::create([
            'name' => 'Test Tournament',
            'tournament_charge' => 0,
            'status' => 'ongoing',
            'automation_mode' => 'manual'
        ]);
        
        // Create match
        $this->match = PoolMatch::create([
            'match_name' => 'test_match',
            'player_1_id' => $this->player1->id,
            'player_2_id' => $this->player2->id,
            'tournament_id' => $this->tournament->id,
            'level' => 'community',
            'round_name' => 'round_1',
            'status' => 'pending',
            'group_id' => $community->id
        ]);
    }

    public function test_player_can_view_their_matches()
    {
        Sanctum::actingAs($this->player1);
        
        $response = $this->getJson('/api/matches/my-matches');
        
        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_player_can_propose_match_dates()
    {
        Sanctum::actingAs($this->player1);
        
        $dates = [
            now()->addDays(1)->format('Y-m-d'),
            now()->addDays(2)->format('Y-m-d'),
            now()->addDays(3)->format('Y-m-d'),
        ];
        
        $response = $this->postJson("/api/matches/{$this->match->id}/propose-dates", [
            'dates' => $dates
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Dates proposed successfully'
            ]);
        
        $this->assertDatabaseHas('pool_matches', [
            'id' => $this->match->id,
            'proposed_dates' => json_encode($dates)
        ]);
    }

    public function test_player_can_select_preferred_dates()
    {
        Sanctum::actingAs($this->player2);
        
        // First, player1 proposes dates
        $this->match->update([
            'proposed_dates' => [
                now()->addDays(1)->format('Y-m-d'),
                now()->addDays(2)->format('Y-m-d'),
                now()->addDays(3)->format('Y-m-d'),
            ]
        ]);
        
        // Player2 selects dates
        $response = $this->postJson("/api/matches/{$this->match->id}/select-dates", [
            'dates' => [now()->addDays(2)->format('Y-m-d')]
        ]);
        
        $response->assertStatus(200);
    }

    public function test_player_can_submit_match_results()
    {
        Sanctum::actingAs($this->player1);
        
        $this->match->update(['status' => 'scheduled']);
        
        $response = $this->postJson("/api/matches/{$this->match->id}/submit-results", [
            'player_1_points' => 100,
            'player_2_points' => 85
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Results submitted. Waiting for confirmation from opponent.'
            ]);
        
        $this->assertDatabaseHas('pool_matches', [
            'id' => $this->match->id,
            'player_1_points' => 100,
            'player_2_points' => 85,
            'status' => 'pending_confirmation',
            'submitted_by' => $this->player1->id
        ]);
    }

    public function test_opponent_can_confirm_results()
    {
        Sanctum::actingAs($this->player2);
        
        // Set up match with submitted results
        $this->match->update([
            'status' => 'pending_confirmation',
            'player_1_points' => 100,
            'player_2_points' => 85,
            'submitted_by' => $this->player1->id,
            'winner_id' => $this->player1->id
        ]);
        
        $response = $this->postJson("/api/matches/{$this->match->id}/confirm-results", [
            'confirm' => true
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Results confirmed successfully'
            ]);
        
        $this->assertDatabaseHas('pool_matches', [
            'id' => $this->match->id,
            'status' => 'completed'
        ]);
    }

    public function test_opponent_can_reject_results()
    {
        Sanctum::actingAs($this->player2);
        
        // Set up match with submitted results
        $this->match->update([
            'status' => 'pending_confirmation',
            'player_1_points' => 100,
            'player_2_points' => 85,
            'submitted_by' => $this->player1->id
        ]);
        
        $response = $this->postJson("/api/matches/{$this->match->id}/confirm-results", [
            'confirm' => false
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Results rejected. Match reset to scheduled state.'
            ]);
        
        $this->assertDatabaseHas('pool_matches', [
            'id' => $this->match->id,
            'status' => 'scheduled',
            'player_1_points' => null,
            'player_2_points' => null,
            'submitted_by' => null
        ]);
    }

    public function test_player_cannot_confirm_own_submission()
    {
        Sanctum::actingAs($this->player1);
        
        $this->match->update([
            'status' => 'pending_confirmation',
            'submitted_by' => $this->player1->id
        ]);
        
        $response = $this->postJson("/api/matches/{$this->match->id}/confirm-results", [
            'confirm' => true
        ]);
        
        $response->assertStatus(403);
    }

    public function test_player_can_report_forfeit()
    {
        Sanctum::actingAs($this->player1);
        
        $response = $this->postJson("/api/matches/{$this->match->id}/forfeit");
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Forfeit reported successfully'
            ]);
        
        $this->assertDatabaseHas('pool_matches', [
            'id' => $this->match->id,
            'status' => 'forfeit',
            'winner_id' => $this->player1->id
        ]);
    }
}
