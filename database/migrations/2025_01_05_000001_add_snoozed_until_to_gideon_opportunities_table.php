<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gideon_opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $table->timestamp('snoozed_until')
                      ->nullable()
                      ->after('status')
                      ->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gideon_opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('gideon_opportunities', 'snoozed_until')) {
                $table->dropColumn('snoozed_until');
            }
        });
    }
};
