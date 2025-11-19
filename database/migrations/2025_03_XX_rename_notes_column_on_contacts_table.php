<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'notes')) {
                $table->renameColumn('notes', 'legacy_notes');
            }
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'legacy_notes')) {
                $table->renameColumn('legacy_notes', 'notes');
            }
        });
    }
};
