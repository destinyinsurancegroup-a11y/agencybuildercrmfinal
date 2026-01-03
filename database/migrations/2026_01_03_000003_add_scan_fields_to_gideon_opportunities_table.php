<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gideon_opportunities', function (Blueprint $table) {
            // These fields make note-scans reliable and deduped.
            $table->string('rule_code', 32)->nullable()->after('category');
            $table->string('source_type', 32)->nullable()->after('rule_code'); // e.g. note_index
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type'); // note_id
            $table->timestamp('due_at')->nullable()->after('source_id');

            $table->timestamp('first_detected_at')->nullable()->after('source_snapshot');
            $table->timestamp('last_detected_at')->nullable()->after('first_detected_at');
        });

        Schema::table('gideon_opportunities', function (Blueprint $table) {
            // One opportunity per entity per rule per source note (no duplicates).
            $table->unique(
                ['agency_id', 'entity_type', 'entity_id', 'rule_code', 'source_id'],
                'gideon_opp_scan_dedupe'
            );
        });
    }

    public function down(): void
    {
        Schema::table('gideon_opportunities', function (Blueprint $table) {
            $table->dropUnique('gideon_opp_scan_dedupe');

            $table->dropColumn([
                'rule_code',
                'source_type',
                'source_id',
                'due_at',
                'first_detected_at',
                'last_detected_at',
            ]);
        });
    }
};
