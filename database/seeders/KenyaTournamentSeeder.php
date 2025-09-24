<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class KenyaTournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        DB::table('registered_users')->truncate();
        DB::table('matches')->truncate();
        DB::table('notifications')->truncate();
        DB::table('chat_messages')->truncate();
        DB::table('users')->truncate();
        DB::table('communities')->truncate();
        DB::table('counties')->truncate();
        DB::table('regions')->truncate();
        DB::table('tournaments')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create regions
        $regions = $this->createRegions();
        
        // Create counties
        $counties = $this->createCounties($regions);
        
        // Create communities
        $communities = $this->createCommunities($counties);
        
        // Create users
        $users = $this->createUsers($communities);
        
        // Create tournaments
        $tournaments = $this->createTournaments();
        
        // Register users for tournaments
        $this->registerUsersForTournaments($users, $tournaments);
        
        // Output statistics
        $this->outputStatistics();
    }

    private function createRegions(): array
    {
        $regionData = [
            ['name' => 'Nairobi'],
            ['name' => 'Central'],
            ['name' => 'Coast'],
            ['name' => 'Eastern'],
            ['name' => 'North Eastern'],
            ['name' => 'Nyanza'],
            ['name' => 'Rift Valley'],
            ['name' => 'Western'],
        ];

        $regions = [];
        foreach ($regionData as $data) {
            $regions[] = DB::table('regions')->insertGetId([
                'name' => $data['name'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return $regions;
    }

    private function createCounties($regions): array
    {
        $countyData = [
            // Nairobi Region
            ['name' => 'Nairobi', 'region_id' => $regions[0]],
            
            // Central Region
            ['name' => 'Kiambu', 'region_id' => $regions[1]],
            ['name' => 'Murang\'a', 'region_id' => $regions[1]],
            ['name' => 'Nyeri', 'region_id' => $regions[1]],
            ['name' => 'Kirinyaga', 'region_id' => $regions[1]],
            ['name' => 'Nyandarua', 'region_id' => $regions[1]],
            
            // Coast Region
            ['name' => 'Mombasa', 'region_id' => $regions[2]],
            ['name' => 'Kwale', 'region_id' => $regions[2]],
            ['name' => 'Kilifi', 'region_id' => $regions[2]],
            ['name' => 'Tana River', 'region_id' => $regions[2]],
            ['name' => 'Lamu', 'region_id' => $regions[2]],
            ['name' => 'Taita Taveta', 'region_id' => $regions[2]],
            
            // Eastern Region
            ['name' => 'Marsabit', 'region_id' => $regions[3]],
            ['name' => 'Isiolo', 'region_id' => $regions[3]],
            ['name' => 'Meru', 'region_id' => $regions[3]],
            ['name' => 'Tharaka-Nithi', 'region_id' => $regions[3]],
            ['name' => 'Embu', 'region_id' => $regions[3]],
            ['name' => 'Kitui', 'region_id' => $regions[3]],
            ['name' => 'Machakos', 'region_id' => $regions[3]],
            ['name' => 'Makueni', 'region_id' => $regions[3]],
            
            // North Eastern Region
            ['name' => 'Garissa', 'region_id' => $regions[4]],
            ['name' => 'Wajir', 'region_id' => $regions[4]],
            ['name' => 'Mandera', 'region_id' => $regions[4]],
            
            // Nyanza Region
            ['name' => 'Siaya', 'region_id' => $regions[5]],
            ['name' => 'Kisumu', 'region_id' => $regions[5]],
            ['name' => 'Homa Bay', 'region_id' => $regions[5]],
            ['name' => 'Migori', 'region_id' => $regions[5]],
            ['name' => 'Kisii', 'region_id' => $regions[5]],
            ['name' => 'Nyamira', 'region_id' => $regions[5]],
            
            // Rift Valley Region
            ['name' => 'Turkana', 'region_id' => $regions[6]],
            ['name' => 'West Pokot', 'region_id' => $regions[6]],
            ['name' => 'Samburu', 'region_id' => $regions[6]],
            ['name' => 'Trans-Nzoia', 'region_id' => $regions[6]],
            ['name' => 'Uasin Gishu', 'region_id' => $regions[6]],
            ['name' => 'Elgeyo-Marakwet', 'region_id' => $regions[6]],
            ['name' => 'Nandi', 'region_id' => $regions[6]],
            ['name' => 'Baringo', 'region_id' => $regions[6]],
            ['name' => 'Laikipia', 'region_id' => $regions[6]],
            ['name' => 'Nakuru', 'region_id' => $regions[6]],
            ['name' => 'Narok', 'region_id' => $regions[6]],
            ['name' => 'Kajiado', 'region_id' => $regions[6]],
            ['name' => 'Kericho', 'region_id' => $regions[6]],
            ['name' => 'Bomet', 'region_id' => $regions[6]],
            
            // Western Region
            ['name' => 'Kakamega', 'region_id' => $regions[7]],
            ['name' => 'Vihiga', 'region_id' => $regions[7]],
            ['name' => 'Bungoma', 'region_id' => $regions[7]],
            ['name' => 'Busia', 'region_id' => $regions[7]],
        ];

        $counties = [];
        foreach ($countyData as $data) {
            $counties[] = DB::table('counties')->insertGetId([
                'name' => $data['name'],
                'region_id' => $data['region_id'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return $counties;
    }

    private function createCommunities($counties): array
    {
        $communities = [];
        
        foreach ($counties as $countyId) {
            $county = DB::table('counties')->find($countyId);
            $numCommunities = rand(1, 10); // Random number of communities per county
            
            for ($i = 1; $i <= $numCommunities; $i++) {
                $communities[] = DB::table('communities')->insertGetId([
                    'name' => $county->name . ' Community ' . $i,
                    'county_id' => $countyId,
                    'region_id' => $county->region_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        return $communities;
    }

    

    private function createTournaments(): array
    {
        $tournaments = [];

        // National Championship (Hierarchical)
        $tournaments[] = DB::table('tournaments')->insertGetId([
            'name' => 'Kenya National Championship 2024',
            'special' => false,
            'community_prize' => 50000,
            'county_prize' => 100000,
            'regional_prize' => 250000,
            'national_prize' => 500000,
            'tournament_charge' => 500,
            'status' => 'upcoming',
            'automation_mode' => 'manual',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Special Tournament (Flat)
        $tournaments[] = DB::table('tournaments')->insertGetId([
            'name' => 'Kenya Pool Masters Special Tournament',
            'special' => true,
            'community_prize' => 0,
            'county_prize' => 0,
            'regional_prize' => 0,
            'national_prize' => 1000000,
            'tournament_charge' => 1000,
            'status' => 'upcoming',
            'automation_mode' => 'automatic',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $tournaments;
    }

    private function registerUsersForTournaments($users, $tournaments): void
    {
        // Register 800 random users for National Championship
        $selectedUsers = array_rand($users, min(800, count($users)));
        if (!is_array($selectedUsers)) {
            $selectedUsers = [$selectedUsers];
        }

        foreach ($selectedUsers as $userIndex) {
            DB::table('registered_users')->insert([
                'player_id' => $users[$userIndex],
                'tournament_id' => $tournaments[0],
                'status' => 'approved',
                'payment_status' => rand(1, 10) == 1 ? 'pending' : 'paid',
                'payment_id' => 'pi_' . md5($users[$userIndex] . '_tournament_1'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Register top 200 players for Special Tournament
        $topPlayers = DB::table('users')
            ->where('email', '!=', 'admin@cuesports.com')
            ->where('points', '>=', 1500)
            ->orderBy('points', 'desc')
            ->limit(200)
            ->pluck('id');

        foreach ($topPlayers as $playerId) {
            DB::table('registered_users')->insert([
                'player_id' => $playerId,
                'tournament_id' => $tournaments[1],
                'status' => 'approved',
                'payment_status' => 'paid',
                'payment_id' => 'pi_' . md5($playerId . '_tournament_2'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    private function outputStatistics(): void
    {
        $this->command->info('Database seeding complete!');
        $this->command->info('');
        $this->command->info('Statistics:');
        $this->command->info('Regions: ' . DB::table('regions')->count());
        $this->command->info('Counties: ' . DB::table('counties')->count());
        $this->command->info('Communities: ' . DB::table('communities')->count());
        $this->command->info('Users: ' . DB::table('users')->count());
        $this->command->info('Tournaments: ' . DB::table('tournaments')->count());
        $this->command->info('Tournament 1 Registrations: ' . DB::table('registered_users')->where('tournament_id', 1)->count());
        $this->command->info('Tournament 2 Registrations: ' . DB::table('registered_users')->where('tournament_id', 2)->count());
        $this->command->info('');
        
        // Show user distribution by region
        $this->command->info('User Distribution by Region:');
        $regions = DB::table('regions')
            ->leftJoin('users', 'users.region_id', '=', 'regions.id')
            ->select('regions.name', DB::raw('COUNT(users.id) as user_count'))
            ->groupBy('regions.id', 'regions.name')
            ->orderBy('user_count', 'desc')
            ->get();
        
        foreach ($regions as $region) {
            $this->command->info($region->name . ': ' . $region->user_count . ' users');
        }
    }
}
