<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('service_submissions', 'fulfillment_status')) {
                $table->string('fulfillment_status')->default('none')->after('admin_notes');
            }
            if (! Schema::hasColumn('service_submissions', 'fulfillment_action')) {
                $table->string('fulfillment_action')->nullable()->after('fulfillment_status');
            }
            if (! Schema::hasColumn('service_submissions', 'fulfillment_reason')) {
                $table->text('fulfillment_reason')->nullable()->after('fulfillment_action');
            }
            if (! Schema::hasColumn('service_submissions', 'fulfillment_data_fields')) {
                $table->json('fulfillment_data_fields')->nullable()->after('fulfillment_reason');
            }
            if (! Schema::hasColumn('service_submissions', 'fulfillment_requested_by')) {
                $table->foreignUuid('fulfillment_requested_by')->nullable()->after('fulfillment_data_fields');
            }
            if (! Schema::hasColumn('service_submissions', 'fulfillment_requested_at')) {
                $table->timestamp('fulfillment_requested_at')->nullable()->after('fulfillment_requested_by');
            }
            if (! Schema::hasColumn('service_submissions', 'fulfillment_completed_at')) {
                $table->timestamp('fulfillment_completed_at')->nullable()->after('fulfillment_requested_at');
            }
        });

        Schema::table('gis_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_status')) {
                $table->string('fulfillment_status')->default('none')->after('status');
            }
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_action')) {
                $table->string('fulfillment_action')->nullable()->after('fulfillment_status');
            }
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_reason')) {
                $table->text('fulfillment_reason')->nullable()->after('fulfillment_action');
            }
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_data_fields')) {
                $table->json('fulfillment_data_fields')->nullable()->after('fulfillment_reason');
            }
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_requested_by')) {
                $table->foreignUuid('fulfillment_requested_by')->nullable()->after('fulfillment_data_fields');
            }
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_requested_at')) {
                $table->timestamp('fulfillment_requested_at')->nullable()->after('fulfillment_requested_by');
            }
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_completed_at')) {
                $table->timestamp('fulfillment_completed_at')->nullable()->after('fulfillment_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_status', 'fulfillment_action', 'fulfillment_reason',
                'fulfillment_data_fields', 'fulfillment_requested_by',
                'fulfillment_requested_at', 'fulfillment_completed_at',
            ]);
        });

        Schema::table('gis_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_status', 'fulfillment_action', 'fulfillment_reason',
                'fulfillment_data_fields', 'fulfillment_requested_by',
                'fulfillment_requested_at', 'fulfillment_completed_at',
            ]);
        });
    }
};
