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
        Schema::create('gis_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary(); // رقم الطلب (UUID)
            $table->foreignUuid('user_id')->constrained();
            $table->foreignId('gis_sub_service_id')->constrained();
            $table->json('applicant_info'); // الاسم والصفة..
            $table->json('address_info');   // المركز والوحدة والقرية..
            $table->string('request_type'); // جديد / إعادة دراسة / بدل فاقد
            $table->json('form_data');      // إجابات الحقول الديناميكية
            $table->json('attachments');    // البطاقة والتوكيل..
            $table->string('payment_status')->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('received'); // تم استلامه
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gis_submissions');
    }
};
