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

class CountyLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_county_level_progression()
    {
        try {
            // Create basic structure - 1 county with 2 communities
            $region = Region::create(['name' => 'Test Region']);
            $county = County::create(['name' => 'Test County', 'region_id' => $region->id]);
            
            $community1 = Community::create([
                'name' => 'Community 1',
                'county_id' => $county->id,
                'region_id' => $region->id
            ]);
            
            $community2 = Community::create([
                'name' => 'Community 2', 
                'county_id' => $county->id,
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

            // Create community winners (3 winners per community)
            $communityWinners = [];
            
            // Community 1 winners
            for ($i = 1; $i <= 3; $i++) {
                $player = User::factory()->create([
                    'community_id' => $community1->id,
                    'county_id' => $county->id,
                    'region_id' => $region->id
                ]);
                
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'level' => 'community',
                    'group_id' => $community1->id,
                    'position' => $i
                ]);
                
                $communityWinners[] = $player;
            }
            
            // Community 2 winners
            for ($i = 1; $i <= 3; $i++) {
                $player = User::factory()->create([
                    'community_id' => $community2->id,
                    'county_id' => $county->id,
                    'region_id' => $region->id
                ]);
                
                Winner::create([
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                    'level' => 'community',
                    'group_id' => $community2->id,
                    'position' => $i
                ]);
                
                $communityWinners[] = $player;
            }

            echo "Community winners created: " . count($communityWinners) . "\n";
            
            // Verify winners exist
            $winners = Winner::where('tournament_id', $tournament->id)
                ->where('level', 'community')
                ->get();
            echo "Winners in database: " . $winners->count() . "\n";

            // Test county level initialization
            $matchService = new MatchAlgorithmService();
            $result = $matchService->initialize($tournament->id, 'county');

            echo "County initialization result: " . json_encode($result) . "\n";

            // Check if county matches were created
            $countyMatches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', 'county')
                ->where('group_id', $county->id)
                ->get();

            echo "County matches created: " . $countyMatches->count() . "\n";
            $this->assertGreaterThan(0, $countyMatches->count());

            // Verify match details
            foreach ($countyMatches as $match) {
                echo "County Match: {$match->match_name}, Round: {$match->round_name}, Players: {$match->player_1_id} vs {$match->player_2_id}\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            throw $e;
        }
    }
}
