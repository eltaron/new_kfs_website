<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'is_active')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // الخدمة الموقوفة لا تظهر في الخدمات الإلكترونية ولا يمكن التقديم عليها.
            $table->boolean('is_active')->default(true)->after('is_highlighted');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'is_active')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
