<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateSpecificUsersLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Central region (id: 2), Kiambu county (id: 2)
        // Thika community (use Kiambu Community 1 - id: 11)
        // Ruiru community (use Kiambu Community 2 - id: 12)
        
        $thikaCommunityUsers = [
            'mukundithomas8@gmail.com',
            'moureengathoni@gmail.com', 
            'jamesmike@gmail.com',
            'miketyson@gmail.com'
        ];
        
        $ruiruCommunityUsers = [
            'ericmarks@gmail.com',
            'markrubio@gmail.com',
            'labansmiles@gmail.con', // Note: typo in original email
            'lakersnoofs@gmail.com',
            'stellasteves@gmail.com'
        ];
        
        // Update Thika community users
        foreach ($thikaCommunityUsers as $email) {
            $updated = DB::table('users')
                ->where('email', $email)
                ->update([
                    'community_id' => 11, // Kiambu Community 1 (Thika)
                    'county_id' => 2,     // Kiambu
                    'region_id' => 2      // Central
                ]);
                
            if ($updated) {
                $this->command->info("Updated {$email} to Thika community");
            } else {
                $this->command->warn("User {$email} not found");
            }
        }
        
        // Update Ruiru community users  
        foreach ($ruiruCommunityUsers as $email) {
            $updated = DB::table('users')
                ->where('email', $email)
                ->update([
                    'community_id' => 12, // Kiambu Community 2 (Ruiru)
                    'county_id' => 2,     // Kiambu
                    'region_id' => 2      // Central
                ]);
                
            if ($updated) {
                $this->command->info("Updated {$email} to Ruiru community");
            } else {
                $this->command->warn("User {$email} not found");
            }
        }
        
        $this->command->info("Location updates completed!");
    }
}
