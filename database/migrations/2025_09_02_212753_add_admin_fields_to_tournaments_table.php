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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->decimal('entry_fee', 10, 2)->default(0)->after('tournament_charge');
            $table->integer('max_participants')->nullable()->after('entry_fee');
            $table->unsignedBigInteger('created_by')->nullable()->after('automation_mode');
            $table->string('status')->change(); // Remove enum constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['description', 'entry_fee', 'max_participants', 'created_by']);
        });
    }
};
