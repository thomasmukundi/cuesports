<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add tournament_announcement to the notifications.type enum
        $allTypes = [
            // Existing types from previous migration
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
            'registration',
            'tournament_started',
            'prize',
            'admin_alert',
            'tournament_complete',
            // Add the missing tournament_announcement type
            'tournament_announcement',
        ];

        $typesSql = "'" . implode("','", $allTypes) . "'";

        // Update the enum to include tournament_announcement
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM($typesSql) NOT NULL");
    }

    public function down(): void
    {
        // Revert to the previous list without tournament_announcement
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
            'registration',
            'tournament_started',
            'prize',
            'admin_alert',
            'tournament_complete',
        ];
        $typesSql = "'" . implode("','", $previousTypes) . "'";
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM($typesSql) NOT NULL");
    }
};
