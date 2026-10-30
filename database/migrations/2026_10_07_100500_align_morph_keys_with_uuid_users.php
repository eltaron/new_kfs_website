<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Users have UUID primary keys, but the original permission / activity-log
 * migrations created bigint morph keys. Production already uses string keys
 * (model_has_roles.model_id varchar(255), activity_log.*_id char(36)); fresh
 * installs (and the dev/test databases) could not assign roles to users.
 * This aligns the schema with production. It only runs on MySQL/MariaDB and
 * only touches columns that are still integer typed.
 */
return new class extends Migration
{
    protected array $columns = [
        // table => [column => [definition, nullable]]
        'model_has_roles' => ['model_id' => ['VARCHAR(255)', false]],
        'model_has_permissions' => ['model_id' => ['VARCHAR(255)', false]],
        'activity_log' => ['subject_id' => ['CHAR(36)', true], 'causer_id' => ['CHAR(36)', true]],
    ];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($this->columns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => [$definition, $nullable]) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $type = strtolower(Schema::getColumnType($table, $column));
                if (! in_array($type, ['bigint', 'integer', 'int', 'biginteger'], true)) {
                    continue;
                }

                DB::statement(sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` %s %s',
                    $table,
                    $column,
                    $definition,
                    $nullable ? 'NULL' : 'NOT NULL'
                ));
            }
        }
    }

    public function down(): void
    {
        // Irreversible on purpose: converting UUID strings back to bigint would lose data.
    }
};
