<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_scenarios', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // "FE – Think It Over"
            $table->string('code')->unique(); // "scenario_fe_think_it_over"

            $table->string('product_type')->nullable(); // "final_expense", "medicare", etc.

            $table->foreignId('starting_stage_id')
                ->nullable()
                ->constrained('gideon_stages')
                ->nullOnDelete();

            $table->text('description')->nullable();

            // prospect persona + behavior settings
            $table->json('prospect_profile')->nullable();

            // how Gideon should behave as 'prospect' in this scenario
            $table->json('script_engine')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_scenarios');
    }
};
