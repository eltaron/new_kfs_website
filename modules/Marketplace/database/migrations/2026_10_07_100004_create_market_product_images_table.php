<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('market_products')->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text', 200); // required for accessibility
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_product_images');
    }
};
