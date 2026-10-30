<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Change 'description' to LONGTEXT to accommodate rich text
            $table->longText('description')->change();

            // Add a new nullable column for the external link
            $table->string('link')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Revert 'description' back to TEXT
            $table->text('description')->change();

            // Drop the new column
            $table->dropColumn('link');
        });
    }
};
