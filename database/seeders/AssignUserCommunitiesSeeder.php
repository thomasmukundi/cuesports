<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignUserCommunitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users without community_id who are registered for tournaments
        $usersWithoutCommunity = DB::table('users')
            ->join('registered_users', 'users.id', '=', 'registered_users.player_id')
            ->whereNull('users.community_id')
            ->select('users.id')
            ->distinct()
            ->get();

        // Get available communities
        $communities = DB::table('communities')->get();
        
        if ($communities->isEmpty()) {
            $this->command->error('No communities found. Please seed communities first.');
            return;
        }

        // Assign communities randomly to users
        foreach ($usersWithoutCommunity as $user) {
            $randomCommunity = $communities->random();
            
            DB::table('users')
                ->where('id', $user->id)
                ->update(['community_id' => $randomCommunity->id]);
                
            $this->command->info("Assigned user {$user->id} to community {$randomCommunity->name}");
        }
        
        $this->command->info("Assigned communities to {$usersWithoutCommunity->count()} users");
    }
}
