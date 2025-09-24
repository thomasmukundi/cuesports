<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('special')->default(false);
            $table->decimal('community_prize', 10, 2)->nullable();
            $table->decimal('county_prize', 10, 2)->nullable();
            $table->decimal('regional_prize', 10, 2)->nullable();
            $table->decimal('national_prize', 10, 2)->nullable();
            $table->enum('area_scope', ['community', 'county', 'region', 'national'])->nullable();
            $table->string('area_name')->nullable();
            $table->decimal('tournament_charge', 10, 2);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('registration_deadline')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'completed'])->default('upcoming');
            $table->enum('automation_mode', ['automatic', 'manual'])->default('automatic');
            $table->timestamps();
            
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
