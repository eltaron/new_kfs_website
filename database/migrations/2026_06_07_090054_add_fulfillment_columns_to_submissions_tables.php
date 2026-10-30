<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_submissions', function (Blueprint $table) {
            $table->string('fulfillment_status')->default('none')->after('admin_notes');
            $table->string('fulfillment_action')->nullable()->after('fulfillment_status');
            $table->text('fulfillment_reason')->nullable()->after('fulfillment_action');
            $table->json('fulfillment_data_fields')->nullable()->after('fulfillment_reason');
            $table->foreignUuid('fulfillment_requested_by')->nullable()->after('fulfillment_data_fields');
            $table->timestamp('fulfillment_requested_at')->nullable()->after('fulfillment_requested_by');
            $table->timestamp('fulfillment_completed_at')->nullable()->after('fulfillment_requested_at');
        });

        Schema::table('gis_submissions', function (Blueprint $table) {
            $table->string('fulfillment_status')->default('none')->after('status');
            $table->string('fulfillment_action')->nullable()->after('fulfillment_status');
            $table->text('fulfillment_reason')->nullable()->after('fulfillment_action');
            $table->json('fulfillment_data_fields')->nullable()->after('fulfillment_reason');
            $table->foreignUuid('fulfillment_requested_by')->nullable()->after('fulfillment_data_fields');
            $table->timestamp('fulfillment_requested_at')->nullable()->after('fulfillment_requested_by');
            $table->timestamp('fulfillment_completed_at')->nullable()->after('fulfillment_requested_at');
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
