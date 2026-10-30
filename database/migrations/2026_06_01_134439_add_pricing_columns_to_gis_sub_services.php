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
        Schema::table('gis_sub_services', function (Blueprint $table) {
            $table->boolean('has_vat')->default(false)->after('pricing_settings');
            $table->decimal('martyr_stamp_fee', 8, 2)->default(5.00)->after('has_vat');
            $table->decimal('sms_fee', 8, 2)->default(10.00)->after('martyr_stamp_fee');
        });
    }

    public function down(): void
    {
        Schema::table('gis_sub_services', function (Blueprint $table) {
            $table->dropColumn(['has_vat', 'martyr_stamp_fee', 'sms_fee']);
        });
    }
};
