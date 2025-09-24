<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->nullable()->unique()->after('last_name');
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('community_id')->nullable()->constrained()->after('password');
            $table->foreignId('county_id')->nullable()->constrained()->after('community_id');
            $table->foreignId('region_id')->nullable()->constrained()->after('county_id');
            $table->integer('total_points')->default(0)->after('region_id');
            $table->timestamp('last_login')->nullable()->after('total_points');
            
            $table->index('community_id');
            $table->index(['total_points'], 'idx_total_points_desc');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['community_id']);
            $table->dropForeign(['county_id']);
            $table->dropForeign(['region_id']);
            $table->dropColumn([
                'first_name', 'last_name', 'username', 'phone',
                'community_id', 'county_id', 'region_id', 
                'total_points', 'last_login'
            ]);
        });
    }
};
