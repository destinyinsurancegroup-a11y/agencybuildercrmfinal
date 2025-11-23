<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration originally tried to modify the old `notes` column,
        // which no longer exists. To avoid breaking production, we no-op it.
        //
        // If you later want to tweak contact column nullability,
        // do it in a fresh dedicated migration instead.
    }

    public function down(): void
    {
        // No-op on rollback as well.
    }
};
