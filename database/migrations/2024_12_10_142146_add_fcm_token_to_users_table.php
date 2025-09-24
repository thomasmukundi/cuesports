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
        Schema::table('users', function (Blueprint $table) {
            $table->string('fcm_token', 255)->nullable()->after('remember_token');
            $table->timestamp('fcm_token_updated_at')->nullable()->after('fcm_token');
            
            // Add index for faster lookups when sending notifications
            $table->index('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['fcm_token']);
            $table->dropColumn(['fcm_token', 'fcm_token_updated_at']);
        });
    }
};
