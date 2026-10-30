<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            // To store rich text content
            $table->longText('description')->nullable()->after('thumbnail');
            // To store the iframe code
            $table->text('map_iframe')->nullable()->after('description');
        });
    }
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['description', 'map_iframe']);
        });
    }
};
