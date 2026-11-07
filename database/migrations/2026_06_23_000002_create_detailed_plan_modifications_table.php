<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('detailed_plan_modifications')) {
            Schema::create('detailed_plan_modifications', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->string('plan_number')->comment('رقم المخطط');
                $table->string('plan_name')->comment('اسم المخطط');
                $table->string('location')->nullable()->comment('الموقع');
                $table->text('modification_reason')->comment('سبب التعديل');
                $table->text('description')->nullable()->comment('وصف التعديل المطلوب');
                $table->json('attachments')->nullable()->comment('المرفقات');
                $table->string('status')->default('pending')->comment('pending, in_progress, completed, rejected');
                $table->text('admin_notes')->nullable()->comment('ملاحظات الإدارة');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detailed_plan_modifications');
    }
};
