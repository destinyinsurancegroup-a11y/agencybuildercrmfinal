<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Multi-tenant support
            $table->unsignedBigInteger('tenant_id')->index();

            // Calendar event info
            $table->string('title');
            $table->dateTime('start');
            $table->dateTime('end')->nullable();
            $table->string('color')->nullable();

            // Optional: who created the event
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
