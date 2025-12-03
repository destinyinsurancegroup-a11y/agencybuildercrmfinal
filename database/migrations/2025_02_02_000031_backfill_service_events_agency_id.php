<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Copy agency_id from contacts to service_events
        DB::statement('
            UPDATE service_events se
            SET agency_id = c.agency_id
            FROM contacts c
            WHERE se.contact_id = c.id
              AND se.agency_id IS NULL
        ');
    }

    public function down(): void
    {
        // Rollback: clear agency_id on all service events
        DB::table('service_events')->update(['agency_id' => null]);
    }
};
