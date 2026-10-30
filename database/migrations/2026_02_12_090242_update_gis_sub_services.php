<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // الخدمات والمساحة
        Schema::table('gis_sub_services', function (Blueprint $table) {
            $table->string('pricing_type')->default('fixed'); // fixed, formula, tiered
            $table->json('pricing_settings')->nullable(); // هنا نخزن المتر بكام، والقيم الثابتة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
