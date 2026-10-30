<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('market_orders')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('market_vendors')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('market_products')->nullOnDelete();
            $table->string('product_name', 150);
            $table->string('variant', 150)->default('');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('vendor_earning', 12, 2)->default(0);
            // pending|processing|shipped|delivered|cancelled
            $table->string('fulfillment_status', 20)->default('pending')->index();
            $table->string('tracking_note', 255)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('payout_id')->nullable()->constrained('market_payouts')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'fulfillment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_order_items');
    }
};
