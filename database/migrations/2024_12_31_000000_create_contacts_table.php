<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_type')->nullable();
            $table->string('status')->nullable();
            $table->string('source')->nullable();
            $table->json('tags')->nullable();

            // ADDRESS
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();

            // POLICY FIELDS
            $table->date('date_of_birth')->nullable();
            $table->string('policy_type')->nullable();
            $table->decimal('face_amount', 10, 2)->nullable();
            $table->decimal('premium_amount', 10, 2)->nullable();
            $table->date('premium_due_date')->nullable();

            // NOTES TEXT FIELD
            $table->text('notes')->nullable();

            // BOOK FIELDS
            $table->string('carrier')->nullable();
            $table->date('anniversary')->nullable();
            $table->date('policy_issue_date')->nullable();
            $table->string('premium_due_text')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
