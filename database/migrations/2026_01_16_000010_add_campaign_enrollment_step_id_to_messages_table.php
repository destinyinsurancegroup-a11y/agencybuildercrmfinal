<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_enrollment_step_id')
                ->nullable()
                ->after('created_by')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['campaign_enrollment_step_id']);
            $table->dropColumn('campaign_enrollment_step_id');
        });
    }
};
