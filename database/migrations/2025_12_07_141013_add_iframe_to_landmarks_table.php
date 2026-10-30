<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landmarks', function (Blueprint $table) {
            $table->text('iframe')->nullable()->after('details');
        });
    }
    public function down(): void
    {
        Schema::table('landmarks', function (Blueprint $table) {
            $table->dropColumn('iframe');
        });
    }
};
