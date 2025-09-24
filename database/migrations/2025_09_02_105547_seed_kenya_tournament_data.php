<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert regions
        DB::table('regions')->insert([
            ['id' => 1, 'name' => 'Central', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Coast', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Eastern', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'North Eastern', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Nyanza', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Rift Valley', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Western', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Nairobi', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Insert counties
        $counties = [
            // Central Region
            ['id' => 1, 'name' => 'Kiambu', 'region_id' => 1],
            ['id' => 2, 'name' => 'Kirinyaga', 'region_id' => 1],
            ['id' => 3, 'name' => 'Murang\'a', 'region_id' => 1],
            ['id' => 4, 'name' => 'Nyandarua', 'region_id' => 1],
            ['id' => 5, 'name' => 'Nyeri', 'region_id' => 1],
            
            // Coast Region
            ['id' => 6, 'name' => 'Kilifi', 'region_id' => 2],
            ['id' => 7, 'name' => 'Kwale', 'region_id' => 2],
            ['id' => 8, 'name' => 'Lamu', 'region_id' => 2],
            ['id' => 9, 'name' => 'Mombasa', 'region_id' => 2],
            ['id' => 10, 'name' => 'Taita Taveta', 'region_id' => 2],
            ['id' => 11, 'name' => 'Tana River', 'region_id' => 2],
            
            // Eastern Region
            ['id' => 12, 'name' => 'Embu', 'region_id' => 3],
            ['id' => 13, 'name' => 'Isiolo', 'region_id' => 3],
            ['id' => 14, 'name' => 'Kitui', 'region_id' => 3],
            ['id' => 15, 'name' => 'Machakos', 'region_id' => 3],
            ['id' => 16, 'name' => 'Makueni', 'region_id' => 3],
            ['id' => 17, 'name' => 'Marsabit', 'region_id' => 3],
            ['id' => 18, 'name' => 'Meru', 'region_id' => 3],
            ['id' => 19, 'name' => 'Tharaka Nithi', 'region_id' => 3],
            
            // North Eastern Region
            ['id' => 20, 'name' => 'Garissa', 'region_id' => 4],
            ['id' => 21, 'name' => 'Mandera', 'region_id' => 4],
            ['id' => 22, 'name' => 'Wajir', 'region_id' => 4],
            
            // Nyanza Region
            ['id' => 23, 'name' => 'Homa Bay', 'region_id' => 5],
            ['id' => 24, 'name' => 'Kisii', 'region_id' => 5],
            ['id' => 25, 'name' => 'Kisumu', 'region_id' => 5],
            ['id' => 26, 'name' => 'Migori', 'region_id' => 5],
            ['id' => 27, 'name' => 'Nyamira', 'region_id' => 5],
            ['id' => 28, 'name' => 'Siaya', 'region_id' => 5],
            
            // Rift Valley Region
            ['id' => 29, 'name' => 'Baringo', 'region_id' => 6],
            ['id' => 30, 'name' => 'Bomet', 'region_id' => 6],
            ['id' => 31, 'name' => 'Elgeyo Marakwet', 'region_id' => 6],
            ['id' => 32, 'name' => 'Kajiado', 'region_id' => 6],
            ['id' => 33, 'name' => 'Kericho', 'region_id' => 6],
            ['id' => 34, 'name' => 'Laikipia', 'region_id' => 6],
            ['id' => 35, 'name' => 'Nakuru', 'region_id' => 6],
            ['id' => 36, 'name' => 'Nandi', 'region_id' => 6],
            ['id' => 37, 'name' => 'Narok', 'region_id' => 6],
            ['id' => 38, 'name' => 'Samburu', 'region_id' => 6],
            ['id' => 39, 'name' => 'Trans Nzoia', 'region_id' => 6],
            ['id' => 40, 'name' => 'Turkana', 'region_id' => 6],
            ['id' => 41, 'name' => 'Uasin Gishu', 'region_id' => 6],
            ['id' => 42, 'name' => 'West Pokot', 'region_id' => 6],
            
            // Western Region
            ['id' => 43, 'name' => 'Bungoma', 'region_id' => 7],
            ['id' => 44, 'name' => 'Busia', 'region_id' => 7],
            ['id' => 45, 'name' => 'Kakamega', 'region_id' => 7],
            ['id' => 46, 'name' => 'Vihiga', 'region_id' => 7],
            
            // Nairobi Region
            ['id' => 47, 'name' => 'Nairobi', 'region_id' => 8],
        ];

        foreach ($counties as $county) {
            DB::table('counties')->insert([
                'id' => $county['id'],
                'name' => $county['name'],
                'region_id' => $county['region_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Insert communities (sample communities for each county)
        $communities = [];
        $communityId = 1;
        
        foreach ($counties as $county) {
            for ($i = 1; $i <= 3; $i++) {
                $communities[] = [
                    'id' => $communityId++,
                    'name' => $county['name'] . ' Community ' . $i,
                    'county_id' => $county['id'],
                    'region_id' => $county['region_id'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        foreach ($communities as $community) {
            DB::table('communities')->insert($community);
        }

        // Insert Kenya National Tournament
        DB::table('tournaments')->insert([
            'id' => 1,
            'name' => 'Kenya National Pool Championship 2024',
            'special' => false,
            'community_prize' => 5000.00,
            'county_prize' => 15000.00,
            'regional_prize' => 50000.00,
            'national_prize' => 200000.00,
            'tournament_charge' => 500.00,
            'status' => 'upcoming',
            'automation_mode' => 'manual',
            'start_date' => '2024-12-01',
            'end_date' => '2024-12-31',
            'registration_deadline' => '2024-11-15',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Insert sample users for testing
        $sampleUsers = [
            [
                'id' => 1,
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'email' => 'admin@cuesports.com',
                'phone' => '+254700000000',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'community_id' => 1,
                'county_id' => 1,
                'region_id' => 1,
                'total_points' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        // Add sample players from different regions
        $playerNames = [
            ['John', 'Kamau'], ['Mary', 'Wanjiku'], ['Peter', 'Mwangi'], ['Grace', 'Njeri'],
            ['David', 'Ochieng'], ['Sarah', 'Akinyi'], ['Michael', 'Otieno'], ['Jane', 'Awino'],
            ['James', 'Kipchoge'], ['Ruth', 'Chebet'], ['Daniel', 'Ruto'], ['Esther', 'Jepkemei'],
            ['Samuel', 'Maina'], ['Lucy', 'Wangari'], ['Joseph', 'Kariuki'], ['Ann', 'Wambui']
        ];

        $userId = 2;
        foreach ($communities as $community) {
            if ($userId > 50) break; // Limit to 50 users
            
            $nameIndex = ($userId - 2) % count($playerNames);
            $player = $playerNames[$nameIndex];
            
            $sampleUsers[] = [
                'id' => $userId++,
                'name' => $player[0] . ' ' . $player[1],
                'first_name' => $player[0],
                'last_name' => $player[1],
                'username' => strtolower($player[0] . $player[1] . $community['id']),
                'email' => strtolower($player[0] . '.' . $player[1] . $community['id'] . '@example.com'),
                'phone' => '+2547' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'community_id' => $community['id'],
                'county_id' => $community['county_id'],
                'region_id' => $community['region_id'],
                'total_points' => rand(0, 1000),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        foreach ($sampleUsers as $user) {
            DB::table('users')->insert($user);
        }

        // Register some users for the tournament
        $registeredUsers = [];
        for ($i = 1; $i <= min(30, count($sampleUsers)); $i++) {
            $registeredUsers[] = [
                'player_id' => $i,
                'tournament_id' => 1,
                'status' => 'approved',
                'payment_status' => 'paid',
                'payment_intent_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        foreach ($registeredUsers as $registration) {
            DB::table('registered_users')->insert($registration);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('registered_users')->where('tournament_id', 1)->delete();
        DB::table('tournaments')->where('id', 1)->delete();
        DB::table('users')->whereIn('id', range(1, 50))->delete();
        DB::table('communities')->truncate();
        DB::table('counties')->truncate();
        DB::table('regions')->truncate();
    }
};
