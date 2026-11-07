<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * On the production server `centers` exists without a PRIMARY KEY / AUTO_INCREMENT
 * (it was created outside the migrations), so the next migration's foreign key
 * city_villages.center_id → centers.id fails with errno 150.
 * Repairs the key only when it is missing — a no-op on databases built by migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('centers') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $hasPrimary = DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'centers')
            ->where('constraint_type', 'PRIMARY KEY')
            ->exists();

        if (! $hasPrimary) {
            DB::statement('ALTER TABLE `centers` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)');
        }

        // Same table on production also lacks the unique slug index from the original migration.
        $hasSlugUnique = Schema::hasColumn('centers', 'slug') && DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'centers')
            ->where('column_name', 'slug')
            ->where('non_unique', 0)
            ->exists();

        if (Schema::hasColumn('centers', 'slug') && ! $hasSlugUnique) {
            DB::statement('ALTER TABLE `centers` ADD UNIQUE `centers_slug_unique` (`slug`)');
        }
    }

    public function down(): void
    {
        // Intentionally empty: the key is required by city_villages.center_id.
    }
};
