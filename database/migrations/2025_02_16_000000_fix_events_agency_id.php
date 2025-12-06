<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add agency_id (the correct tenant field)
            $table->foreignId('agency_id')
                ->after('id')
                ->nullable()
                ->constrained('agencies')
                ->cascadeOnDelete();

            // Remove the wrong tenant_id field
            $table->dropColumn('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Restore tenant_id if rolling back
            $table->unsignedBigInteger('tenant_id')->default(1);

            $table->dropConstrainedForeignId('agency_id');
        });
    }
};
