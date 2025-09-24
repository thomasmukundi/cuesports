<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use App\Models\Region;
use App\Models\County;
use App\Models\Community;
use App\Models\PoolMatch;
use App\Models\Winner;
use App\Services\MatchAlgorithmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComprehensiveTournamentTest extends TestCase
{
    use RefreshDatabase;

    private Tournament $tournament;
    private User $admin;
    private array $regions = [];
    private array $counties = [];
    private array $communities = [];
    private array $players = [];
    private MatchAlgorithmService $matchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matchService = app(MatchAlgorithmService::class);
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        // Create 2 regions
        $this->regions = [
            Region::create(['name' => 'Test Region North']),
            Region::create(['name' => 'Test Region South'])
        ];

        // Create 2-3 counties per region
        foreach ($this->regions as $regionIndex => $region) {
            $countyCount = $regionIndex === 0 ? 2 : 3; // First region: 2 counties, Second: 3 counties
            
            for ($c = 1; $c <= $countyCount; $c++) {
                $county = County::create([
                    'name' => "County {$region->name} {$c}",
                    'region_id' => $region->id
                ]);
                $this->counties[] = $county;

                // Create 2-3 communities per county
                $communityCount = ($c % 2 === 0) ? 3 : 2; // Alternate between 2 and 3 communities
                
                for ($com = 1; $com <= $communityCount; $com++) {
                    $community = Community::create([
                        'name' => "Community {$county->name} {$com}",
                        'county_id' => $county->id,
                        'region_id' => $region->id
                    ]);
                    $this->communities[] = $community;

                    // Create 2-7 players per community
                    $playerCount = rand(2, 7);
                    
                    for ($p = 1; $p <= $playerCount; $p++) {
                        $uniqueId = microtime(true) . rand(10000, 99999) . $p;
                        $player = User::factory()->create([
                            'community_id' => $community->id,
                            'county_id' => $county->id,
                            'region_id' => $region->id,
                            'total_points' => rand(500, 2000)
                        ]);
                        $this->players[] = $player;
                    }
                }
            }
        }

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin-' . microtime(true) . '@cuesports.com',
            'community_id' => $this->communities[0]->id,
            'county_id' => $this->counties[0]->id,
            'region_id' => $this->regions[0]->id,
            'total_points' => 5000
        ]);

        // Create tournament
        $this->tournament = Tournament::create([
            'name' => 'Test Tournament',
            'special' => false,
            'tournament_charge' => 100,
            'community_prize' => 1000,
            'county_prize' => 2000,
            'regional_prize' => 5000,
            'national_prize' => 10000,
            'status' => 'upcoming',
            'automation_mode' => 'manual'
        ]);

        // Register all players for the tournament
        foreach ($this->players as $player) {
            $this->tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'paid'
            ]);
        }
    }

    public function test_tournament_data_setup()
    {
        // Verify test data structure
        $this->assertCount(2, $this->regions);
        $this->assertCount(5, $this->counties); // 2 + 3 counties
        $this->assertTrue(count($this->communities) >= 10); // At least 2*2 + 3*2 = 10 communities
        $this->assertTrue(count($this->players) >= 20); // At least 2 players per community
        
        // Verify tournament registration
        $approvedPlayers = $this->tournament->approvedPlayers;
        $this->assertEquals(count($this->players), $approvedPlayers->count());
        
        echo "\n=== Tournament Test Data Summary ===\n";
        echo "Regions: " . count($this->regions) . "\n";
        echo "Counties: " . count($this->counties) . "\n";
        echo "Communities: " . count($this->communities) . "\n";
        echo "Players: " . count($this->players) . "\n";
        echo "Registered Players: " . $approvedPlayers->count() . "\n";
        
        foreach ($this->regions as $region) {
            $regionCounties = collect($this->counties)->where('region_id', $region->id);
            echo "\n{$region->name}: {$regionCounties->count()} counties\n";
            
            foreach ($regionCounties as $county) {
                $countyCommunities = collect($this->communities)->where('county_id', $county->id);
                echo "  {$county->name}: {$countyCommunities->count()} communities\n";
                
                foreach ($countyCommunities as $community) {
                    $communityPlayers = collect($this->players)->where('community_id', $community->id);
                    echo "    {$community->name}: {$communityPlayers->count()} players\n";
                }
            }
        }
    }

    public function test_community_level_initialization_and_completion()
    {
        Sanctum::actingAs($this->admin);
        
        // Initialize community level
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'community'
        ]);
        
        $response->assertStatus(200);
        
        // Verify matches were created for each community
        foreach ($this->communities as $community) {
            $communityPlayers = collect($this->players)->where('community_id', $community->id);
            $matches = PoolMatch::where('tournament_id', $this->tournament->id)
                ->where('level', 'community')
                ->where('group_id', $community->id)
                ->get();
            
            if ($communityPlayers->count() >= 2) {
                $this->assertGreaterThan(0, $matches->count(), 
                    "Community {$community->name} should have matches");
                
                echo "\nCommunity {$community->name}: {$communityPlayers->count()} players, {$matches->count()} matches\n";
                
                // Simulate completing all matches in this community
                $this->completeAllMatches($matches);
                
                // Check if winners were determined
                $winners = Winner::where('tournament_id', $this->tournament->id)
                    ->where('level', 'community')
                    ->where('level_id', $community->id)
                    ->get();
                
                $this->assertGreaterThan(0, $winners->count(), 
                    "Community {$community->name} should have winners");
                
                echo "  Winners: {$winners->count()}\n";
            }
        }
    }

    public function test_county_level_progression()
    {
        // First complete community level
        $this->test_community_level_initialization_and_completion();
        
        Sanctum::actingAs($this->admin);
        
        // Initialize county level
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'county'
        ]);
        
        $response->assertStatus(200);
        
        // Verify county matches were created
        foreach ($this->counties as $county) {
            $matches = PoolMatch::where('tournament_id', $this->tournament->id)
                ->where('level', 'county')
                ->where('group_id', $county->id)
                ->get();
            
            // Get community winners from this county
            $communityWinners = Winner::where('tournament_id', $this->tournament->id)
                ->where('level', 'community')
                ->whereIn('level_id', collect($this->communities)
                    ->where('county_id', $county->id)
                    ->pluck('id'))
                ->whereIn('position', [1, 2, 3])
                ->count();
            
            if ($communityWinners >= 2) {
                $this->assertGreaterThan(0, $matches->count(), 
                    "County {$county->name} should have matches");
                
                echo "\nCounty {$county->name}: {$communityWinners} community winners, {$matches->count()} matches\n";
                
                // Complete county matches
                $this->completeAllMatches($matches);
                
                // Verify county winners
                $countyWinners = Winner::where('tournament_id', $this->tournament->id)
                    ->where('level', 'county')
                    ->where('level_id', $county->id)
                    ->get();
                
                $this->assertGreaterThan(0, $countyWinners->count());
                echo "  County Winners: {$countyWinners->count()}\n";
            }
        }
    }

    public function test_regional_level_progression()
    {
        // Complete previous levels
        $this->test_county_level_progression();
        
        Sanctum::actingAs($this->admin);
        
        // Initialize regional level
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'regional'
        ]);
        
        $response->assertStatus(200);
        
        // Verify regional matches
        foreach ($this->regions as $region) {
            $matches = PoolMatch::where('tournament_id', $this->tournament->id)
                ->where('level', 'regional')
                ->where('group_id', $region->id)
                ->get();
            
            $countyWinners = Winner::where('tournament_id', $this->tournament->id)
                ->where('level', 'county')
                ->whereIn('level_id', collect($this->counties)
                    ->where('region_id', $region->id)
                    ->pluck('id'))
                ->whereIn('position', [1, 2, 3])
                ->count();
            
            if ($countyWinners >= 2) {
                $this->assertGreaterThan(0, $matches->count());
                
                echo "\nRegion {$region->name}: {$countyWinners} county winners, {$matches->count()} matches\n";
                
                // Complete regional matches
                $this->completeAllMatches($matches);
                
                // Verify regional winners
                $regionalWinners = Winner::where('tournament_id', $this->tournament->id)
                    ->where('level', 'regional')
                    ->where('level_id', $region->id)
                    ->get();
                
                $this->assertGreaterThan(0, $regionalWinners->count());
                echo "  Regional Winners: {$regionalWinners->count()}\n";
            }
        }
    }

    public function test_national_level_final()
    {
        // Complete all previous levels
        $this->test_regional_level_progression();
        
        Sanctum::actingAs($this->admin);
        
        // Initialize national level
        $response = $this->postJson("/api/admin/tournaments/{$this->tournament->id}/initialize", [
            'level' => 'national'
        ]);
        
        $response->assertStatus(200);
        
        // Verify national matches
        $nationalMatches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('level', 'national')
            ->get();
        
        $regionalWinners = Winner::where('tournament_id', $this->tournament->id)
            ->where('level', 'regional')
            ->whereIn('position', [1, 2, 3])
            ->count();
        
        if ($regionalWinners >= 2) {
            $this->assertGreaterThan(0, $nationalMatches->count());
            
            echo "\nNational Level: {$regionalWinners} regional winners, {$nationalMatches->count()} matches\n";
            
            // Complete national matches
            $this->completeAllMatches($nationalMatches);
            
            // Verify national winners (tournament champions)
            $nationalWinners = Winner::where('tournament_id', $this->tournament->id)
                ->where('level', 'national')
                ->get();
            
            $this->assertGreaterThan(0, $nationalWinners->count());
            echo "  National Champions: {$nationalWinners->count()}\n";
            
            // Display final tournament results
            $this->displayTournamentResults();
        }
    }

    private function completeAllMatches($matches)
    {
        if ($matches->isEmpty()) {
            return;
        }
        
        $matchService = new \App\Services\MatchAlgorithmService();
        
        foreach ($matches as $match) {
            // Simulate match completion with random results
            $player1Points = rand(0, 10);
            $player2Points = rand(0, 10);
            
            // Ensure there's a winner
            if ($player1Points === $player2Points) {
                $player1Points += 1;
            }
            
            $match->update([
                'player_1_points' => $player1Points,
                'player_2_points' => $player2Points,
                'winner_id' => $player1Points > $player2Points ? $match->player_1_id : $match->player_2_id,
                'status' => 'completed'
            ]);
        }
        
        // After completing all matches in a group, determine winners
        $firstMatch = $matches->first();
        echo "  Checking completion for tournament {$firstMatch->tournament_id}, level {$firstMatch->level}, group {$firstMatch->group_id}\n";
        
        // Debug: Check player count for this group
        $tournament = Tournament::find($firstMatch->tournament_id);
        $approvedPlayers = $tournament->approvedPlayers->where('community_id', $firstMatch->group_id);
        echo "  Approved players in group: {$approvedPlayers->count()}\n";
        
        // Debug: Check what round names exist
        $allMatches = PoolMatch::where('tournament_id', $firstMatch->tournament_id)
            ->where('level', $firstMatch->level)
            ->where('group_id', $firstMatch->group_id)
            ->get();
        echo "  Match round names: " . $allMatches->pluck('round_name')->implode(', ') . "\n";
        
        $result = $matchService->checkLevelCompletion($firstMatch->tournament_id, $firstMatch->level, $firstMatch->group_id);
        echo "  Completion result: " . json_encode($result) . "\n";
        
        // Verify winners were created
        $winners = Winner::where('tournament_id', $firstMatch->tournament_id)
            ->where('level', $firstMatch->level)
            ->where('level_id', $firstMatch->group_id)
            ->get();
        echo "  Winners created: {$winners->count()}\n";
    }

    private function displayTournamentResults()
    {
        echo "\n=== FINAL TOURNAMENT RESULTS ===\n";
        
        $levels = ['national', 'regional', 'county', 'community'];
        
        foreach ($levels as $level) {
            $winners = Winner::where('tournament_id', $this->tournament->id)
                ->where('level', $level)
                ->with('player')
                ->orderBy('level_id')
                ->orderBy('position')
                ->get();
            
            if ($winners->count() > 0) {
                echo "\n{$level} LEVEL WINNERS:\n";
                foreach ($winners as $winner) {
                    $groupName = $this->getGroupName($level, $winner->level_id);
                    echo "  Position {$winner->position}: {$winner->player->name} ({$groupName})\n";
                }
            }
        }
        
        echo "\n=== TOURNAMENT STATISTICS ===\n";
        $totalMatches = PoolMatch::where('tournament_id', $this->tournament->id)->count();
        $completedMatches = PoolMatch::where('tournament_id', $this->tournament->id)
            ->where('status', 'completed')->count();
        
        echo "Total Matches: {$totalMatches}\n";
        echo "Completed Matches: {$completedMatches}\n";
        echo "Tournament Status: " . ($totalMatches === $completedMatches ? 'COMPLETED' : 'IN PROGRESS') . "\n";
    }

    private function getGroupName($level, $groupId)
    {
        switch ($level) {
            case 'community':
                return Community::find($groupId)->name ?? "Community {$groupId}";
            case 'county':
                return County::find($groupId)->name ?? "County {$groupId}";
            case 'regional':
                return Region::find($groupId)->name ?? "Region {$groupId}";
            case 'national':
                return 'National';
            default:
                return "Group {$groupId}";
        }
    }
}
