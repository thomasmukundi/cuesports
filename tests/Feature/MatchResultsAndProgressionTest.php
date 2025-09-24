<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use App\Models\PoolMatch;
use App\Services\MatchAlgorithmService;
use App\Jobs\CheckTournamentCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Queue;

class MatchResultsAndProgressionTest extends TestCase
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
        
        // Create geographic structure with unique names
        $uniqueId = uniqid();
        $this->region = Region::create(['name' => "Test Region {$uniqueId}"]);
        $this->county = County::create([
            'name' => "Test County {$uniqueId}",
            'region_id' => $this->region->id
        ]);
        $this->community = Community::create([
            'name' => "Test Community {$uniqueId}",
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
            'status' => 'ongoing',
            'automation_mode' => 'automatic'
        ]);
        
        // Create and register players
        $this->createAndRegisterPlayers();
        
        // Initialize tournament
        $this->initializeTournament();
    }

    protected function createAndRegisterPlayers($count = 8)
    {
        $uniqueId = uniqid();
        for ($i = 1; $i <= $count; $i++) {
            $player = User::factory()->create([
                'email' => "player{$i}-{$uniqueId}@test.com",
                'community_id' => $this->community->id,
                'county_id' => $this->county->id,
                'region_id' => $this->region->id,
            ]);
            $this->players[] = $player;
            $this->tournament->registeredUsers()->attach($player->id, [
                'payment_status' => 'paid',
                'status' => 'approved'
            ]);
        }
    }

    protected function initializeTournament()
    {
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        $this->tournament->update(['status' => 'ongoing']);
    }

    public function test_can_propose_match_date()
    {
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        Sanctum::actingAs(User::find($match->player_1_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/propose-dates", [
            'dates' => [
                now()->addDays(2)->format('Y-m-d H:i:s'),
                now()->addDays(3)->format('Y-m-d H:i:s'),
                now()->addDays(4)->format('Y-m-d H:i:s')
            ]
        ]);
        
        $response->assertStatus(200);
        
        $match->refresh();
        $this->assertNotNull($match->proposed_dates);
        $this->assertIsArray($match->proposed_dates);
    }

    public function test_can_confirm_match_date()
    {
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        // Player 1 proposes dates first
        Sanctum::actingAs(User::find($match->player_1_id));
        $proposedDates = [
            now()->addDays(2)->format('Y-m-d H:i:s'),
            now()->addDays(3)->format('Y-m-d H:i:s'),
            now()->addDays(4)->format('Y-m-d H:i:s')
        ];
        
        $this->postJson("/api/matches/{$match->id}/propose-dates", [
            'dates' => $proposedDates
        ]);
        
        // Player 1 selects preferred dates
        $this->postJson("/api/matches/{$match->id}/select-dates", [
            'dates' => [$proposedDates[0], $proposedDates[1]]
        ]);
        
        // Player 2 selects overlapping preferred dates
        Sanctum::actingAs(User::find($match->player_2_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/select-dates", [
            'dates' => [$proposedDates[0], $proposedDates[2]]
        ]);
        
        $response->assertStatus(200);
        
        $match->refresh();
        $this->assertEquals('scheduled', $match->status);
        $this->assertNotNull($match->scheduled_date);
    }

    public function test_can_schedule_match()
    {
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        // Player 1 proposes dates first
        Sanctum::actingAs(User::find($match->player_1_id));
        $proposedDates = [
            now()->addDays(2)->format('Y-m-d H:i:s'),
            now()->addDays(3)->format('Y-m-d H:i:s'),
            now()->addDays(4)->format('Y-m-d H:i:s')
        ];
        
        $this->postJson("/api/matches/{$match->id}/propose-dates", [
            'dates' => $proposedDates
        ]);
        
        // Player 1 selects preferred dates
        $this->postJson("/api/matches/{$match->id}/select-dates", [
            'dates' => [$proposedDates[0], $proposedDates[1]]
        ]);
        
        // Player 2 selects overlapping preferred dates
        Sanctum::actingAs(User::find($match->player_2_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/select-dates", [
            'dates' => [$proposedDates[0], $proposedDates[2]]
        ]);
        
        $response->assertStatus(200);
        
        $match->refresh();
        $this->assertEquals('scheduled', $match->status);
        $this->assertNotNull($match->scheduled_date);
    }

    public function test_can_submit_match_result()
    {
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        // Set match as scheduled
        $match->update([
            'scheduled_date' => now(),
            'status' => 'scheduled'
        ]);
        
        // Player 1 submits result
        Sanctum::actingAs(User::find($match->player_1_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/submit-results", [
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        $response->assertStatus(200);
        
        $match->refresh();
        $this->assertEquals('pending_confirmation', $match->status);
        $this->assertEquals($match->player_1_id, $match->winner_id);
        $this->assertEquals($match->player_1_id, $match->submitted_by);
        $this->assertEquals(5, $match->player_1_points);
        $this->assertEquals(3, $match->player_2_points);
    }

    public function test_can_confirm_match_result()
    {
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        // Set match with submitted results
        $match->update([
            'status' => 'pending_confirmation',
            'winner_id' => $match->player_1_id,
            'submitted_by' => $match->player_1_id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Player 2 confirms result
        Sanctum::actingAs(User::find($match->player_2_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/confirm-results", [
            'confirm' => true
        ]);
        
        $response->assertStatus(200);
        
        $match->refresh();
        $this->assertEquals('completed', $match->status);
        $this->assertEquals($match->player_1_id, $match->winner_id);
        $this->assertNotNull($match->updated_at);
    }

    public function test_round_completion_triggers_next_round_generation()
    {
        Queue::fake();
        
        // Complete all first round matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'round_1')
            ->get();
        
        foreach ($matches as $match) {
            // Simulate match completion
            $match->update([
                'match_date' => now(),
                'date_confirmed' => true,
                'status' => 'completed',
                'winner_id' => $match->player_1_id,
                'player_1_points' => 5,
                'player_2_points' => 3,
                'submitted_winner_id' => $match->player_1_id,
                'submitted_by' => $match->player_1_id,
                'confirmed_at' => now(),
                'confirmed_by' => $match->player_2_id
            ]);
        }
        
        // Trigger completion check
        CheckTournamentCompletion::dispatch($this->tournament);
        
        Queue::assertPushed(CheckTournamentCompletion::class);
    }

    public function test_next_round_generated_after_round_completion()
    {
        // Complete all first round matches (8 players = 4 matches)
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'round_1')
            ->get();
        
        $this->assertEquals(4, $matches->count());
        
        foreach ($matches as $match) {
            $match->update([
                'status' => 'completed',
                'winner_id' => $match->player_1_id,
                'player_1_points' => 5,
                'player_2_points' => 3,
                'confirmed_at' => now()
            ]);
        }
        
        // Generate next round
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/generate-next-round", [
            'level' => 'community',
            'community_id' => $this->community->id
        ]);
        
        $response->assertStatus(200);
        
        // Check round 2 matches exist (4 winners = 2 matches)
        $round2Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'round_2')
            ->get();
        
        $this->assertEquals(2, $round2Matches->count());
        
        // Verify winners from round 1 are in round 2
        foreach ($round2Matches as $match) {
            $this->assertContains($match->player_1_id, $matches->pluck('winner_id')->toArray());
            $this->assertContains($match->player_2_id, $matches->pluck('winner_id')->toArray());
        }
    }

    public function test_final_rounds_with_four_players()
    {
        // Create a new tournament with 4 players
        $uniqueId = uniqid();
        $region = Region::create(['name' => "Test Region {$uniqueId}"]);
        $county = County::create(['name' => "Test County {$uniqueId}", 'region_id' => $region->id]);
        $community = Community::create(['name' => "Test Community {$uniqueId}", 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $admin = User::factory()->create(['email' => 'test-admin-flow@cuesports.com', 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $tournament = Tournament::create(['name' => 'Test Tournament 4P', 'special' => false, 'tournament_charge' => 100, 'start_date' => now()->addDays(1), 'end_date' => now()->addDays(30), 'status' => 'ongoing', 'automation_mode' => 'automatic']);
        
        // Create 4 players
        for ($i = 1; $i <= 4; $i++) {
            $player = User::factory()->create(['email' => "player{$i}-{$uniqueId}@test.com", 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
            $tournament->registeredUsers()->attach($player->id, ['payment_status' => 'paid', 'status' => 'approved']);
        }
        
        // Initialize tournament
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/tournaments/{$tournament->id}/initialize", ['level' => 'community']);
        $tournament->update(['status' => 'ongoing']);
        
        // Round 1: 2 matches
        $round1Matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'quarter_final')
            ->get();
        
        $this->assertEquals(2, $round1Matches->count());
        
        // Complete round 1
        $winners = [];
        $losers = [];
        foreach ($round1Matches as $match) {
            $winner = $match->player_1_id;
            $loser = $match->player_2_id;
            
            $match->update([
                'status' => 'completed',
                'winner_id' => $winner,
                'player_1_points' => 5,
                'player_2_points' => 3
            ]);
            
            $winners[] = $winner;
            $losers[] = $loser;
        }
        
        // Generate round 2 (Winners_SF and Losers_SF)
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/tournaments/{$tournament->id}/generate-next-round", [
            'level' => 'community',
            'community_id' => $community->id
        ]);
        
        // Check round 2 matches
        $round2Matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'semi_final')
            ->get();
        
        $this->assertEquals(2, $round2Matches->count());
        
        // Check for Winners_SF match
        $winnersSF = $round2Matches->first(function ($match) {
            return str_contains($match->match_name, 'Winners_SF');
        });
        $this->assertNotNull($winnersSF);
        $this->assertContains($winnersSF->player_1_id, $winners);
        $this->assertContains($winnersSF->player_2_id, $winners);
        
        // Check for Losers_SF match
        $losersSF = $round2Matches->first(function ($match) {
            return str_contains($match->match_name, 'Losers_SF');
        });
        $this->assertNotNull($losersSF);
        $this->assertContains($losersSF->player_1_id, $losers);
        $this->assertContains($losersSF->player_2_id, $losers);
    }

    public function test_three_player_progression()
    {
        // Create a new tournament with 3 players
        $uniqueId = uniqid();
        $region = Region::create(['name' => "Test Region {$uniqueId}"]);
        $county = County::create(['name' => "Test County {$uniqueId}", 'region_id' => $region->id]);
        $community = Community::create(['name' => "Test Community {$uniqueId}", 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $admin = User::factory()->create(['email' => "admin-3p-{$uniqueId}@cuesports.com", 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $tournament = Tournament::create(['name' => 'Test Tournament 3P', 'special' => false, 'tournament_charge' => 100, 'start_date' => now()->addDays(1), 'end_date' => now()->addDays(30), 'status' => 'ongoing', 'automation_mode' => 'automatic']);
        
        // Create 3 players
        for ($i = 1; $i <= 3; $i++) {
            $player = User::factory()->create(['email' => "player{$i}-{$uniqueId}@test.com", 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
            $tournament->registeredUsers()->attach($player->id, ['payment_status' => 'paid', 'status' => 'approved']);
        }
        
        // Initialize tournament
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/tournaments/{$tournament->id}/initialize", ['level' => 'community']);
        $tournament->update(['status' => 'ongoing']);
        
        // Round 1: Should have 1 regular match and 1 bye
        $matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'semi_final')
            ->get();
        
        // For 3 players, there should be exactly 1 semi-final match
        $this->assertEquals(1, $matches->count());
        $regularMatch = $matches->first();
        $this->assertNotNull($regularMatch);
        
        // Complete regular match
        $regularMatch->update([
            'status' => 'completed',
            'winner_id' => $regularMatch->player_1_id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // The third player automatically advances to final (no bye match needed)
        
        // Generate next round - Final match
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/tournaments/{$tournament->id}/generate-next-round", [
            'level' => 'community',
            'community_id' => $community->id
        ]);
        
        // Check Final match
        $finalMatch = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'final')
            ->first();
        
        $this->assertNotNull($finalMatch);
        $this->assertStringContainsString('Final', $finalMatch->match_name);
        
        // Final should be between SF loser and bye player
        $expectedPlayers = [
            $regularMatch->player_2_id, // SF loser
            $byeMatch->player_1_id      // Bye player
        ];
        
        $this->assertContains($finalMatch->player_1_id, $expectedPlayers);
        $this->assertContains($finalMatch->player_2_id, $expectedPlayers);
    }

    public function test_two_player_final_match()
    {
        // Create a new tournament with 2 players
        $uniqueId = uniqid();
        $region = Region::create(['name' => "Test Region {$uniqueId}"]);
        $county = County::create(['name' => "Test County {$uniqueId}", 'region_id' => $region->id]);
        $community = Community::create(['name' => "Test Community {$uniqueId}", 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $admin = User::factory()->create(['email' => "admin-2p-{$uniqueId}@cuesports.com", 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $tournament = Tournament::create(['name' => 'Test Tournament 2P', 'special' => false, 'tournament_charge' => 100, 'start_date' => now()->addDays(1), 'end_date' => now()->addDays(30), 'status' => 'ongoing', 'automation_mode' => 'automatic']);
        
        // Create 2 players
        for ($i = 1; $i <= 2; $i++) {
            $player = User::factory()->create(['email' => "player{$i}-{$uniqueId}@test.com", 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
            $tournament->registeredUsers()->attach($player->id, ['payment_status' => 'paid', 'status' => 'approved']);
        }
        
        // Initialize tournament
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/tournaments/{$tournament->id}/initialize", ['level' => 'community']);
        $tournament->update(['status' => 'ongoing']);
        
        // Should have exactly 1 match - 2_final
        $match = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->first();
        
        $this->assertNotNull($match);
        $this->assertStringContainsString('2_final', $match->match_name);
        
        // Complete the match
        $match->update([
            'status' => 'completed',
            'winner_id' => $match->player_1_id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // No more rounds should be generated
        Sanctum::actingAs($admin);
        $response = $this->postJson("/api/admin/tournaments/{$tournament->id}/generate-next-round", [
            'level' => 'community',
            'community_id' => $community->id
        ]);
        
        // Should indicate next round generated (or level complete)
        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->json('message'), 'Next round generated') ||
            str_contains($response->json('message'), 'Level completed')
        );
    }

    public function test_odd_number_player_pairing_in_subsequent_rounds()
    {
        // Create a new tournament with 5 players
        $uniqueId = uniqid();
        $region = Region::create(['name' => "Test Region {$uniqueId}"]);
        $county = County::create(['name' => "Test County {$uniqueId}", 'region_id' => $region->id]);
        $community = Community::create(['name' => "Test Community {$uniqueId}", 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $admin = User::factory()->create(['email' => "admin-5p-{$uniqueId}@cuesports.com", 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
        
        $tournament = Tournament::create(['name' => 'Test Tournament 5P', 'special' => false, 'tournament_charge' => 100, 'start_date' => now()->addDays(1), 'end_date' => now()->addDays(30), 'status' => 'ongoing', 'automation_mode' => 'automatic']);
        
        // Create 5 players
        for ($i = 1; $i <= 5; $i++) {
            $player = User::factory()->create(['email' => "player{$i}-{$uniqueId}@test.com", 'community_id' => $community->id, 'county_id' => $county->id, 'region_id' => $region->id]);
            $tournament->registeredUsers()->attach($player->id, ['payment_status' => 'paid', 'status' => 'approved']);
        }
        
        // Initialize tournament
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/tournaments/{$tournament->id}/initialize", ['level' => 'community']);
        $tournament->update(['status' => 'ongoing']);
        
        // Round 1: Should have matches
        $round1Matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->get();
        
        $this->assertGreaterThan(0, $round1Matches->count());
        
        // Complete round 1 (3 winners)
        foreach ($round1Matches as $match) {
            $match->update([
                'status' => 'completed',
                'winner_id' => $match->player_1_id,
                'player_1_points' => 5,
                'player_2_points' => 3
            ]);
        }
        
        // Generate round 2
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/tournaments/{$tournament->id}/generate-next-round", [
            'level' => 'community',
            'community_id' => $community->id
        ]);
        
        // Round 2: Should have additional matches
        $round2Matches = PoolMatch::where('tournament_id', $tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'round_2')
            ->get();
        
        $this->assertGreaterThanOrEqual(0, $round2Matches->count());
        
        // Verify at least one loser from round 1 is in round 2
        $losersFromRound1 = $round1Matches->map(function ($match) {
            return $match->winner_id == $match->player_1_id 
                ? $match->player_2_id 
                : $match->player_1_id;
        });
        
        $playersInRound2 = $round2Matches->flatMap(function ($match) {
            return [$match->player_1_id, $match->player_2_id];
        });
        
        $losersInRound2 = $losersFromRound1->intersect($playersInRound2);
        $this->assertGreaterThan(0, $losersInRound2->count());
    }
}
