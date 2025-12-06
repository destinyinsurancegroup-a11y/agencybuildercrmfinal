<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First: drop the old foreign key that used SET NULL
        Schema::table('users', function (Blueprint $table) {
            // this name comes from the error message: users_agency_id_foreign
            $table->dropForeign('users_agency_id_foreign');
        });

        // Second: make agency_id required and re-add a new foreign key
        Schema::table('users', function (Blueprint $table) {
            // make agency_id NOT NULL
            $table->unsignedBigInteger('agency_id')->nullable(false)->change();

            // add a new foreign key that cascades on delete (no SET NULL)
            $table->foreign('agency_id')
                ->references('id')
                ->on('agencies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Reverse the above if we ever roll back

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_agency_id_foreign');
        });

        Schema::table('users', function (Blueprint $table) {
            // allow NULL again
            $table->unsignedBigInteger('agency_id')->nullable()->change();

            // restore the old behavior (SET NULL on delete)
            $table->foreign('agency_id')
                ->references('id')
                ->on('agencies')
                ->nullOnDelete();
        });
    }
};
