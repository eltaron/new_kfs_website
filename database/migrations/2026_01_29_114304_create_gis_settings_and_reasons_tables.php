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
        // أسباب إعادة الدراسة التي تظهر في الـ Select
        Schema::create('gis_study_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason_text'); // النص: "تعديل في المساحة"، "تظلم".. إلخ
            $table->timestamps();
        });

        // جدول السجلات الورقية (تأمين رقم الشهادة)
        Schema::create('gis_certificates_archive', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->string('owner_name');
            $table->date('issue_date');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gis_settings_and_reasons_tables');
    }
};
