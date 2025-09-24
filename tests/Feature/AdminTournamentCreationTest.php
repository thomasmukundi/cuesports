<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AdminTournamentCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $regularUser;
    protected $region;
    protected $county;
    protected $community;

    protected function setUp(): void
    {
        parent::setUp();
        
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
        
        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'test-admin@cuesports.com',
            'community_id' => $this->community->id,
            'county_id' => $this->county->id,
            'region_id' => $this->region->id,
        ]);
        
        // Create regular user
        $this->regularUser = User::factory()->create([
            'email' => 'regular-user@test.com',
            'community_id' => $this->community->id,
            'county_id' => $this->county->id,
            'region_id' => $this->region->id,
        ]);
    }

    public function test_admin_can_authenticate()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user',
            'access_token'
        ]);
    }

    public function test_admin_is_recognized_as_admin()
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertFalse($this->regularUser->isAdmin());
    }

    public function test_admin_can_create_tournament()
    {
        Sanctum::actingAs($this->admin);
        
        $tournamentData = [
            'name' => 'Test Tournament',
            'special' => false,
            'community_prize' => 1000,
            'county_prize' => 2000,
            'regional_prize' => 3000,
            'national_prize' => 5000,
            'area_scope' => 'national',
            'area_name' => 'Kenya',
            'tournament_charge' => 100,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(14)->format('Y-m-d'),
            'registration_deadline' => now()->addDays(3)->format('Y-m-d'),
            'automation_mode' => 'automatic'
        ];
        
        $response = $this->postJson('/api/admin/tournaments', $tournamentData);
        
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'tournament' => [
                'id',
                'name',
                'start_date',
                'end_date'
            ]
        ]);
        
        $this->assertDatabaseHas('tournaments', [
            'name' => 'Test Tournament',
            'status' => 'upcoming'
        ]);
    }

    public function test_regular_user_cannot_create_tournament()
    {
        Sanctum::actingAs($this->regularUser);
        
        $tournamentData = [
            'name' => 'Test Tournament',
            'special' => false,
            'tournament_charge' => 100,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(14)->format('Y-m-d'),
            'automation_mode' => 'automatic'
        ];
        
        $response = $this->postJson('/api/admin/tournaments', $tournamentData);
        
        $response->assertStatus(403);
    }


    public function test_admin_can_list_tournaments()
    {
        Sanctum::actingAs($this->admin);
        
        // Create test tournaments
        Tournament::factory()->create([
            'name' => 'Tournament 1',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'upcoming'
        ]);
        
        Tournament::factory()->create([
            'name' => 'Tournament 2', 
            'start_date' => now()->addDays(15),
            'end_date' => now()->addDays(20),
            'status' => 'ongoing'
        ]);
        
        
        $response = $this->getJson('/api/admin/tournaments');
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'status',
                    'start_date',
                    'end_date'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_tournament_details()
    {
        Sanctum::actingAs($this->admin);
        
        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'special' => false,
            'tournament_charge' => 50,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'upcoming',
            'automation_mode' => 'automatic'
        ]);
        
        $response = $this->getJson("/api/admin/tournaments/{$tournament->id}");
        
        $response->assertStatus(200);
        $response->assertJson([
            'tournament' => [
                'id' => $tournament->id,
                'name' => 'Test Tournament'
            ]
        ]);
    }
}
