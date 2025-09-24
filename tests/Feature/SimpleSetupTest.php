<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\County;
use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_model_creation()
    {
        // Test basic model creation
        $region = Region::create(['name' => 'Test Region']);
        $this->assertNotNull($region->id);

        $county = County::create([
            'name' => 'Test County',
            'region_id' => $region->id
        ]);
        $this->assertNotNull($county->id);

        $community = Community::create([
            'name' => 'Test Community',
            'county_id' => $county->id,
            'region_id' => $region->id
        ]);
        $this->assertNotNull($community->id);

        $user = User::factory()->create([
            'community_id' => $community->id,
            'county_id' => $county->id,
            'region_id' => $region->id
        ]);
        $this->assertNotNull($user->id);

        echo "Basic model creation successful!\n";
        echo "Region: {$region->name}\n";
        echo "County: {$county->name}\n";
        echo "Community: {$community->name}\n";
        echo "User: {$user->name}\n";
    }
}
