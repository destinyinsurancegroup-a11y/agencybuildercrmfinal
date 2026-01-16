<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drip_steps', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->unsignedBigInteger('drip_campaign_id')->index();
            $table->unsignedBigInteger('template_id')->index();

            $table->unsignedInteger('step_order')->default(1);

            // Onboarding timeline:
            $table->unsignedInteger('delay_days')->nullable();

            // Holiday only:
            $table->char('holiday_mmdd', 5)->nullable(); // "12-25"

            $table->time('send_time_local')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['drip_campaign_id','step_order'], 'drip_steps_campaign_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_steps');
    }
};
