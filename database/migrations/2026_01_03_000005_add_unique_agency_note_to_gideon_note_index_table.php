<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gideon_note_index', function (Blueprint $table) {
            // Correct uniqueness: two note tables can share the same numeric IDs,
            // so we must include the source system to prevent collisions.
            $table->unique(
                ['agency_id', 'source_system', 'note_id'],
                'gideon_note_index_agency_source_note_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('gideon_note_index', function (Blueprint $table) {
            $table->dropUnique('gideon_note_index_agency_source_note_unique');
        });
    }
};
