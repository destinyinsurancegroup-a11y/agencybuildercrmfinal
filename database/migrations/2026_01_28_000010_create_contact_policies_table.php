<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_policies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('agency_id')->index();
            $table->unsignedBigInteger('contact_id')->index();

            // Mirrors the fields you already show on the card today
            $table->string('carrier')->nullable();
            $table->string('policy_type')->nullable();      // keep your existing naming
            $table->decimal('face_amount', 12, 2)->nullable();
            $table->decimal('premium_amount', 12, 2)->nullable();
            $table->date('policy_issue_date')->nullable();  // "Initial Draft Date"
            $table->date('premium_due_date')->nullable();
            $table->string('premium_due_text')->nullable(); // "3rd"

            $table->timestamps();

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('cascade');

            $table->index(['agency_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_policies');
    }
};
