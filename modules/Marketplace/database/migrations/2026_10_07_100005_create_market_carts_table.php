<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->nullable()->unique(); // guest cart (token held in session)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_carts');
    }
};
