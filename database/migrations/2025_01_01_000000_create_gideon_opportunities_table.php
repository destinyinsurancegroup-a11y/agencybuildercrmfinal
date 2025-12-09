<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gideon_opportunities', function (Blueprint $table) {
            $table->id();

            // Multi-tenant: use agency_id to match the rest of your app
            $table->unsignedBigInteger('agency_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // What this opportunity is attached to
            $table->string('entity_type', 50); // lead|contact|policy|service|household|partner|other
            $table->unsignedBigInteger('entity_id')->nullable();

            // What kind of opportunity this is
            $table->string('category', 50); // beneficiary|cross_sell|lapse_risk|renewal|household|referral|revive_lead|other
            $table->string('title', 255)->nullable();
            $table->text('short_reason')->nullable();
            $table->text('recommended_action')->nullable();

            // Simple 0–100 score (higher = more important)
            $table->unsignedTinyInteger('score')->default(0);

            // Workflow status
            $table->string('status', 32)->default('open'); // open|snoozed|completed|dismissed

            // Outcome fields for adaptive intelligence (later)
            $table->string('outcome', 32)->nullable(); // won|lost|ignored|in_progress
            $table->string('outcome_reason', 255)->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Minimal snapshot of related data at the time this was created
            $table->json('source_snapshot')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index(['agency_id', 'status', 'score']);
            $table->index(['agency_id', 'entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gideon_opportunities');
    }
};
