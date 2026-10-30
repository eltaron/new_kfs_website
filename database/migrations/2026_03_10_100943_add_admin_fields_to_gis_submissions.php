<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('gis_submissions', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->comment('رقم المسلسل');
            $table->dateTime('inspection_date')->nullable()->comment('موعد المعاينة');
            $table->boolean('is_inspection_confirmed')->default(false)->comment('تأكيد موعد المعاينة');

            // الاشتراطات التخطيطية والحدود (نخزنها كـ JSON لسهولة التعامل)
            $table->json('urban_planning')->nullable();
            $table->json('borders')->nullable();

            $table->text('web_map_url')->nullable()->comment('رابط الخريطة الرقمية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gis_submissions', function (Blueprint $table) {
            //
        });
    }
};
