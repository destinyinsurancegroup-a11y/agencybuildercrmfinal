<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gideon_note_index', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->uuid('tenant_id')->index();
            $table->string('entity_type', 32)->index(); // lead|client|contact|service_case
            $table->unsignedBigInteger('entity_id')->index();

            $table->unsignedBigInteger('note_id')->index();
            $table->unsignedBigInteger('author_user_id')->nullable()->index();

            $table->text('note_text');

            $table->timestampTz('note_created_at')->index();
            $table->timestampTz('note_updated_at')->nullable()->index();

            $table->string('source_system', 32)->nullable(); // native|import|sms|email
            $table->string('visibility', 16)->default('public'); // public|private

            $table->timestampsTz();

            // Prevent duplicates if the same note is indexed twice
            $table->unique(['tenant_id', 'note_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gideon_note_index');
    }
};
