<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Copy agency_id from contacts to contact_relations
        DB::statement('
            UPDATE contact_relations cr
            SET agency_id = c.agency_id
            FROM contacts c
            WHERE cr.contact_id = c.id
              AND cr.agency_id IS NULL
        ');
    }

    public function down(): void
    {
        // Rollback: clear agency_id on all relations
        DB::table('contact_relations')->update(['agency_id' => null]);
    }
};
