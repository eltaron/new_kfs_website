<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('removal_orders', 'citizen_notification_time')) {
                $table->dateTime('citizen_notification_time')->nullable()->after('governor_decision_date');
            }
            if (! Schema::hasColumn('removal_orders', 'citizen_notification_file')) {
                $table->string('citizen_notification_file')->nullable()->after('citizen_notification_time');
            }
            if (! Schema::hasColumn('removal_orders', 'police_notification_time')) {
                $table->dateTime('police_notification_time')->nullable()->after('citizen_notification_file');
            }
            if (! Schema::hasColumn('removal_orders', 'police_notification_file')) {
                $table->string('police_notification_file')->nullable()->after('police_notification_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            $table->dropColumn([
                'citizen_notification_time',
                'citizen_notification_file',
                'police_notification_time',
                'police_notification_file',
            ]);
        });
    }
};
