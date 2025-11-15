<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_notes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('contact_id')->index();
            $table->unsignedBigInteger('created_by')->index();

            $table->text('body');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_notes');
    }
};
