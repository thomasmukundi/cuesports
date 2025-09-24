<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registered_users', function (Blueprint $table) {
            $table->foreignId('player_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->enum('payment_status', ['pending', 'paid', 'completed', 'failed'])->default('pending');
            $table->enum('status', ['registered', 'approved', 'withdrawn'])->default('registered');
            $table->string('payment_intent_id')->nullable();
            $table->timestamp('registration_date')->useCurrent();
            $table->timestamps();
            
            $table->primary(['player_id', 'tournament_id']);
            $table->index('tournament_id');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registered_users');
    }
};
