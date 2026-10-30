<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('syndicate_projects')) {
            return;
        }

        Schema::create('syndicate_projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique()->comment('كود المشروع');

            // المالك
            $table->string('owner_name');
            $table->string('owner_national_id', 14)->index();
            $table->string('owner_phone', 30)->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_address')->nullable();
            $table->uuid('owner_user_id')->nullable()->index();

            // الموقع
            $table->text('address')->comment('عنوان الموقع بالتفصيل');
            $table->string('center', 100)->index()->comment('المركز / المدينة');
            $table->string('village', 100)->nullable()->comment('القرية / الحي');
            $table->string('plot_number', 50)->nullable()->comment('رقم القطعة');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // الترخيص
            $table->string('license_number', 50)->nullable()->index();
            $table->date('license_date')->nullable();
            $table->string('license_authority')->nullable();
            $table->enum('works_type', ['execution', 'demolition'])->default('execution');
            $table->enum('execution_sub_type', ['creation', 'elevation'])->nullable();
            $table->text('works_description')->nullable();
            $table->decimal('works_value', 15, 2)->nullable();

            // حالة الأعمال
            $table->enum('work_status', ['pending', 'active', 'suspended'])->default('pending')->index();
            $table->timestamp('work_status_changed_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedBigInteger('active_certificate_id')->nullable()->index();

            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicate_projects');
    }
};
