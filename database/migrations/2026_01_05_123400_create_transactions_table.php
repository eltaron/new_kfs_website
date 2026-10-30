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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Polymorphic relation to link to any model (ServiceSubmission, Enrollment, etc.)
            $table->morphs('transactionable');

            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('payment_gateway')->nullable(); // e.g., 'Fawry', 'Stripe'
            $table->string('gateway_transaction_id')->nullable()->unique(); // ID from the payment provider

            $table->json('metadata')->nullable(); // To store extra details like VAT, fees, etc.

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
