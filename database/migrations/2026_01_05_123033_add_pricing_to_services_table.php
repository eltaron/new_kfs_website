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
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('base_price', 8, 2)->default(0)->after('form_fields');
            $table->boolean('has_vat')->default(true)->after('base_price');
            $table->decimal('martyr_stamp_fee', 8, 2)->default(5.00)->after('has_vat');
            $table->decimal('sms_fee', 8, 2)->default(10.00)->after('martyr_stamp_fee');
        });
    }
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'has_vat', 'martyr_stamp_fee', 'sms_fee']);
        });
    }
};
