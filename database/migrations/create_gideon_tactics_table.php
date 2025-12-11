<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gideon_tactics', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   // "Going Negative"
            $table->string('code')->unique();   // "tactic_going_negative"
            $table->text('description')->nullable();

            $table->json('example_uses')->nullable();       // array of strings
            $table->json('sparring_examples')->nullable();  // array of sample lines
            $table->text('coaching_notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_tactics');
    }
};
