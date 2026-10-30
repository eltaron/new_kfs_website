<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns that exist in the production database but were added manually,
 * so no migration created them. Guarded so it is a no-op where they exist.
 */
return new class extends Migration
{
    private function columns(): array
    {
        return [
            'removal_orders' => [
                'governor_decision_number' => fn (Blueprint $t) => $t->string('governor_decision_number')->nullable(),
                'governor_decision_date' => fn (Blueprint $t) => $t->date('governor_decision_date')->nullable(),
                'notification_to_the_revenue_department_file' => fn (Blueprint $t) => $t->string('notification_to_the_revenue_department_file')->nullable(),
            ],
            'users' => [
                'center' => fn (Blueprint $t) => $t->string('center')->nullable(),
            ],
            'gis_sub_services' => [
                'description' => fn (Blueprint $t) => $t->text('description')->nullable(),
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->columns() as $table => $columns) {
            foreach ($columns as $column => $define) {
                if (! Schema::hasColumn($table, $column)) {
                    Schema::table($table, fn (Blueprint $t) => $define($t));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->columns() as $table => $columns) {
            foreach (array_keys($columns) as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropColumn($column));
                }
            }
        }
    }
};
