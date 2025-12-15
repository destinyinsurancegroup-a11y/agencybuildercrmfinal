<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('gideon_sparring_sessions', function (Blueprint $table) {

            // New sparring modes:
            // stages | discovery_start | full_presentation
            if (!Schema::hasColumn('gideon_sparring_sessions', 'training_mode')) {
                $table->string('training_mode', 50)->nullable()->after('mode');
            }

            // Used only when training_mode = stages
            if (!Schema::hasColumn('gideon_sparring_sessions', 'selected_stage')) {
                $table->string('selected_stage', 50)->nullable()->after('training_mode');
            }

            // easy | normal | hard
            if (!Schema::hasColumn('gideon_sparring_sessions', 'difficulty')) {
                $table->string('difficulty', 20)->default('normal')->after('selected_stage');
            }

            // Stores current stage, ledger, terminated, termination_reason, expression, etc.
            if (!Schema::hasColumn('gideon_sparring_sessions', 'state')) {
                $table->json('state')->nullable()->after('config');
            }
        });

        Schema::table('gideon_sparring_sessions', function (Blueprint $table) {
            $table->index(['agency_id', 'user_id', 'training_mode'], 'gideon_sparring_sessions_mode_idx');
        });
    }

    public function down(): void
    {
        Schema::table('gideon_sparring_sessions', function (Blueprint $table) {
            $table->dropIndex('gideon_sparring_sessions_mode_idx');
            $table->dropColumn(['training_mode', 'selected_stage', 'difficulty', 'state']);
        });
    }
};
