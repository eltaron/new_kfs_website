<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('syndicate_engineers')) {
            return;
        }

        Schema::create('syndicate_engineers', function (Blueprint $table) {
            $table->id();
            $table->string('syndicate_number', 50)->unique()->comment('رقم عضوية النقابة');
            $table->string('national_id', 14)->nullable()->unique()->comment('الرقم القومي');
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('specialization', 100)->nullable()->comment('الشعبة / التخصص');
            $table->string('grade', 50)->nullable()->comment('الدرجة: استشاري / أول / ممارس');
            $table->string('registration_number', 50)->nullable()->comment('رقم السجل الهندسي');
            $table->string('office_name')->nullable()->comment('اسم المكتب الهندسي');
            $table->string('office_registration_number', 50)->nullable();
            $table->enum('membership_status', ['active', 'suspended', 'expired'])->default('active');
            $table->uuid('user_id')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicate_engineers');
    }
};
