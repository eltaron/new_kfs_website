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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('thumbnail'); // صورة مصغرة رئيسية للمقال
            $table->longText('content'); // لاستيعاب محرر نصوص غني
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->date('published_at')->nullable();
            $table->boolean('allow_comments')->default(true);
            $table->integer('likes_count')->default(0); // عدد الإعجابات
            $table->integer('shares_count')->default(0); // عدد المشاركات
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
