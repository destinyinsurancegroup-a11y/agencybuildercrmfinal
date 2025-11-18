<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add the new calendar fields
            if (!Schema::hasColumn('events', 'location')) {
                $table->string('location')->nullable()->after('title');
            }

            if (!Schema::hasColumn('events', 'reminder')) {
                $table->string('reminder')->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('events', 'reminder')) {
                $table->dropColumn('reminder');
            }
        });
    }
};
