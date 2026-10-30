<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** True/false and essay questions carry no option list. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->longText('options')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Kept nullable: reverting could fail on rows without options.
    }
};
