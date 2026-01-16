<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drip_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->string('name', 150);
            $table->string('type', 64)->index();   // onboarding_timeline|date_birthday|date_client_anniversary|date_holiday
            $table->string('status', 32)->default('paused')->index(); // active|paused|archived

            // Tier-1: simplest: sms or email per campaign (mixed later)
            $table->string('channel_mode', 16)->default('sms'); // sms|email|mixed

            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_campaigns');
    }
};
