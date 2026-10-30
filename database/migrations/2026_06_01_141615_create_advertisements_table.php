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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('street_name');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('type')->comment('نوع الإعلان: لافتة, باص, تليفزيوني, إلكتروني, وغيرها');
            $table->decimal('height', 8, 2)->nullable()->comment('الارتفاع عن الأرض بالمتر');
            $table->string('size')->nullable()->comment('المقاس (مثلاً 3x2 متر)');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->uuid('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
