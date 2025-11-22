<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            // Ties beneficiary to a Book-of-Business client/contact
            $table->unsignedBigInteger('contact_id');

            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Multi-tenant protection
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('beneficiaries');
    }
};
