<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_sparring_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('mode', 50);           // "standard", "objection_gauntlet"
            $table->string('persona_key', 100)->nullable();
            $table->json('config')->nullable();

            $table->enum('status', ['active', 'completed', 'aborted'])->default('active');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_sparring_sessions');
    }
};
