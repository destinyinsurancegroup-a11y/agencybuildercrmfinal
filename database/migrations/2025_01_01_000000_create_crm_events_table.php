<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('crm_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->string('color')->default('#facc15'); // CRM gold
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_events');
    }
};
