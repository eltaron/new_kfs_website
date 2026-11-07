<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production's `shiakhas` was created outside the migrations and has no `updated_at`,
 * while the Shiakha model (and CenterCityVillageSeeder::updateOrCreate) write it.
 * Adds the column only when missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shiakhas') && ! Schema::hasColumn('shiakhas', 'updated_at')) {
            Schema::table('shiakhas', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: the column is used by the model.
    }
};
