<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * خدمة «طلب صلاحية الموقع» (Modules\SiteValidity) — كل الجداول بالبادئة sv_.
 * إضافية فقط (لا تمس جداول أخرى). المراكز والقرى تُقرأ من centers / city_villages الموجودة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sv_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sv_inspection_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label');
            $table->string('type', 20)->default('yes_no'); // yes_no | text | number | select | textarea
            $table->json('options')->nullable();            // لخيارات النوع select
            $table->string('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sv_attachment_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('extensions')->default('pdf,jpg,jpeg,png');
            $table->unsignedInteger('max_size_kb')->default(20480);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sv_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('sv_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->nullable()->unique();
            $table->char('user_id', 36)->index();
            $table->foreignId('service_type_id')->nullable()->constrained('sv_service_types')->nullOnDelete();
            $table->string('status', 32)->default('submitted')->index();

            $table->string('applicant_name');
            $table->string('national_id', 14)->index();
            $table->string('phone', 20)->nullable();
            $table->unsignedBigInteger('center_id')->nullable()->index();
            $table->unsignedBigInteger('village_id')->nullable();
            $table->string('center_name')->nullable();
            $table->string('village_name')->nullable();
            $table->string('address', 300)->nullable();
            $table->text('notes')->nullable();

            $table->decimal('fee', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->string('payment_reference', 64)->nullable();

            $table->char('geo_user_id', 36)->nullable()->index();
            $table->char('engineer_user_id', 36)->nullable()->index();

            $table->timestamp('appointment_at')->nullable()->index();
            $table->text('appointment_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('geo_inspected_at')->nullable();
            $table->text('geo_inspection_notes')->nullable();
            $table->timestamp('engineer_inspected_at')->nullable();
            $table->json('inspection_data')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->timestamp('attachments_uploaded_at')->nullable();
            $table->unsignedInteger('completion_rounds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->text('final_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sv_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('sv_requests')->cascadeOnDelete();
            $table->unsignedInteger('round')->default(1);
            $table->text('notes');
            $table->json('requested_items')->nullable();
            $table->char('requested_by', 36)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sv_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('sv_requests')->cascadeOnDelete();
            $table->string('kind', 20)->index(); // sketch | contract | id_card | attachment | completion | final
            $table->foreignId('attachment_type_id')->nullable()->constrained('sv_attachment_types')->nullOnDelete();
            $table->foreignId('completion_id')->nullable()->constrained('sv_completions')->nullOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->char('uploaded_by', 36)->nullable();
            $table->timestamps();
        });

        Schema::create('sv_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('sv_requests')->cascadeOnDelete();
            $table->char('user_id', 36)->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 40)->nullable();
            $table->string('action', 40);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sv_request_events');
        Schema::dropIfExists('sv_request_files');
        Schema::dropIfExists('sv_completions');
        Schema::dropIfExists('sv_requests');
        Schema::dropIfExists('sv_settings');
        Schema::dropIfExists('sv_attachment_types');
        Schema::dropIfExists('sv_inspection_fields');
        Schema::dropIfExists('sv_service_types');
    }
};
