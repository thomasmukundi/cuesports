<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('winners', function (Blueprint $table) {
            $table->string('level_name')->nullable()->after('level');
            $table->index(['level', 'level_name'], 'idx_level_name');
        });
    }

    public function down(): void
    {
        Schema::table('winners', function (Blueprint $table) {
            $table->dropIndex('idx_level_name');
            $table->dropColumn('level_name');
        });
    }
};
