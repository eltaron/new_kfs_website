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
        Schema::create('governorate_details', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // مثلا: history, naming_reason, vision
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('icon')->nullable(); // للأيقونات المستخدمة في قسم "بماذا تشتهر"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('governorate_details');
    }
};
