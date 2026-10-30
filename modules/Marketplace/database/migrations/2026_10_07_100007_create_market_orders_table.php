<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            // pending_payment|payment_failed|paid|processing|shipped|delivered|completed|cancelled
            $table->string('status', 20)->default('pending_payment')->index();
            $table->string('payment_method', 20)->default('efinance'); // efinance|cod
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('commission_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('shipping_name', 120);
            $table->string('shipping_phone', 20);
            $table->string('shipping_center', 100);
            $table->string('shipping_address', 255);
            $table->string('notes', 500)->nullable();
            $table->boolean('stock_reserved')->default(false);   // reserved_stock currently held
            $table->boolean('stock_committed')->default(false);  // stock already decremented
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('needs_review')->default(false); // e.g. paid after stock ran out
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_orders');
    }
};
