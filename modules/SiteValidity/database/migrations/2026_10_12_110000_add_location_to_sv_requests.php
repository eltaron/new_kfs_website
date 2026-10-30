<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** موقع الأرض على الخريطة (اختياري): يحدده المواطن عند التقديم أو الموظف من «تحديد موقع الأرض». */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sv_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('sv_requests', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('address');
            }
            if (! Schema::hasColumn('sv_requests', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sv_requests', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['latitude', 'longitude'], fn ($c) => Schema::hasColumn('sv_requests', $c))));
        });
    }
};
