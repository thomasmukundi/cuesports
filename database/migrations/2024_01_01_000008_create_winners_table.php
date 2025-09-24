<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('users');
            $table->unsignedTinyInteger('position');
            $table->enum('level', ['community', 'county', 'regional', 'national', 'special']);
            $table->unsignedBigInteger('level_id')->nullable();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->boolean('prize_awarded')->default(false);
            $table->decimal('prize_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['tournament_id', 'level', 'position'], 'idx_tournament_level_position');
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winners');
    }
};
