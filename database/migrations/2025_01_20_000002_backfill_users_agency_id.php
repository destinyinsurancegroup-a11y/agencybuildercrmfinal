<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fetch the first (and currently only) agency ID
        $agencyId = DB::table('agencies')->value('id');

        if ($agencyId) {
            DB::table('users')
                ->whereNull('agency_id')
                ->update(['agency_id' => $agencyId]);
        }
    }

    public function down(): void
    {
        // Reverse the backfill
        DB::table('users')
            ->update(['agency_id' => null]);
    }
};
