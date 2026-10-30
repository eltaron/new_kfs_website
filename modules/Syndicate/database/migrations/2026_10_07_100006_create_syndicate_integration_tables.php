<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('syndicate_integration_accounts')) {
            Schema::create('syndicate_integration_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->json('allowed_ips')->nullable()->comment('قائمة IP/CIDR إضافية خاصة بالحساب');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('syndicate_api_logs')) {
            Schema::create('syndicate_api_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('integration_account_id')->nullable()->index();
                $table->unsignedBigInteger('token_id')->nullable();
                $table->string('method', 10);
                $table->string('path');
                $table->string('route_name')->nullable();
                $table->string('ip', 45)->nullable()->index();
                $table->unsignedSmallInteger('status_code')->index();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->string('idempotency_key')->nullable();
                $table->json('request_body')->nullable();
                $table->string('error_code')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (! Schema::hasTable('syndicate_idempotency_keys')) {
            Schema::create('syndicate_idempotency_keys', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('integration_account_id');
                $table->string('key', 100);
                $table->string('method', 10);
                $table->string('path');
                $table->string('request_hash', 64);
                $table->unsignedSmallInteger('response_status');
                $table->longText('response_body');
                $table->timestamps();
                $table->unique(['integration_account_id', 'key']);
            });
        }

        if (! Schema::hasTable('syndicate_sequences')) {
            Schema::create('syndicate_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('name', 60)->unique();
                $table->unsignedInteger('last_value')->default(0);
                $table->timestamps();
            });
        }

        // جدول توكنات Sanctum (غير منشأ مسبقاً في هذا المشروع) — محمي بفحص الوجود.
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->text('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicate_sequences');
        Schema::dropIfExists('syndicate_idempotency_keys');
        Schema::dropIfExists('syndicate_api_logs');
        Schema::dropIfExists('syndicate_integration_accounts');
        // personal_access_tokens قد يكون مستخدماً من وحدات أخرى؛ لا نحذفه.
    }
};
