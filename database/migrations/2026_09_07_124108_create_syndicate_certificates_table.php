<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syndicate_certificates', function (Blueprint $table) {
            $table->id();

            // نوع التدخل
            $table->enum('type', ['execution', 'demolition'])->comment('نوع التدخل: تنفيذ أو هدم');
            $table->enum('execution_sub_type', ['creation', 'elevation'])->nullable()->comment('نوع التنفيذ: إنشاء أو تعلية');

            // بيانات المهندس المشرف
            $table->string('engineer_name')->comment('اسم المهندس');
            $table->string('engineer_address')->comment('عنوان الإقامة');
            $table->string('engineer_national_id')->comment('الرقم القومي للمهندس');
            $table->string('engineer_syndicate_number')->comment('رقم عضوية النقابة');
            $table->string('engineer_department')->comment('الشعبة - التخصص');
            $table->string('engineer_registration_number')->comment('رقم السجل الهندسي');

            // بيانات المالك
            $table->string('owner_name')->comment('اسم المالك');
            $table->string('owner_address')->comment('عنوان المالك');
            $table->string('owner_national_id')->comment('الرقم القومي للمالك');

            // بيانات الترخيص
            $table->string('license_number')->comment('رقم الترخيص');
            $table->date('license_date')->comment('تاريخ الترخيص');
            $table->string('license_authority')->comment('جهة صدور الترخيص');

            // بيانات المشروع
            $table->text('project_address')->comment('عنوان المشروع بالتفصيل');
            $table->string('city')->comment('المدينة');
            $table->string('governorate')->comment('المحافظة');
            $table->text('licensed_works_description')->comment('بيان الأعمال المرخص بها');

            // قيمة الأعمال
            $table->decimal('works_value_numbers', 15, 2)->comment('قيمة الأعمال بالأرقام');
            $table->string('works_value_words')->comment('قيمة الأعمال بالحروف');

            // المرفق
            $table->string('certificate_file')->nullable()->comment('ملف شهادة الإشراف على التنفيذ');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicate_certificates');
    }
};
