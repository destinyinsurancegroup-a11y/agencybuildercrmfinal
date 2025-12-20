<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'tenant_id') && !Schema::hasColumn('activities', 'agency_id')) {
                $table->renameColumn('tenant_id', 'agency_id');
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            try {
                $table->dropIndex(['tenant_id', 'user_id']);
            } catch (\Throwable $e) {}

            try {
                $table->index(['agency_id', 'user_id']);
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'agency_id') && !Schema::hasColumn('activities', 'tenant_id')) {
                $table->renameColumn('agency_id', 'tenant_id');
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            try {
                $table->dropIndex(['agency_id', 'user_id']);
            } catch (\Throwable $e) {}

            try {
                $table->index(['tenant_id', 'user_id']);
            } catch (\Throwable $e) {}
        });
    }
};
