<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * MySQL-compatible backfill.
         *
         * Copy agency_id from contacts into contact_relations rows
         * based on matching contact_id.
         */
        DB::statement("
            UPDATE contact_relations cr
            JOIN contacts c ON cr.contact_id = c.id
            SET cr.agency_id = c.agency_id
            WHERE cr.agency_id IS NULL
        ");
    }

    public function down(): void
    {
        // Rollback behavior: clear agency_id on all contact_relations
        DB::table('contact_relations')->update(['agency_id' => null]);
    }
};
