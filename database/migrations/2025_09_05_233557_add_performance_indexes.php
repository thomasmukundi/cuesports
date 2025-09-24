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
        // Add indexes for tournaments table
        Schema::table('tournaments', function (Blueprint $table) {
            if (!Schema::hasIndex('tournaments', 'tournaments_status_index')) {
                $table->index('status');
            }
            if (!Schema::hasIndex('tournaments', 'tournaments_created_at_index')) {
                $table->index('created_at');
            }
            if (!Schema::hasIndex('tournaments', 'tournaments_status_created_at_index')) {
                $table->index(['status', 'created_at']);
            }
        });

        // Add indexes for matches/pool_matches table
        Schema::table('matches', function (Blueprint $table) {
            if (!Schema::hasIndex('matches', 'matches_player_1_id_index')) {
                $table->index('player_1_id');
            }
            if (!Schema::hasIndex('matches', 'matches_player_2_id_index')) {
                $table->index('player_2_id');
            }
            if (!Schema::hasIndex('matches', 'matches_tournament_id_index')) {
                $table->index('tournament_id');
            }
            if (!Schema::hasIndex('matches', 'matches_winner_id_index')) {
                $table->index('winner_id');
            }
            if (!Schema::hasIndex('matches', 'matches_status_index')) {
                $table->index('status');
            }
            if (!Schema::hasIndex('matches', 'matches_player_1_id_player_2_id_index')) {
                $table->index(['player_1_id', 'player_2_id']);
            }
            if (!Schema::hasIndex('matches', 'matches_tournament_id_status_index')) {
                $table->index(['tournament_id', 'status']);
            }
        });

        // Add indexes for users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', 'users_total_points_index')) {
                $table->index('total_points');
            }
            if (!Schema::hasIndex('users', 'users_community_id_index')) {
                $table->index('community_id');
            }
            if (!Schema::hasIndex('users', 'users_county_id_index')) {
                $table->index('county_id');
            }
            if (!Schema::hasIndex('users', 'users_region_id_index')) {
                $table->index('region_id');
            }
            if (!Schema::hasIndex('users', 'users_total_points_community_id_index')) {
                $table->index(['total_points', 'community_id']);
            }
        });

        // Add indexes for tournament_registrations table
        Schema::table('tournament_registrations', function (Blueprint $table) {
            if (!Schema::hasIndex('tournament_registrations', 'tournament_registrations_tournament_id_index')) {
                $table->index('tournament_id');
            }
            if (!Schema::hasIndex('tournament_registrations', 'tournament_registrations_player_id_index')) {
                $table->index('player_id');
            }
            if (!Schema::hasIndex('tournament_registrations', 'tournament_registrations_tournament_id_player_id_index')) {
                $table->index(['tournament_id', 'player_id']);
            }
            if (!Schema::hasIndex('tournament_registrations', 'tournament_registrations_payment_status_index')) {
                $table->index('payment_status');
            }
        });

        // Add indexes for notifications table
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasIndex('notifications', 'notifications_player_id_index')) {
                $table->index('player_id');
            }
            if (!Schema::hasIndex('notifications', 'notifications_read_at_index')) {
                $table->index('read_at');
            }
            if (!Schema::hasIndex('notifications', 'notifications_player_id_read_at_index')) {
                $table->index(['player_id', 'read_at']);
            }
            if (!Schema::hasIndex('notifications', 'notifications_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Add indexes for match_messages table
        Schema::table('match_messages', function (Blueprint $table) {
            if (!Schema::hasIndex('match_messages', 'match_messages_match_id_index')) {
                $table->index('match_id');
            }
            if (!Schema::hasIndex('match_messages', 'match_messages_sender_id_index')) {
                $table->index('sender_id');
            }
            if (!Schema::hasIndex('match_messages', 'match_messages_match_id_created_at_index')) {
                $table->index(['match_id', 'created_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for tournaments table
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status', 'created_at']);
        });

        // Drop indexes for matches table
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex(['player_1_id']);
            $table->dropIndex(['player_2_id']);
            $table->dropIndex(['tournament_id']);
            $table->dropIndex(['winner_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['player_1_id', 'player_2_id']);
            $table->dropIndex(['tournament_id', 'status']);
        });

        // Drop indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['total_points']);
            $table->dropIndex(['community_id']);
            $table->dropIndex(['county_id']);
            $table->dropIndex(['region_id']);
            $table->dropIndex(['total_points', 'community_id']);
        });

        // Drop indexes for tournament_registrations table
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropIndex(['tournament_id']);
            $table->dropIndex(['player_id']);
            $table->dropIndex(['tournament_id', 'player_id']);
            $table->dropIndex(['payment_status']);
        });

        // Drop indexes for notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['player_id']);
            $table->dropIndex(['read_at']);
            $table->dropIndex(['player_id', 'read_at']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes for match_messages table
        Schema::table('match_messages', function (Blueprint $table) {
            $table->dropIndex(['match_id']);
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['match_id', 'created_at']);
        });
    }
};
