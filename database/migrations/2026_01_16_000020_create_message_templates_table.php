<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('agency_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->string('channel', 20); // sms|email
            $table->string('name', 150);
            $table->string('subject', 255)->nullable();
            $table->text('body');

            $table->boolean('is_active')->default(true);
            $table->jsonb('variables_json')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'tenant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
