<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_if_then_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // "Problem -> Impact Question"
            $table->string('code')->unique(); // "rule_problem_to_impact"

            // e.g. { "event": "prospect_expresses_problem", "stage_code": "stage_motivation_urgency" }
            $table->json('if_conditions');

            // e.g. { "tactic_code": "tactic_socratic_question", "goal": "deepen_impact", "sparring_prompt_templates": [...] }
            $table->json('then_actions');

            $table->text('coaching_explanation')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100); // lower = checked first

            $table->timestamps();
        });

        Schema::table('gideon_if_then_rules', function (Blueprint $table) {
            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_if_then_rules');
    }
};
