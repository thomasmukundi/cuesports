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

class RegionalLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_regional_level_progression()
    {
        try {
            // Create basic structure - 1 region with 2 counties
            $region = Region::create(['name' => 'Test Region']);
            
            $county1 = County::create(['name' => 'County 1', 'region_id' => $region->id]);
            $county2 = County::create(['name' => 'County 2', 'region_id' => $region->id]);

            // Create communities for proper player assignment
            $community1 = Community::create([
                'name' => 'Community 1',
                'county_id' => $county1->id,
                'region_id' => $region->id
            ]);
            
            $community2 = Community::create([
                'name' => 'Community 2',
                'county_id' => $county2->id,
                'region_id' => $region->id
            ]);

            // Create tournament
            $tournament = Tournament::create([
                'name' => 'Test Tournament',
                'special' => false,
                'tournament_charge' => 100,
                'status' => 'upcoming',
                'automation_mode' => 'manual'
            ]);

            // Create county winners (3 winners per county)
            $countyWinners = [];
            
            // County 1 winners
            for ($i = 1; $i <= 3; $i++) {
                $player = User::factory()->create([
                    'community_id' => $community1->id,
                    'county_id' => $county1->id,
                    'region_id' => $region->id
                ]);
                
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'level' => 'county',
                    'group_id' => $county1->id,
                    'position' => $i
                ]);
                
                $countyWinners[] = $player;
            }
            
            // County 2 winners
            for ($i = 1; $i <= 3; $i++) {
                $player = User::factory()->create([
                    'community_id' => $community2->id,
                    'county_id' => $county2->id,
                    'region_id' => $region->id
                ]);
                
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'level' => 'county',
                    'group_id' => $county2->id,
                    'position' => $i
                ]);
                
                $countyWinners[] = $player;
            }

            echo "County winners created: " . count($countyWinners) . "\n";
            
            // Verify winners exist
            $winners = Winner::where('tournament_id', $tournament->id)
                ->where('level', 'county')
                ->get();
            echo "County winners in database: " . $winners->count() . "\n";

            // Test regional level initialization
            $matchService = new MatchAlgorithmService();
            $result = $matchService->initialize($tournament->id, 'regional');

            echo "Regional initialization result: " . json_encode($result) . "\n";

            // Check if regional matches were created
            $regionalMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', 'regional')
                ->where('group_id', $region->id)
                ->get();

            echo "Regional matches created: " . $regionalMatches->count() . "\n";
            $this->assertGreaterThan(0, $regionalMatches->count());

            // Verify match details
            foreach ($regionalMatches as $match) {
                echo "Regional Match: {$match->match_name}, Round: {$match->round_name}, Players: {$match->player_1_id} vs {$match->player_2_id}\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            throw $e;
        }
    }
}
