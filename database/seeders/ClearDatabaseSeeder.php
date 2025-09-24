<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearDatabaseSeeder extends Seeder
{
    /**
     * Clear all tables except regions, counties, communities, and admin user
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Tables to clear (preserve regions, counties, communities)
        $tablesToClear = [
            'admin_messages',
            'cache',
            'cache_locks',
            'chat_messages',
            'match_messages',
            'matches',
            'notifications',
            'password_reset_tokens',
            'personal_access_tokens',
            'registered_users',
            'sessions',
            'tournament_registrations',
            'tournaments',
            'winners',
            'failed_jobs',
            'job_batches',
            'jobs',
        ];

        foreach ($tablesToClear as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("Cleared table: {$table}");
            }
        }

        // Clear users table but preserve admin user (assuming admin has ID 1 or email contains 'admin')
        $adminUsers = DB::table('users')
            ->where('email', 'like', '%admin%')
            ->orWhere('id', 1)
            ->get();

        DB::table('users')->truncate();

        // Restore admin users
        foreach ($adminUsers as $admin) {
            DB::table('users')->insert((array) $admin);
            $this->command->info("Preserved admin user: {$admin->email}");
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Database cleared successfully! Preserved: regions, counties, communities, and admin users.');
    }
}
