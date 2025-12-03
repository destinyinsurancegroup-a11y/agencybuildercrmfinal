<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // later we can link this to the "owner" user if we want
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->timestamps();
        });

        // Seed your first (existing) agency so we can attach users & data later
        DB::table('agencies')->insert([
            'name'         => 'My Agency',
            'owner_user_id'=> null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
