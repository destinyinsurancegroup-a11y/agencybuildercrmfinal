<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // "Motivation & Urgency"
            $table->string('code')->unique();  // "stage_motivation_urgency"
            $table->text('description')->nullable();

            // JSON logic hints
            $table->json('entry_conditions')->nullable();
            $table->json('success_criteria')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_stages');
    }
};
