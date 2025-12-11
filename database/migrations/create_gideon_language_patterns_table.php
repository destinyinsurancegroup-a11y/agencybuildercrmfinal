<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_language_patterns', function (Blueprint $table) {
            $table->id();

            $table->string('type'); 
            // "softener", "clarifier", "noncommittal_trigger"

            $table->string('code'); 
            // "softener_thats_fair", "trigger_maybe"

            $table->text('pattern_text');
            $table->json('tags')->nullable(); // ["rapport", "happy_ears"]

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_language_patterns');
    }
};
