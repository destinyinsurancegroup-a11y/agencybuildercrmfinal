<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gideon_note_index', function (Blueprint $table) {
            if (!Schema::hasColumn('gideon_note_index', 'agency_id')) {
                $table->unsignedBigInteger('agency_id')->nullable()->index()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gideon_note_index', function (Blueprint $table) {
            if (Schema::hasColumn('gideon_note_index', 'agency_id')) {
                $table->dropColumn('agency_id');
            }
        });
    }
};
