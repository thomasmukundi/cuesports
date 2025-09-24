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
use App\Jobs\CheckTournamentCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class WinnerDeterminationTest extends TestCase
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
        
        // Create admin with unique email
        $uniqueId = time() . rand(1000, 9999);
        $this->admin = User::factory()->create([
            'email' => "admin-{$uniqueId}@cuesports.com",
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
    }

    protected function createAndRegisterPlayers($count, $community = null)
    {
        $targetCommunity = $community ?? $this->community;
        
        for ($i = 1; $i <= $count; $i++) {
            $player = User::factory()->create([
                'email' => "player{$i}_{$targetCommunity->id}@test.com",
                'community_id' => $targetCommunity->id,
                'county_id' => $targetCommunity->county_id,
                'region_id' => $targetCommunity->region_id,
            ]);
            $this->players[] = $player;
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'paid'
            ]);
        }
        
        return $this->players;
    }

    public function test_winner_determination_with_four_players()
    {
        $this->createAndRegisterPlayers(4);
        
        // Initialize tournament
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        // Debug: Check response and registered players
        $response->assertStatus(200);
        $approvedPlayers = $this->tournament->approvedPlayers;
        $this->assertEquals(4, $approvedPlayers->count(), 'Should have 4 approved players');
        
        // Round 1: 2 matches
        $round1Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'round_1')
            ->get();
        
        $this->assertEquals(2, $round1Matches->count());
        
        // Complete round 1
        $match1Winner = $round1Matches[0]->player_1_id;
        $match1Loser = $round1Matches[0]->player_2_id;
        $match2Winner = $round1Matches[1]->player_1_id;
        $match2Loser = $round1Matches[1]->player_2_id;
        
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
            'level' => 'community',
            'community_id' => $this->community->id
        ]);
        
        $round2Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round', 2)
            ->get();
        
        // Complete Winners_SF (winner gets position 1)
        $winnersSF = $round2Matches->first(fn($m) => str_contains($m->match_name, 'Winners_SF'));
        $position1Player = $winnersSF->player_1_id;
        $winnersSFLoser = $winnersSF->player_2_id;
        
        $winnersSF->update([
            'status' => 'completed',
            'winner_id' => $position1Player,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Complete Losers_SF
        $losersSF = $round2Matches->first(fn($m) => str_contains($m->match_name, 'Losers_SF'));
        $losersSFWinner = $losersSF->player_1_id;
        
        $losersSF->update([
            'status' => 'completed',
            'winner_id' => $losersSFWinner,
            'player_1_points' => 5,
            'player_2_points' => 2
        ]);
        
        // Generate round 3 (Final)
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/generate-next-round", [
            'level' => 'community',
            'community_id' => $this->community->id
        ]);
        
        $finalMatch = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round', 3)
            ->first();
        
        $this->assertStringContainsString('Final', $finalMatch->match_name);
        
        // Complete Final (determines positions 2 and 3)
        $position2Player = $winnersSFLoser;
        $position3Player = $losersSFWinner;
        
        $finalMatch->update([
            'status' => 'completed',
            'winner_id' => $position2Player,
            'player_1_points' => 5,
            'player_2_points' => 4
        ]);
        
        // Check tournament completion to trigger winner determination
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Check winners table
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $this->community->id)
            ->get();
        
        $this->assertEquals(3, $winners->count());
        
        // Verify positions
        $position1 = $winners->where('position', 1)->first();
        $position2Record = $winners->where('position', 2)->first();
        $position3Record = $winners->where('position', 3)->first();
        
        $this->assertEquals($position1Player, $position1->player_id);
        $this->assertEquals($position2Player, $position2Record->player_id);
        $this->assertEquals($position3Player, $position3Record->player_id);
    }

    public function test_winner_determination_with_three_players()
    {
        $this->createAndRegisterPlayers(3);
        
        // Initialize tournament
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        // Round 1: 1 regular match + 1 bye
        $round1Matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round_name', 'round_1')
            ->get();
        
        $regularMatch = $round1Matches->where('player_2_id', '!=', null)->first();
        $byeMatch = $round1Matches->where('player_2_id', null)->first();
        
        // Complete regular match (SF)
        $sfWinner = $regularMatch->player_1_id;
        $sfLoser = $regularMatch->player_2_id;
        
        $regularMatch->update([
            'status' => 'completed',
            'winner_id' => $sfWinner,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Position 1 is the SF winner
        $position1Player = $sfWinner;
        
        // Generate round 2 (Final between SF loser and bye player)
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/generate-next-round", [
            'level' => 'community',
            'community_id' => $this->community->id
        ]);
        
        $finalMatch = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('round', 2)
            ->first();
        
        $this->assertStringContainsString('Final', $finalMatch->match_name);
        
        // Complete Final
        $position2Player = $sfLoser;
        $position3Player = $byeMatch->player_1_id;
        
        $finalMatch->update([
            'status' => 'completed',
            'winner_id' => $position2Player,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Check tournament completion
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Check winners
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $this->community->id)
            ->get();
        
        $this->assertEquals(3, $winners->count());
        
        // Verify positions
        $this->assertEquals($position1Player, $winners->where('position', 1)->first()->user_id);
        $this->assertEquals($position2Player, $winners->where('position', 2)->first()->user_id);
        $this->assertEquals($position3Player, $winners->where('position', 3)->first()->user_id);
    }

    public function test_winner_determination_with_two_players()
    {
        $this->createAndRegisterPlayers(2);
        
        // Initialize tournament
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        // Should have 1 match - 2_player_final
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        $this->assertStringContainsString('2_final', $match->match_name);
        
        // Complete the match
        $position1Player = $match->player_1_id;
        $position2Player = $match->player_2_id;
        
        $match->update([
            'status' => 'completed',
            'winner_id' => $position1Player,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Check tournament completion
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Check winners
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $this->community->id)
            ->get();
        
        $this->assertEquals(2, $winners->count());
        
        // Verify positions
        $this->assertEquals($position1Player, $winners->where('position', 1)->first()->user_id);
        $this->assertEquals($position2Player, $winners->where('position', 2)->first()->user_id);
    }

    public function test_winner_determination_with_one_player()
    {
        $this->createAndRegisterPlayers(1);
        
        // Initialize tournament
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        // Should have no matches
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->get();
        
        $this->assertEquals(0, $matches->count());
        
        // Check tournament completion
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Check winners - single player should be position 1
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $this->community->id)
            ->get();
        
        $this->assertEquals(1, $winners->count());
        
        $winner = $winners->first();
        $this->assertEquals(1, $winner->position);
        $this->assertEquals($this->players[0]->id, $winner->player_id);
    }

    public function test_multiple_communities_complete_independently()
    {
        // Create second community
        $community2 = Community::create([
            'name' => 'Test Community 2',
            'county_id' => $this->county->id,
            'region_id' => $this->region->id
        ]);
        
        // Create players in both communities
        $this->createAndRegisterPlayers(2, $this->community);
        $community2Players = [];
        for ($i = 1; $i <= 2; $i++) {
            $player = User::factory()->create([
                'email' => "c2_player{$i}@test.com",
                'community_id' => $community2->id,
                'county_id' => $community2->county_id,
                'region_id' => $community2->region_id,
            ]);
            $community2Players[] = $player;
            $this->tournament->registeredUsers()->attach($player->id);
        }
        
        // Initialize tournament
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        // Complete community 1 match
        $community1Match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $this->community->id)
            ->first();
        
        $community1Match->update([
            'status' => 'completed',
            'winner_id' => $community1Match->player_1_id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Check tournament completion for community 1
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Community 1 should have winners
        $community1Winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $this->community->id)
            ->get();
        
        $this->assertEquals(2, $community1Winners->count());
        
        // Community 2 should not have winners yet
        $community2Winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $community2->id)
            ->get();
        
        $this->assertEquals(0, $community2Winners->count());
        
        // Complete community 2 match
        $community2Match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $community2->id)
            ->first();
        
        $community2Match->update([
            'status' => 'completed',
            'winner_id' => $community2Match->player_1_id,
            'player_1_points' => 5,
            'player_2_points' => 2
        ]);
        
        // Check tournament completion again
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Now community 2 should have winners
        $community2Winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $community2->id)
            ->get();
        
        $this->assertEquals(2, $community2Winners->count());
    }

    public function test_admin_can_view_all_winners()
    {
        // Create players and complete tournament
        $this->createAndRegisterPlayers(2);
        
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        $match->update([
            'status' => 'completed',
            'winner_id' => $match->player_1_id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Trigger winner determination
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Admin should be able to query winners
        $response = $this->getJson("/api/admin/tournaments/{$this->tournament->id}/winners");
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'winners' => [
                '*' => [
                    'id',
                    'user_id',
                    'tournament_id',
                    'level',
                    'position',
                    'community_id',
                    'user' => [
                        'id',
                        'first_name',
                        'last_name'
                    ]
                ]
            ]
        ]);
    }

    public function test_prizes_awarded_on_tournament_completion()
    {
        $this->createAndRegisterPlayers(2);
        
        // Initialize and complete tournament
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->first();
        
        $winner = User::find($match->player_1_id);
        $loser = User::find($match->player_2_id);
        
        $initialWinnerBalance = $winner->total_points;
        $initialLoserBalance = $loser->total_points;
        
        $match->update([
            'status' => 'completed',
            'winner_id' => $winner->id,
            'player_1_points' => 5,
            'player_2_points' => 3
        ]);
        
        // Mark tournament as completed
        $this->tournament->update(['status' => 'completed']);
        
        // Trigger completion job
        $job = new CheckTournamentCompletion($this->tournament);
        $job->handle($this->matchService);
        
        // Check prize distribution
        $winner->refresh();
        $loser->refresh();
        
        $prizeDistribution = json_decode($this->tournament->prize_distribution, true);
        
        // Winner should receive position 1 prize
        $this->assertEquals(
            $initialWinnerBalance + $prizeDistribution['position_1'],
            $winner->total_points
        );
        
        // Loser should receive position 2 prize
        $this->assertEquals(
            $initialLoserBalance + $prizeDistribution['position_2'],
            $loser->total_points
        );
    }
}
