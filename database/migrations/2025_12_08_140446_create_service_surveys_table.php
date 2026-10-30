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
        Schema::create('service_surveys', function (Blueprint $table) {
            $table->id();

            // Part 0: Personal & Center Information
            $table->string('center_name');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('age_group');
            $table->string('gender');

            // Part 1: Service Quality Assessment (أولاً: مدى رضاك عن الخدمات المقدمة)
            $table->tinyInteger('q1_1_accessibility'); // سهولة الوصول
            $table->tinyInteger('q1_2_procedure_clarity'); // وضوح إجراءات
            $table->tinyInteger('q1_3_needs_fulfillment'); // تلبية الاحتياجات
            $table->tinyInteger('q1_4_guidance'); // الإرشاد حول استخدام بوابة الخدمات الحكومية
            $table->tinyInteger('q1_5_staff_cooperation'); // تعاون الموظفين
            $table->tinyInteger('q1_6_process_handling'); // كيفية التعامل معها

            // Part 2: Service Speed Assessment (ثانياً: تقييم سرعة تقديم الخدمات)
            $table->tinyInteger('q2_1_service_speed'); // سرعة تقديم الخدمة
            $table->tinyInteger('q2_2_wait_time'); // مدة الانتظار
            $table->tinyInteger('q2_3_delay_justification'); // وضوح تبرير التأخير

            // Part 3: Staff Performance Assessment (ثالثًا: تقييم سرعة أداء الموظفين)
            $table->tinyInteger('q3_1_staff_treatment'); // تعامل الموظفين
            $table->tinyInteger('q3_2_problem_solving'); // اهتمام بحل المشكلات
            $table->tinyInteger('q3_3_communication_ease'); // سهولة التواصل
            $table->tinyInteger('q3_4_fees_clarity'); // وضوح وشفافية الرسوم

            // Part 4: Center Environment Assessment (رابعًا: تقييم بيئة المركز التكنولوجي)
            $table->tinyInteger('q4_1_cleanliness'); // نظافة وتنظيم
            $table->tinyInteger('q4_2_seating_comfort'); // مناسبة وراحة أماكن الجلوس
            $table->tinyInteger('q4_3_accessibility_tools'); // الوسائل التكنولوجية المتاحة لذوي الهمم

            // Part 5: Suggestions and Recommendations (خامسًا: الاقتراحات والتوصيات)
            $table->text('suggestions')->nullable();

            // Part 6: Complaint about a specific employee (شكوى من موظف معين إن وجد)
            $table->string('complaint_employee_name')->nullable();
            $table->text('complaint_reason')->nullable();

            // Status for admin panel to track if it's been reviewed
            $table->boolean('is_reviewed')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_surveys');
    }
};
