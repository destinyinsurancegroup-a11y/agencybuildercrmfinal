<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_relations', function (Blueprint $table) {
            $table->id();

            // Parent contact
            $table->unsignedBigInteger('contact_id');

            // 'beneficiary' or 'emergency'
            $table->string('type', 20);

            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('phone')->nullable();

            // Red / green dot in UI
            $table->boolean('contacted')->default(false);

            // Multi-tenant + audit (simple version; tune later)
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('cascade');

            $table->index(['contact_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_relations');
    }
};
