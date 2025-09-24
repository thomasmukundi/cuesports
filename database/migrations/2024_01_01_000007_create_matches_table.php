<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('match_name')->nullable();
            $table->foreignId('player_1_id')->nullable()->constrained('users');
            $table->foreignId('player_2_id')->nullable()->constrained('users');
            $table->integer('player_1_points')->nullable();
            $table->integer('player_2_points')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->foreignId('bye_player_id')->nullable()->constrained('users');
            $table->enum('level', ['community', 'county', 'regional', 'national', 'special']);
            $table->string('round_name');
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'scheduled', 'in_progress', 'pending_confirmation', 'completed', 'forfeit'])->default('pending');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->json('proposed_dates')->nullable();
            $table->json('player_1_preferred_dates')->nullable();
            $table->json('player_2_preferred_dates')->nullable();
            $table->datetime('scheduled_date')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['tournament_id', 'level', 'round_name', 'status'], 'idx_tournament_level_round_status');
            $table->index('player_1_id');
            $table->index('player_2_id');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
