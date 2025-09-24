<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Change type from ENUM to VARCHAR to support all notification types
            $table->string('type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Revert back to original ENUM
            $table->enum('type', ['pairing', 'result', 'admin', 'admin_message', 'match_scheduled', 'result_confirmation', 'tournament_completed', 'tournament_position', 'other'])->change();
        });
    }
};
