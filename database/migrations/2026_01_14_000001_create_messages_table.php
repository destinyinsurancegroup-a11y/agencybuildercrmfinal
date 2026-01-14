<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Multi-tenant identifiers (nullable because your schema uses both in places)
            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->unsignedBigInteger('contact_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();

            // sms | email
            $table->string('channel', 20);

            // outbound | inbound (inbound later for replies/webhooks)
            $table->string('direction', 20)->default('outbound');

            // queued | sent | failed
            $table->string('status', 20)->default('queued');

            // Phone number or email address
            $table->string('to_address', 255);

            // Email only
            $table->string('subject', 255)->nullable();

            // Message content
            $table->text('body');

            // Provider info (later: twilio / ses / smtp / etc.)
            $table->string('provider', 50)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // FK constraints (safe defaults; can be tightened later)
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');

            // created_by points at users, but not every env has strict FK
            // If you want it strict, uncomment:
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['contact_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
