<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Defense-in-depth tenant scoping (even if attachable is scoped)
            $table->unsignedBigInteger('agency_id')->nullable()->index();

            // Polymorphic owner (Option A: Contact is the attachable)
            $table->string('attachable_type', 200);
            $table->unsignedBigInteger('attachable_id');
            $table->index(['attachable_type', 'attachable_id'], 'attachments_attachable_idx');

            // File metadata
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            // Storage
            $table->string('storage_disk', 50)->default('local'); // keep simple for now
            $table->string('storage_path', 1024);                 // relative path on disk

            // Audit
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            // Optional: if you want referential integrity later, you can add FK constraints,
            // but polymorphic relationships don't play nicely with FK on attachable_id.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
