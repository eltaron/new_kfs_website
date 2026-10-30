<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markazs', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
            $table->string('g_code', 50)->nullable();
            $table->string('gov_name', 100)->nullable()->default('كفر الشيخ');
            $table->integer('qc_process')->nullable()->default(0);
            $table->integer('cities_num')->nullable();
            $table->integer('mun_num')->nullable();
            $table->integer('shiakha_num')->nullable();
            $table->integer('villages_num')->nullable();
            $table->integer('ezab_num')->nullable();
            $table->string('global_id')->nullable();
            $table->decimal('st_area', 20, 10)->nullable();
            $table->decimal('st_length', 20, 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markazs');
    }
};
