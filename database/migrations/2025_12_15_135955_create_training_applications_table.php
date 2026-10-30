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
        Schema::create('training_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained('training_programs')->cascadeOnDelete();

            // Personal Information
            $table->string('applicant_email');
            $table->string('applicant_name');
            $table->string('national_id', 14)->unique();
            $table->string('phone');
            $table->string('gender');
            $table->string('educational_qualification');
            $table->string('specialization')->nullable(); // التخصص
            $table->string('highest_degree')->nullable(); // أعلى مؤهل

            // Work Information
            $table->string('employment_status'); // جهة العمل
            $table->string('current_position'); // الوظيفة الحالية
            $table->string('job_address'); // عنوان الوظيفة

            // Attachments
            $table->string('national_id_front_image');
            $table->string('national_id_back_image');
            $table->string('personal_statement')->nullable(); // بيان حالة مدون

            // Survey Questions
            $table->boolean('has_taken_previous_courses');
            $table->text('previous_courses_names')->nullable()->comment('To be filled if has_taken_previous_courses is true');

            // Admin fields
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_applications');
    }
};
