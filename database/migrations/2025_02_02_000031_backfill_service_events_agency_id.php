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
         * The old migration used Postgres syntax:
         *   UPDATE service_events ... FROM contacts
         *
         * MySQL requires JOIN syntax instead.
         */
        DB::statement("
            UPDATE service_events se
            JOIN contacts c ON se.contact_id = c.id
            SET se.agency_id = c.agency_id
            WHERE se.agency_id IS NULL
        ");
    }

    public function down(): void
    {
        // Rollback behavior: remove agency_id values (optional)
        DB::table('service_events')->update(['agency_id' => null]);
    }
};
