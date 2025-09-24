<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            
            $table->unique(['region_id', 'name']);
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counties');
    }
};
