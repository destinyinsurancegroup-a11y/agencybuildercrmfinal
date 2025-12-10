<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_sparring_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                ->constrained('gideon_sparring_sessions')
                ->cascadeOnDelete();

            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->json('scores')->nullable();  // { "rapport": 6, "discovery": 5, ... }
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['agency_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_sparring_assessments');
    }
};
