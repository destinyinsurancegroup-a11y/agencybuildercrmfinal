<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get the first (current) agency ID
        $agencyId = DB::table('agencies')->value('id');

        if ($agencyId) {
            DB::table('leads')
                ->whereNull('agency_id')
                ->update(['agency_id' => $agencyId]);
        }
    }

    public function down(): void
    {
        // Rollback: clear agency_id on all leads
        DB::table('leads')->update(['agency_id' => null]);
    }
};
