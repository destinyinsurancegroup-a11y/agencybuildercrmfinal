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
            $table->unsignedBigInteger('tenant_id')->index(); // multi-tenant isolation

            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->index();

            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();

            $table->string('contact_type')->default('lead');   // lead, prospect, client, etc.
            $table->string('status')->default('active');       // active, inactive, lost, etc.
            $table->string('source')->nullable();              // referral, web, list, etc.

            $table->jsonb('tags')->nullable();

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();

            $table->date('date_of_birth')->nullable();

            // Simple policy-related fields for now (can be normalized to policies later)
            $table->string('policy_type')->nullable();
            $table->decimal('face_amount', 12, 2)->nullable();
            $table->decimal('premium_amount', 12, 2)->nullable();
            $table->date('premium_due_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Optional foreign keys – keep commented if your tenant/users tables differ
            // $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
