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

            // Multi-tenant safe
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();

            // Event fields
            $table->string('title');
            $table->datetime('start');
            $table->datetime('end')->nullable();
            $table->string('color')->nullable();

            // NEW required fields
            $table->string('location')->nullable();
            $table->integer('reminder')->nullable(); // minutes before

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
