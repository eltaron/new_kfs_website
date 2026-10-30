<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment state on every payable model + fulfillment fee amounts +
 * the fulfillment_fees table (extra fees requested from the citizen
 * through the "الاستيفاء" workflow).
 */
return new class extends Migration
{
    public function up(): void
    {
        // service_submissions.status was an enum that rejected
        // 'awaiting_payment' / 'paid' which the code and views rely on.
        Schema::table('service_submissions', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
            if (! Schema::hasColumn('service_submissions', 'payment_status')) {
                $table->string('payment_status', 20)->default('unpaid')->index()->after('total_amount');
            }
            if (! Schema::hasColumn('service_submissions', 'fulfillment_fee_amount')) {
                $table->decimal('fulfillment_fee_amount', 12, 2)->nullable()->after('fulfillment_reason');
            }
        });

        Schema::table('gis_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('gis_submissions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('gis_submissions', 'fulfillment_fee_amount')) {
                $table->decimal('fulfillment_fee_amount', 12, 2)->nullable()->after('fulfillment_reason');
            }
        });

        foreach (['bookings', 'tour_bookings', 'removal_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'payment_status')) {
                    $table->string('payment_status', 20)->default('unpaid');
                }
                if (! Schema::hasColumn($tableName, 'paid_at')) {
                    $table->timestamp('paid_at')->nullable();
                }
            });
        }

        foreach (['bookings', 'tour_bookings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'user_id')) {
                    $table->char('user_id', 36)->nullable()->index();
                }
            });
        }

        if (! Schema::hasTable('fulfillment_fees')) {
            Schema::create('fulfillment_fees', function (Blueprint $table) {
                $table->id();
                $table->string('feeable_type');
                $table->string('feeable_id', 36);
                $table->char('user_id', 36)->nullable()->index();
                $table->decimal('amount', 12, 2);
                $table->string('reason')->nullable();
                $table->string('status', 20)->default('pending'); // pending|paid|cancelled
                $table->char('requested_by', 36)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['feeable_type', 'feeable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_fees');

        foreach (['bookings', 'tour_bookings'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('user_id'));
        }
        foreach (['bookings', 'tour_bookings', 'removal_orders'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn(['payment_status', 'paid_at']));
        }
        Schema::table('gis_submissions', fn (Blueprint $table) => $table->dropColumn(['paid_at', 'fulfillment_fee_amount']));
        Schema::table('service_submissions', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'fulfillment_fee_amount']);
        });
    }
};
