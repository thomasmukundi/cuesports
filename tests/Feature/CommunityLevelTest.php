<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use App\Models\Region;
use App\Models\County;
use App\Models\Community;
use App\Models\PoolMatch;
use App\Services\MatchAlgorithmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_level_initialization()
    {
        try {
            // Create basic structure
            $region = Region::create(['name' => 'Test Region']);
            $county = County::create(['name' => 'Test County', 'region_id' => $region->id]);
            $community = Community::create([
                'name' => 'Test Community',
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

            // Create 4 players
            $players = [];
            for ($i = 1; $i <= 4; $i++) {
                $player = User::factory()->create([
                    'community_id' => $community->id,
                    'county_id' => $county->id,
                    'region_id' => $region->id
                ]);
                $players[] = $player;
                
                // Register player
                $tournament->registeredUsers()->attach($player->id, [
                    'status' => 'approved',
                    'payment_status' => 'paid'
                ]);
            }

            // Debug: Check approved players before initialization
            $approvedPlayers = $tournament->approvedPlayers;
            echo "Approved players count: " . $approvedPlayers->count() . "\n";
            
            foreach ($approvedPlayers as $player) {
                echo "Player: {$player->first_name} {$player->last_name}, Community: {$player->community_id}\n";
            }

            // Test initialization directly through service
            $matchService = new MatchAlgorithmService();
            $result = $matchService->initialize($tournament->id, 'community');

            echo "Initialization result: " . json_encode($result) . "\n";

            // Check if matches were created
            $matches = PoolMatch::where('tournament_id', $tournament->id)
                ->where('level', 'community')
                ->where('group_id', $community->id)
                ->get();

            echo "Matches created: " . $matches->count() . "\n";
            $this->assertGreaterThan(0, $matches->count());

            // Verify match details
            foreach ($matches as $match) {
                echo "Match: {$match->match_name}, Round: {$match->round_name}, Players: {$match->player_1_id} vs {$match->player_2_id}\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            throw $e;
        }
    }
}
