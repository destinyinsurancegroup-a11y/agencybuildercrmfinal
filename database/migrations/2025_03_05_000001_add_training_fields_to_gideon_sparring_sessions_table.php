<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gideon_sparring_sessions', function (Blueprint $table) {

            // How the user is training (full, discovery_start, stages)
            if (!Schema::hasColumn('gideon_sparring_sessions', 'training_mode')) {
                $table->string('training_mode')
                    ->nullable()
                    ->after('mode')
                    ->index();
            }

            // Which stage is selected when using stage-based training
            if (!Schema::hasColumn('gideon_sparring_sessions', 'selected_stage')) {
                $table->string('selected_stage')
                    ->nullable()
                    ->after('training_mode')
                    ->index();
            }

            // Difficulty level (easy / normal / hard)
            if (!Schema::hasColumn('gideon_sparring_sessions', 'difficulty')) {
                $table->string('difficulty')
                    ->nullable()
                    ->after('selected_stage')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('gideon_sparring_sessions', function (Blueprint $table) {

            if (Schema::hasColumn('gideon_sparring_sessions', 'training_mode')) {
                $table->dropColumn('training_mode');
            }

            if (Schema::hasColumn('gideon_sparring_sessions', 'selected_stage')) {
                $table->dropColumn('selected_stage');
            }

            if (Schema::hasColumn('gideon_sparring_sessions', 'difficulty')) {
                $table->dropColumn('difficulty');
            }
        });
    }
};
