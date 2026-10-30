<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shiakhas', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('shiakha_num')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('shiakha_c_code', 50)->nullable();
            $table->string('shiakha_g_code', 50)->nullable();
            $table->string('municipality_name', 255)->nullable();
            $table->string('municipality_code', 50)->nullable();
            $table->string('markaz_name', 255)->nullable();
            $table->string('markaz_code', 50)->nullable();
            $table->string('gov_code', 20)->nullable();
            $table->string('gov_name', 100)->nullable();
            $table->string('global_id', 255)->nullable();
            $table->decimal('st_area', 25, 10)->nullable();
            $table->decimal('st_length', 25, 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shiakhas');
    }
};
