<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')
                  ->constrained('contacts')
                  ->onDelete('cascade');

            $table->date('event_date')->nullable();
            $table->string('event_type', 150)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_events');
    }
};
