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
        Schema::create('emergency_reports', function (Blueprint $table) {
            $table->id();
            // Submitter Info
            $table->string('reporter_name');
            $table->string('reporter_national_id', 14);
            $table->string('reporter_phone');
            $table->string('report_type'); // نوع البلاغ

            // Location Info
            $table->string('location_type'); // مدينة أم قرية
            $table->string('center'); // المركز
            $table->string('area'); // القرية/المدينة
            $table->text('location_description');

            // Geo-location from browser
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Report Details
            $table->text('details')->nullable();
            $table->json('attachments')->nullable(); // Store multiple file paths as JSON array

            // Admin fields
            $table->enum('status', ['new', 'dispatched', 'resolved'])->default('new');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_reports');
    }
};
