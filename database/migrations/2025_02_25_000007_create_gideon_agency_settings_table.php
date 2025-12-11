<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_agency_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')
                ->constrained('agencies')
                ->cascadeOnDelete();

            $table->json('tone_profile_overrides')->nullable();
            $table->json('enabled_scenario_codes')->nullable(); // ["scenario_fe_think_it_over", ...]
            $table->json('difficulty_settings')->nullable();    // { "aggressiveness": "medium", ... }

            $table->timestamps();

            $table->unique('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_agency_settings');
    }
};
