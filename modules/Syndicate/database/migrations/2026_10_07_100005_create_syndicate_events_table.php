<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('syndicate_events')) {
            return;
        }

        Schema::create('syndicate_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('syndicate_projects')->cascadeOnDelete();
            $table->unsignedBigInteger('request_id')->nullable()->index();
            $table->unsignedBigInteger('certificate_id')->nullable()->index();
            $table->string('type', 50)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('actor_type', 20)->default('system')->comment('user | api | system');
            $table->string('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicate_events');
    }
};
