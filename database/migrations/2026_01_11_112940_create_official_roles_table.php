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
        Schema::create('official_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('official_id')->constrained()->cascadeOnDelete();
            $table->enum('role_name', ['governor', 'deputy_governor', 'secretary_general', 'assistant_secretary_general']);
            $table->boolean('is_current')->default(false);
            $table->year('start_year')->nullable();
            $table->year('end_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('official_roles');
    }
};
