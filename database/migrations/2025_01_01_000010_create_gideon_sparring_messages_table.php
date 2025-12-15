<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_sparring_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // IMPORTANT: session_id must match the sessions table PK type.
            // Your sessions table uses $table->id() so session_id must be foreignId().
            $table->foreignId('session_id')
                ->constrained('gideon_sparring_sessions')
                ->cascadeOnDelete();

            // agent | prospect | coach
            $table->string('role', 20);

            $table->text('content');

            // stage, mini_close_status, objection category, etc.
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['agency_id', 'user_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_sparring_messages');
    }
};
