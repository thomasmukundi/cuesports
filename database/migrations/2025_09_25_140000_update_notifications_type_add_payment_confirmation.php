<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'payment_confirmation' to notifications.type enum (MySQL only)
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        if ($driver === 'mysql') {
            // Retrieve current enum definition if needed; here we hardcode to the known set + new value
            DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM('pairing','result','admin','admin_message','match_scheduled','result_confirmation','tournament_completed','tournament_position','other','payment_confirmation') NOT NULL");
        } else {
            // For non-MySQL drivers, enums are typically strings; no change required.
        }
    }

    public function down(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");
        if ($driver === 'mysql') {
            // Revert to previous enum list without 'payment_confirmation'
            DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM('pairing','result','admin','admin_message','match_scheduled','result_confirmation','tournament_completed','tournament_position','other') NOT NULL");
        }
    }
};
