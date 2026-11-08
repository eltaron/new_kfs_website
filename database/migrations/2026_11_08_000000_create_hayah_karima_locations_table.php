<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مواقع تنفيذ مشروعات «حياة كريمة» (مدرسة، وحدة صحية، محطة…) لكل قطاع —
 * تظهر في صفحة تفاصيل القطاع وعلى الخريطة التفاعلية.
 * Idempotent (production schema can drift from migrations).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hayah_karima_locations')) {
            return;
        }

        Schema::create('hayah_karima_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hayah_karima_project_id')->constrained('hayah_karima_projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('center')->nullable();
            $table->string('village')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->nullable();          // e.g. «تم التنفيذ» / «جارٍ التنفيذ»
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['hayah_karima_project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hayah_karima_locations');
    }
};
