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
use Tests\TestCase;

class NationalLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_national_level_final()
    {
        try {
            // Create basic structure - 2 regions
            $region1 = Region::create(['name' => 'Region 1']);
            $region2 = Region::create(['name' => 'Region 2']);
            
            // Create counties and communities for proper player assignment
            $county1 = County::create(['name' => 'County 1', 'region_id' => $region1->id]);
            $county2 = County::create(['name' => 'County 2', 'region_id' => $region2->id]);

            $community1 = Community::create([
                'name' => 'Community 1',
                'county_id' => $county1->id,
                'region_id' => $region1->id
            ]);
            
            $community2 = Community::create([
                'name' => 'Community 2',
                'county_id' => $county2->id,
                'region_id' => $region2->id
            ]);

            // Create tournament
            $tournament = Tournament::create([
                'name' => 'Test Tournament',
                'special' => false,
                'tournament_charge' => 100,
                'status' => 'upcoming',
                'automation_mode' => 'manual'
            ]);

            // Create regional winners (3 winners per region)
            $regionalWinners = [];
            
            // Region 1 winners
            for ($i = 1; $i <= 3; $i++) {
                $player = User::factory()->create([
                    'community_id' => $community1->id,
                    'county_id' => $county1->id,
                    'region_id' => $region1->id
                ]);
                
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'level' => 'regional',
                    'group_id' => $region1->id,
                    'position' => $i
                ]);
                
                $regionalWinners[] = $player;
            }
            
            // Region 2 winners
            for ($i = 1; $i <= 3; $i++) {
                $player = User::factory()->create([
                    'community_id' => $community2->id,
                    'county_id' => $county2->id,
                    'region_id' => $region2->id
                ]);
                
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'level' => 'regional',
                    'group_id' => $region2->id,
                    'position' => $i
                ]);
                
                $regionalWinners[] = $player;
            }

            echo "Regional winners created: " . count($regionalWinners) . "\n";
            
            // Verify winners exist
            $winners = Winner::where('tournament_id', $tournament->id)
                ->where('level', 'regional')
                ->get();
            echo "Regional winners in database: " . $winners->count() . "\n";

            // Test national level initialization
            $matchService = new MatchAlgorithmService();
            $result = $matchService->initialize($tournament->id, 'national');

            echo "National initialization result: " . json_encode($result) . "\n";

            // Check if national matches were created
            $nationalMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', 'national')
                ->get();

            echo "National matches created: " . $nationalMatches->count() . "\n";
            $this->assertGreaterThan(0, $nationalMatches->count());

            // Verify match details
            foreach ($nationalMatches as $match) {
                echo "National Match: {$match->match_name}, Round: {$match->round_name}, Players: {$match->player_1_id} vs {$match->player_2_id}\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            throw $e;
        }
    }
}
