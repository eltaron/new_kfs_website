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
        Schema::create('gis_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_title');     // عنوان التقرير
            $table->string('report_type');      // نوع التقرير (إزالات، خدمات، مالي)
            $table->json('filters_applied')->nullable(); // الفلاتر التي استخدمت لإنشاء التقرير
            $table->string('file_path')->nullable();     // رابط ملف PDF المستخرج
            $table->foreignUuid('user_id')->constrained(); // الموظف الذي استخرج التقرير
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gis_reports');
    }
};
