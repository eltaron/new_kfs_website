<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('tour_name');
            $table->date('trip_date');
            $table->json('trip_types')->nullable();
            $table->integer('people_count')->nullable();
            $table->string('meals_booking')->nullable();
            $table->text('desouk_to_fouh_details')->nullable();
            $table->text('edfina_to_desouk_details')->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_bookings');
    }
};
