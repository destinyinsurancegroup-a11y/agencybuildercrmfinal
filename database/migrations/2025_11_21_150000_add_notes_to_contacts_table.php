<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Add "notes" column if it does NOT already exist
            if (!Schema::hasColumn('contacts', 'notes')) {
                $table->longText('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
