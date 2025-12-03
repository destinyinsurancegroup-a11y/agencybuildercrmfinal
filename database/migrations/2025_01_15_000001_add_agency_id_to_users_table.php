<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Add agency_id as nullable during initial migration
            $table->foreignId('agency_id')
                ->nullable()
                ->after('id')
                ->constrained('agencies')
                ->cascadeOnUpdate()
                ->nullOnDelete(); 
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn('agency_id');
        });
    }
};
