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
        // 1. جدول المراكز الخاص بقطاع الـ GIS
        Schema::create('gis_markazs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم المركز: مطوبس، بيلا، إلخ');
            $table->string('gis_code')->unique()->nullable()->comment('كود المركز الرسمي بالخارطة');
            $table->timestamps();
        });

        // 2. جدول الوحدات المحلية / الشياخات التابع لقطاع الـ GIS
        Schema::create('gis_shiakhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gis_markaz_id')->constrained('gis_markazs')->cascadeOnDelete();
            $table->string('name')->comment('اسم الوحدة المحلية');
            $table->string('shiakha_code')->nullable();
            $table->timestamps();
        });

        // 3. جدول القرى والعزب النهائي
        Schema::create('gis_villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gis_shiakha_id')->constrained('gis_shiakhas')->cascadeOnDelete();
            $table->string('name')->comment('اسم القرية أو العزبة');
            $table->boolean('is_ezba')->default(false); // تحديد هل هي عزبة أم قرية
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gis_villages');
    }
};
