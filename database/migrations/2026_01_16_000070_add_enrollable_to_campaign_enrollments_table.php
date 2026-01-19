<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaign_enrollments', function (Blueprint $table) {
            // Generic pointer to either a Contact or a Lead (or future types)
            $table->string('enrollable_type', 100)
                ->nullable()
                ->after('contact_id');

            $table->unsignedBigInteger('enrollable_id')
                ->nullable()
                ->after('enrollable_type');

            $table->index(
                ['drip_campaign_id', 'enrollable_type', 'enrollable_id'],
                'campaign_enrollments_campaign_enrollable_idx'
            );
        });

        // Backfill existing rows that were contact-based
        DB::table('campaign_enrollments')
            ->whereNull('enrollable_type')
            ->whereNotNull('contact_id')
            ->update([
                'enrollable_type' => \App\Models\Contact::class,
                'enrollable_id'   => DB::raw('contact_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('campaign_enrollments', function (Blueprint $table) {
            $table->dropIndex('campaign_enrollments_campaign_enrollable_idx');
            $table->dropColumn(['enrollable_type', 'enrollable_id']);
        });
    }
};
