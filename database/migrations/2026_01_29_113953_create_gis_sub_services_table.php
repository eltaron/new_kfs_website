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
        Schema::create('gis_sub_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gis_service_type_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // مثلا: رخصة بناء
            $table->string('slug')->unique();
            $table->string('video_url')->nullable();
            $table->longText('terms_conditions');
            $table->longText('requirements');
            $table->json('dynamic_fields')->nullable(); // الحقول المضافة من الإدمن (text, select, file)
            $table->decimal('base_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gis_sub_services');
    }
};
