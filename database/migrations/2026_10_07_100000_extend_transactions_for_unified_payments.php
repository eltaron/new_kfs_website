<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified e-Finance payments: the transactions table becomes the single
 * source of truth for every payment attempt (one row per gateway request).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Financial records must survive user deletion: cascade -> null on delete.
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->char('user_id', 36)->nullable()->change();
            // GisSubmission uses UUID keys, other payables use bigint ids.
            $table->string('transactionable_id', 36)->change();
            $table->string('status', 20)->default('pending')->change();

            $table->string('description')->nullable()->after('amount');
            $table->string('sender_request_number', 64)->nullable()->unique()->after('payment_gateway');
            $table->string('payment_request_number', 64)->nullable()->index()->after('sender_request_number');
            $table->string('authorization_code', 64)->nullable()->after('payment_request_number');
            $table->decimal('gateway_amount', 12, 2)->nullable()->after('authorization_code');
            $table->decimal('collection_fees', 12, 2)->nullable()->after('gateway_amount');
            $table->text('raw_response')->nullable()->after('collection_fees');   // encrypted:array cast
            $table->text('aes_secret')->nullable()->after('raw_response');        // encrypted:array cast
            $table->string('failure_reason')->nullable()->after('aes_secret');
            $table->timestamp('expires_at')->nullable()->index()->after('failure_reason');
            $table->timestamp('failed_at')->nullable()->after('completed_at');

            $table->index(['status', 'completed_at']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['status', 'completed_at']);
            $table->dropUnique(['sender_request_number']);
            $table->dropIndex(['payment_request_number']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn([
                'description', 'sender_request_number', 'payment_request_number', 'authorization_code',
                'gateway_amount', 'collection_fees', 'raw_response', 'aes_secret', 'failure_reason',
                'expires_at', 'failed_at',
            ]);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
