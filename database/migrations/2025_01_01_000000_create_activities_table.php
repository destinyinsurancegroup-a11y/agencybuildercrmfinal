<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->integer('leads_worked')->default(0);
            $table->integer('calls')->default(0);
            $table->integer('stops')->default(0);
            $table->integer('presentations')->default(0);
            $table->integer('apps_written')->default(0);
            $table->decimal('premium_collected', 10, 2)->default(0);
            $table->decimal('ap', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
