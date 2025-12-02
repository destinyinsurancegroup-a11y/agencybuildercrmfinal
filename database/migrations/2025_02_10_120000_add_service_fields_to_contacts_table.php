<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Book of Business flag
            if (!Schema::hasColumn('contacts', 'in_book_of_business')) {
                $table->boolean('in_book_of_business')
                    ->default(false)
                    ->after('contact_type')
                    ->index();
            }

            // Current service status (Saved, Back on Books, Not Interested, Cancelled)
            if (!Schema::hasColumn('contacts', 'service_status')) {
                $table->string('service_status')
                    ->nullable()
                    ->after('notes')
                    ->index();
            }

            // When the service was archived
            if (!Schema::hasColumn('contacts', 'service_archived_at')) {
                $table->timestamp('service_archived_at')
                    ->nullable()
                    ->after('service_status')
                    ->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'in_book_of_business')) {
                $table->dropColumn('in_book_of_business');
            }
            if (Schema::hasColumn('contacts', 'service_status')) {
                $table->dropColumn('service_status');
            }
            if (Schema::hasColumn('contacts', 'service_archived_at')) {
                $table->dropColumn('service_archived_at');
            }
        });
    }
};
