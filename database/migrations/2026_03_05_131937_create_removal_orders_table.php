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
        Schema::create('removal_orders', function (Blueprint $table) {
            $table->id();
            // نوع المخالفة
            $table->string('violation_type'); // new_violation, licensed_violation

            // بيانات الترخيص (nullable)
            $table->string('license_number')->nullable();
            $table->date('license_date')->nullable();
            $table->text('licensed_works')->nullable();

            // بيانات الموقع
            $table->string('center');
            $table->string('local_unit');
            $table->string('street');
            $table->string('violation_area');
            $table->string('district');

            // بيانات المالك
            $table->string('owner_name');
            $table->string('owner_national_id');
            $table->string('owner_center');
            $table->string('owner_unit');
            $table->string('owner_street');
            $table->string('owner_district');
            $table->string('owner_governorate');

            // المهندس والمقاول
            $table->string('engineer_name');
            $table->string('engineer_national_id');
            $table->string('contractor_name');
            $table->string('contractor_national_id');

            // تفاصيل المخالفة
            $table->string('violation_plot');
            $table->string('violation_dimensions');
            $table->decimal('violation_cost', 15, 2);
            $table->text('violation_works');

            // القرارات الرسمية
            $table->string('stop_order_number');
            $table->date('stop_order_date');
            $table->string('violation_report_number');
            $table->date('report_date');
            $table->date('announcement_date');

            // الحالة والمرفقات
            $table->string('status')->default('قيد الإعداد');
            $table->string('sketch_file')->nullable();
            $table->string('photo_file')->nullable();

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('removal_orders');
    }
};
