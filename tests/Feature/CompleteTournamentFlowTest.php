<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use App\Models\PoolMatch;
use App\Models\Winner;
use App\Models\Notification;
use App\Services\MatchAlgorithmService;
use App\Jobs\CheckTournamentCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Queue;

class CompleteTournamentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $matchService;
    protected $tournament;
    protected $players = [];
    protected $region;
    protected $counties = [];
    protected $communities = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->matchService = app(MatchAlgorithmService::class);
        
        // Create hierarchical structure
        $this->setupGeographicStructure();
        
        // Create admin
        $this->admin = User::factory()->create([
            'email' => 'test-admin-flow@cuesports.com',
            'community_id' => $this->communities[0]->id,
            'county_id' => $this->counties[0]->id,
            'region_id' => $this->region->id,
        ]);
        
        // Create players distributed across communities
        $this->createPlayers();
        
        // Create tournament
        $this->tournament = Tournament::create([
            'name' => 'Kenya National Tournament',
            'special' => false,
            'community_prize' => 5000,
            'county_prize' => 10000,
            'regional_prize' => 20000,
            'national_prize' => 50000,
            'tournament_charge' => 100,
            'status' => 'upcoming',
            'automation_mode' => 'manual'
        ]);
    }

    private function setupGeographicStructure()
    {
        // Create 1 region
        $this->region = Region::create(['name' => 'Central Region']);
        
        // Create 2 counties
        $this->counties[] = County::create([
            'name' => 'Nairobi County',
            'region_id' => $this->region->id
        ]);
        $this->counties[] = County::create([
            'name' => 'Kiambu County',
            'region_id' => $this->region->id
        ]);
        
        // Create 3 communities per county
        foreach ($this->counties as $county) {
            for ($i = 1; $i <= 3; $i++) {
                $this->communities[] = Community::create([
                    'name' => "{$county->name} Community {$i}",
                    'county_id' => $county->id,
                    'region_id' => $this->region->id
                ]);
            }
        }
    }

    private function createPlayers()
    {
        // Create 8 players per community (48 total)
        foreach ($this->communities as $community) {
            for ($i = 1; $i <= 8; $i++) {
                $player = User::factory()->create([
                    'name' => "Player {$community->name} {$i}",
                    'community_id' => $community->id,
                    'county_id' => $community->county_id,
                    'region_id' => $community->region_id,
                ]);
                
                // Register player for tournament
                $this->tournament->registeredUsers()->attach($player->id, [
                    'status' => 'approved',
                    'payment_status' => 'paid'
                ]);
                
                $this->players[] = $player;
            }
        }
    }

    /**
     * Test complete tournament flow from community to national level
     */
    public function test_complete_tournament_flow()
    {
        Sanctum::actingAs($this->admin);
        
        // Step 1: Initialize community level matches
        $this->initializeCommunityLevel();
        
        // Step 2: Simulate community matches
        $this->simulateCommunityMatches();
        
        // Step 3: Initialize county level
        $this->initializeCountyLevel();
        
        // Step 4: Simulate county matches
        $this->simulateCountyMatches();
        
        // Step 5: Initialize regional level
        $this->initializeRegionalLevel();
        
        // Step 6: Simulate regional matches
        $this->simulateRegionalMatches();
        
        // Step 7: Initialize national level
        $this->initializeNationalLevel();
        
        // Step 8: Simulate national matches
        $this->simulateNationalMatches();
        
        // Step 9: Verify tournament completion
        $this->verifyTournamentCompletion();
    }

    private function initializeCommunityLevel()
    {
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
        
        // Verify matches created for each community
        foreach ($this->communities as $community) {
            $matches = PoolMatch::where('tournament_id', $this->tournament->id)
                ->where('level', 'community')
                ->where('group_id', $community->id)
                ->get();
            
            // With 8 players, expect 4 matches in first round
            $this->assertCount(4, $matches);
        }
        
        $this->tournament->refresh();
        $this->assertEquals('ongoing', $this->tournament->status);
    }

    private function simulateCommunityMatches()
    {
        $communities = $this->communities;
        
        foreach ($communities as $community) {
            $this->simulateMatchesForGroup('community', $community->id);
        }
        
        // Verify 3 winners per community
        foreach ($communities as $community) {
            $winners = Winner::where('tournament_id', $this->tournament->id)
                ->where('level', 'community')
                ->where('level_id', $community->id)
                ->get();
            
            $this->assertCount(3, $winners, "Community {$community->name} should have 3 winners");
        }
    }

    private function simulateMatchesForGroup($level, $groupId)
    {
        $maxRounds = 5; // Safety limit
        $currentRound = 1;
        
        while ($currentRound <= $maxRounds) {
            $pendingMatches = PoolMatch::where('tournament_id', $this->tournament->id)
                ->where('level', $level)
                ->where('group_id', $groupId)
                ->whereIn('status', ['pending', 'scheduled'])
                ->get();
            
            if ($pendingMatches->isEmpty()) {
                // Check if level is complete
                $completion = $this->matchService->checkLevelCompletion(
                    $this->tournament->id,
                    $level,
                    $groupId
                );
                
                if ($completion['completed']) {
                    break;
                }
                
                // Generate next round if not complete
                $activeMatches = PoolMatch::where('tournament_id', $this->tournament->id)
                    ->where('level', $level)
                    ->where('group_id', $groupId)
                    ->where('status', 'completed')
                    ->where('round_name', "Round {$currentRound}")
                    ->get();
                
                $winners = User::whereIn('id', $activeMatches->pluck('winner_id')->filter())->get();
                
                if ($winners->count() > 3) {
                    // Need another round
                    $this->matchService->generateNextRound($this->tournament, $level, $groupId);
                    $currentRound++;
                } else {
                    break;
                }
            }
            
            // Simulate match results
            foreach ($pendingMatches as $match) {
                if ($match->bye_player_id) {
                    // Bye match already completed with no winner
                    continue;
                }
                
                // Simulate date selection
                $match->proposed_dates = [
                    now()->addDays(1)->format('Y-m-d'),
                    now()->addDays(2)->format('Y-m-d'),
                    now()->addDays(3)->format('Y-m-d'),
                ];
                $match->player_1_preferred_dates = $match->proposed_dates;
                $match->player_2_preferred_dates = [now()->addDays(2)->format('Y-m-d')];
                $match->scheduled_date = now()->addDays(2);
                $match->status = 'scheduled';
                $match->save();
                
                // Simulate match play with random scores
                $player1Points = rand(50, 100);
                $player2Points = rand(50, 100);
                
                // Ensure no tie
                if ($player1Points == $player2Points) {
                    $player1Points++;
                }
                
                $match->player_1_points = $player1Points;
                $match->player_2_points = $player2Points;
                $match->winner_id = $player1Points > $player2Points 
                    ? $match->player_1_id 
                    : $match->player_2_id;
                $match->status = 'completed';
                $match->save();
            }
        }
        
        // Store top 3 winners for this level/group
        $this->storeTopWinners($level, $groupId);
    }

    private function storeTopWinners($level, $groupId)
    {
        // Get all completed matches for this level/group
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', $level)
            ->where('group_id', $groupId)
            ->where('status', 'completed')
            ->get();
        
        // Calculate player standings
        $standings = [];
        foreach ($matches as $match) {
            if ($match->winner_id) {
                if (!isset($standings[$match->winner_id])) {
                    $standings[$match->winner_id] = 0;
                }
                $standings[$match->winner_id]++;
            }
        }
        
        // Sort by wins and take top 3
        arsort($standings);
        $topPlayers = array_slice(array_keys($standings), 0, 3, true);
        
        // Store winners
        $position = 1;
        foreach ($topPlayers as $playerId) {
            Winner::create([
                'player_id' => $playerId,
                'position' => $position,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $this->tournament->id,
                'prize_awarded' => false
            ]);
            $position++;
        }
    }

    private function initializeCountyLevel()
    {
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
        
        // Verify matches created for each county
        foreach ($this->counties as $county) {
            $matches = PoolMatch::where('tournament_id', $this->tournament->id)
                ->where('level', 'county')
                ->where('group_id', $county->id)
                ->get();
            
            // With 3 communities × 3 winners = 9 players per county
            $this->assertGreaterThan(0, $matches->count());
        }
    }

    private function simulateCountyMatches()
    {
        foreach ($this->counties as $county) {
            $this->simulateMatchesForGroup('county', $county->id);
        }
        
        // Verify 3 winners per county
        foreach ($this->counties as $county) {
            $winners = Winner::where('tournament_id', $this->tournament->id)
                ->where('level', 'county')
                ->where('level_id', $county->id)
                ->get();
            
            $this->assertCount(3, $winners, "County {$county->name} should have 3 winners");
        }
    }

    private function initializeRegionalLevel()
    {
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
        
        // Verify matches created for the region
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('group_id', $this->region->id)
            ->get();
        
        // With 2 counties × 3 winners = 6 players
        $this->assertGreaterThan(0, $matches->count());
    }

    private function simulateRegionalMatches()
    {
        $this->simulateMatchesForGroup('regional', $this->region->id);
        
        // Verify 3 regional winners
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->where('level_id', $this->region->id)
            ->get();
        
        $this->assertCount(3, $winners, "Region should have 3 winners");
    }

    private function initializeNationalLevel()
    {
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
        
        // Verify matches created for national level
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        // With potentially multiple regions contributing winners
        $this->assertGreaterThan(0, $matches->count());
    }

    private function simulateNationalMatches()
    {
        $this->simulateMatchesForGroup('national', null);
        
        // Verify 3 national winners
        $winners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        $this->assertCount(3, $winners, "Should have 3 national winners");
    }

    private function verifyTournamentCompletion()
    {
        // Run the completion check job
        $job = new CheckTournamentCompletion($this->tournament->id);
        $job->handle($this->matchService);
        
        // Refresh tournament
        $this->tournament->refresh();
        
        // Check tournament is completed
        $this->assertEquals('completed', $this->tournament->status);
        
        // Verify winners at all levels
        $levels = ['community', 'county', 'regional', 'national'];
        foreach ($levels as $level) {
            $winners = Winner::where('tournament_id', $this->tournament->id)
                ->where('level', $level)
                ->get();
            
            $this->assertGreaterThan(0, $winners->count(), "Should have winners at {$level} level");
        }
        
        // Check prizes are awarded
        $nationalWinners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        foreach ($nationalWinners as $winner) {
            $this->assertTrue($winner->prize_awarded);
            $this->assertGreaterThan(0, $winner->prize_amount);
        }
        
        // Check notifications sent
        $notifications = Notification::where('type', 'tournament_complete')->count();
        $this->assertGreaterThan(0, $notifications);
    }

    /**
     * Test automatic tournament progression
     */
    public function test_automatic_tournament_progression()
    {
        Queue::fake();
        
        // Set tournament to automatic mode
        $this->tournament->update(['automation_mode' => 'automatic']);
        
        Sanctum::actingAs($this->admin);
        
        // Initialize community level
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Simulate a community completing all matches
        $community = $this->communities[0];
        $this->simulateMatchesForGroup('community', $community->id);
        
        // Trigger completion check
        $job = new CheckTournamentCompletion($this->tournament->id);
        $job->handle($this->matchService);
        
        // Verify job was dispatched for next level
        Queue::assertPushed(CheckTournamentCompletion::class);
    }

    /**
     * Test bye handling
     */
    public function test_bye_match_handling()
    {
        // Create tournament with odd number of players
        $community = $this->communities[0];
        
        // Remove one player to make it odd
        $this->tournament->registeredUsers()->detach($this->players[0]->id);
        
        Sanctum::actingAs($this->admin);
        
        // Initialize with 7 players (odd number)
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Check bye match was created
        $byeMatch = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->whereNotNull('bye_player_id')
            ->first();
        
        $this->assertNotNull($byeMatch);
        $this->assertNull($byeMatch->winner_id); // Bye player should lose
        $this->assertEquals('completed', $byeMatch->status);
    }

    /**
     * Test match result submission and confirmation flow
     */
    public function test_match_result_submission_flow()
    {
        Sanctum::actingAs($this->admin);
        
        // Initialize tournament
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        // Get a match
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->whereNull('bye_player_id')
            ->first();
        
        // Player 1 submits results
        Sanctum::actingAs(User::find($match->player_1_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/submit-results", [
            'player_1_points' => 75,
            'player_2_points' => 60
        ]);
        
        $response->assertStatus(200);
        $match->refresh();
        $this->assertEquals('pending_confirmation', $match->status);
        
        // Player 2 confirms results
        Sanctum::actingAs(User::find($match->player_2_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/confirm-results", [
            'confirm' => true
        ]);
        
        $response->assertStatus(200);
        $match->refresh();
        $this->assertEquals('completed', $match->status);
        $this->assertEquals($match->player_1_id, $match->winner_id);
    }

    /**
     * Test date selection and scheduling
     */
    public function test_match_date_selection()
    {
        Sanctum::actingAs($this->admin);
        
        // Initialize tournament
        $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        // Get a match
        $match = PoolMatch::where('tournament_id', $this->tournament->id)
            ->whereNull('bye_player_id')
            ->first();
        
        // Player 1 proposes dates
        Sanctum::actingAs(User::find($match->player_1_id));
        
        $proposedDates = [
            now()->addDays(1)->format('Y-m-d'),
            now()->addDays(2)->format('Y-m-d'),
            now()->addDays(3)->format('Y-m-d'),
        ];
        
        $response = $this->postJson("/api/matches/{$match->id}/propose-dates", [
            'dates' => $proposedDates
        ]);
        
        $response->assertStatus(200);
        
        // Player 2 selects preferred dates
        Sanctum::actingAs(User::find($match->player_2_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/select-dates", [
            'dates' => [$proposedDates[1]] // Select second date
        ]);
        
        $response->assertStatus(200);
        
        // Player 1 also selects dates
        Sanctum::actingAs(User::find($match->player_1_id));
        
        $response = $this->postJson("/api/matches/{$match->id}/select-dates", [
            'dates' => [$proposedDates[1], $proposedDates[2]]
        ]);
        
        $response->assertStatus(200);
        
        $match->refresh();
        $this->assertEquals('scheduled', $match->status);
        $this->assertEquals($proposedDates[1], $match->scheduled_date);
    }

    /**
     * Test admin tournament statistics
     */
    public function test_tournament_statistics()
    {
        Sanctum::actingAs($this->admin);
        
        // Initialize and simulate some matches
        $this->initializeCommunityLevel();
        
        // Simulate some community matches
        $community = $this->communities[0];
        $matches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'community')
            ->where('group_id', $community->id)
            ->limit(2)
            ->get();
        
        foreach ($matches as $match) {
            if (!$match->bye_player_id) {
                $match->update([
                    'status' => 'completed',
                    'player_1_points' => 80,
                    'player_2_points' => 70,
                    'winner_id' => $match->player_1_id
                ]);
            }
        }
        
        // Get statistics
        $response = $this->getJson("/api/admin/tournaments/{$this->tournament->id}/statistics");
        
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
        
        $data = $response->json();
        $this->assertEquals(48, $data['approved_players']);
        $this->assertGreaterThan(0, $data['total_matches']);
        $this->assertGreaterThan(0, $data['completed_matches']);
    }
}
