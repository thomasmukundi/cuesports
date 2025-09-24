<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Create admin user with proper credentials
     */
    public function run(): void
    {
        // Check if admin user already exists
        $existingAdmin = DB::table('users')->where('email', 'admin@cuesports.com')->first();
        
        if ($existingAdmin) {
            // Update existing admin user
            DB::table('users')
                ->where('email', 'admin@cuesports.com')
                ->update([
                    'is_admin' => true,
                    'password' => Hash::make('password'),
                    'updated_at' => Carbon::now(),
                ]);
            
            $this->command->info('Updated existing admin user with is_admin=true');
        } else {
            // Create new admin user
            DB::table('users')->insert([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@cuesports.com',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            $this->command->info('Created new admin user');
        }
        
        $this->command->info('Admin credentials:');
        $this->command->info('Email: admin@cuesports.com');
        $this->command->info('Password: password');
    }
}
