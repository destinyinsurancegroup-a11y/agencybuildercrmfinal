<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('carrier')->nullable();
            $table->date('anniversary')->nullable();
            $table->date('policy_issue_date')->nullable();
            $table->string('premium_due_text')->nullable(); // “3rd”, “2nd Wednesday”, etc.
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'carrier',
                'anniversary',
                'policy_issue_date',
                'premium_due_text'
            ]);
        });
    }
};
