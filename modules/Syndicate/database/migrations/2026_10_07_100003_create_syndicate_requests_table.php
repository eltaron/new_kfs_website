<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('syndicate_requests')) {
            return;
        }

        Schema::create('syndicate_requests', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 40)->unique()->comment('رقم المعاملة');
            $table->enum('type', ['supervision', 'cancellation'])->index();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'completed'])->default('submitted')->index();
            $table->enum('requested_by', ['citizen', 'engineer', 'council'])->default('citizen');
            $table->string('channel', 20)->default('counter')->comment('counter | online | api');
            $table->foreignId('project_id')->constrained('syndicate_projects')->cascadeOnDelete();

            // مقدم الطلب
            $table->string('applicant_name');
            $table->string('applicant_national_id', 14)->nullable()->index();
            $table->string('applicant_phone', 30)->nullable();
            $table->string('applicant_email')->nullable();
            $table->uuid('applicant_user_id')->nullable()->index();

            $table->unsignedBigInteger('target_certificate_id')->nullable()->comment('شهادة الإشراف المطلوب إلغاؤها');
            $table->unsignedBigInteger('certificate_id')->nullable()->comment('الشهادة الصادرة رداً على الطلب');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->uuid('registered_by')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicate_requests');
    }
};
