<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('drip_campaigns')
            ->where('type', 'date_client_anniversary')
            ->update(['type' => 'policy_anniversary']);
    }

    public function down(): void
    {
        DB::table('drip_campaigns')
            ->where('type', 'policy_anniversary')
            ->update(['type' => 'date_client_anniversary']);
    }
};
