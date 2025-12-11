<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_agency_scenario_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')
                ->constrained('agencies')
                ->cascadeOnDelete();

            $table->foreignId('gideon_scenario_id')
                ->constrained('gideon_scenarios')
                ->cascadeOnDelete();

            // JSON patch: override persona, script bits, etc.
            $table->json('overrides')->nullable();

            $table->timestamps();

            $table->unique(['agency_id', 'gideon_scenario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_agency_scenario_overrides');
    }
};
