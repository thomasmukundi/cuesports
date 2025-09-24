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
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('pairing', 'result', 'admin', 'admin_message', 'match_scheduled', 'result_confirmation', 'tournament_completed', 'tournament_position', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('pairing', 'result', 'admin', 'admin_message', 'match_scheduled', 'result_confirmation', 'tournament_completed', 'other')");
    }
};
