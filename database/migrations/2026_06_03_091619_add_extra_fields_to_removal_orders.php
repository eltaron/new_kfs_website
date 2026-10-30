<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('removal_orders', 'stop_order_file')) {
                $table->string('stop_order_file')->nullable()->after('stop_order_date');
            }
            if (!Schema::hasColumn('removal_orders', 'violation_report_file')) {
                $table->string('violation_report_file')->nullable()->after('violation_report_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            $table->dropColumn(['stop_order_file', 'violation_report_file']);
        });
    }
};
