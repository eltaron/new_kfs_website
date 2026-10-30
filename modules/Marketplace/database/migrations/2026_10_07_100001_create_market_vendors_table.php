<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('store_name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            // Integrated services card (بطاقة الخدمات المتكاملة) / disability proof
            $table->string('disability_card_number', 50);
            $table->string('disability_card_path')->nullable(); // private disk
            $table->string('national_id', 14);
            $table->string('phone', 20);
            $table->string('center', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('payout_method', 20)->default('wallet'); // bank|wallet
            $table->text('payout_details')->nullable(); // encrypted json
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected|suspended
            $table->text('review_notes')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable(); // null => global default
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_vendors');
    }
};
