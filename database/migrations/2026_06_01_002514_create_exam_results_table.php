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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->string('seat_number', 20)->unique()->comment('رقم الجلوس');
            $table->string('student_name')->comment('اسم الطالب');
            $table->string('school')->nullable()->comment('المدرسة');
            $table->string('academic_year')->nullable()->comment('العام الدراسي');
            $table->decimal('total_grade', 6, 2)->nullable()->comment('المجموع الكلي');
            $table->json('subjects')->nullable()->comment('المواد: {name, grade}');
            $table->string('status')->default('pass')->comment('النتيجة: pass/fail');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
