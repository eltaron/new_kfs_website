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
        Schema::create('hayah_karima_projects', function (Blueprint $table) {
            $table->id();
            $table->string('sector_name');       // اسم القطاع (مثل التعليم، الصحة)
            $table->string('slug')->unique();
            $table->string('icon')->nullable();  // أيقونة للقطاع
            $table->longText('description');      // الوصف التفصيلي
            $table->integer('progress')->default(100); // نسبة التنفيذ
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hayah_karima_projects');
    }
};
