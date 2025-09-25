<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Consolidate and standardize the notifications.type enum to include all types used in code
        $allTypes = [
            // Existing base types
            'pairing',
            'result',
            'admin',
            'admin_message',
            'match_scheduled',
            'result_confirmation',
            'tournament_completed',
            'tournament_position',
            'other',
            // Newly added previously
            'payment_confirmation',
            // Additional types found in codebase
            'registration',
            'tournament_started',
            'prize',
            'admin_alert',
            // Some code uses this variant; keep for compatibility
            'tournament_complete',
        ];

        $typesSql = "'" . implode("','", $allTypes) . "'";

        // Only applies to MySQL enum columns
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM($typesSql) NOT NULL");
    }

    public function down(): void
    {
        // Revert to the previously known list including payment_confirmation
        $previousTypes = [
            'pairing',
            'result',
            'admin',
            'admin_message',
            'match_scheduled',
            'result_confirmation',
            'tournament_completed',
            'tournament_position',
            'other',
            'payment_confirmation',
        ];
        $typesSql = "'" . implode("','", $previousTypes) . "'";
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM($typesSql) NOT NULL");
    }
};
