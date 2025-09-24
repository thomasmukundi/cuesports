<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use App\Models\Region;
use App\Models\County;
use App\Models\Community;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_tournament_setup()
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
                'status' => 'registration'
            ]);

            // Create and register a player
            $player = User::factory()->create([
                'community_id' => $community->id,
                'county_id' => $county->id,
                'region_id' => $region->id
            ]);

            $tournament->registeredUsers()->attach($player->id, [
                'status' => 'approved',
                'payment_status' => 'paid'
            ]);

            // Test approved players relationship
            $approvedPlayers = $tournament->approvedPlayers;
            echo "Approved players: " . $approvedPlayers->count() . "\n";

            $this->assertEquals(1, $approvedPlayers->count());
            echo "Basic setup successful!\n";

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n";
            throw $e;
        }
    }
}
