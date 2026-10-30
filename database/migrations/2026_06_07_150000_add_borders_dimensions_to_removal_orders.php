<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            $table->string('border_east')->nullable()->after('violation_dimensions');
            $table->string('border_west')->nullable()->after('border_east');
            $table->string('border_north')->nullable()->after('border_west');
            $table->string('border_south')->nullable()->after('border_north');
            $table->string('dimension_east')->nullable()->after('border_south');
            $table->string('dimension_north')->nullable()->after('dimension_east');
            $table->string('dimension_south')->nullable()->after('dimension_north');
            $table->string('dimension_west')->nullable()->after('dimension_south');
            $table->decimal('area', 15, 2)->nullable()->after('dimension_west');
            $table->text('engineer_description')->nullable()->after('area');
        });
    }

    public function down(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            $table->dropColumn([
                'border_east', 'border_west', 'border_north', 'border_south',
                'dimension_east', 'dimension_north', 'dimension_south', 'dimension_west',
                'area', 'engineer_description',
            ]);
        });
    }
};
