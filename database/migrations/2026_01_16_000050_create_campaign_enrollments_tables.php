<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_enrollments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->unsignedBigInteger('drip_campaign_id')->index();
            $table->unsignedBigInteger('contact_id')->index();

            $table->string('status', 32)->default('active')->index(); // active|paused|completed|canceled
            $table->string('source', 32)->default('auto');            // auto|manual|import
            $table->unsignedBigInteger('enrolled_by')->nullable()->index();

            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();

            $table->timestamps();

            $table->unique(['drip_campaign_id','contact_id'], 'campaign_enrollments_campaign_contact_unique');
        });

        Schema::create('campaign_enrollment_steps', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->unsignedBigInteger('campaign_enrollment_id')->index();
            $table->unsignedBigInteger('drip_step_id')->index();

            $table->timestamp('scheduled_for')->index();
            $table->string('status', 32)->default('scheduled')->index(); // scheduled|queued|sent|skipped|failed

            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('skipped_at')->nullable();

            $table->string('skip_reason', 64)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(
                ['campaign_enrollment_id','drip_step_id','scheduled_for'],
                'enrollment_steps_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_enrollment_steps');
        Schema::dropIfExists('campaign_enrollments');
    }
};
