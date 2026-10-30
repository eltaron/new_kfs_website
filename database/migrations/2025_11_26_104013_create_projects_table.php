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
        // سننشئ جدولًا وسيطًا لربط المشاريع بالاستثمارات
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('investment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('thumbnail'); // صورة رئيسية للمشروع
            $table->string('type')->comment('مثلاً: صورة, شعار');
            $table->longText('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); // إحداثيات الخريطة
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_highlighted')->default(false);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
