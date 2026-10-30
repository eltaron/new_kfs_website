<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('market_carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('market_products')->cascadeOnDelete();
            $table->string('variant', 150)->default('');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_cart_items');
    }
};
