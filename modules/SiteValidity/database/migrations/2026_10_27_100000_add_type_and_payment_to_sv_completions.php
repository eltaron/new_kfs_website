<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نوع الاستيفاء: مستندات/بيانات (يرد المواطن ويرفع ملفات) أو رسوم إضافية (يسددها المواطن إلكترونياً
 * فيُغلق الاستيفاء تلقائياً). جولة الاستيفاء Payable: route('payments.pay', ['type' => 'site_validity_completion', 'id' => ...]).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sv_completions', function (Blueprint $table) {
            if (! Schema::hasColumn('sv_completions', 'type')) {
                $table->string('type', 16)->default('documents')->after('round');
            }
            if (! Schema::hasColumn('sv_completions', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('type');
            }
            if (! Schema::hasColumn('sv_completions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('replied_at');
                $table->decimal('paid_amount', 10, 2)->nullable()->after('paid_at');
                $table->string('payment_reference', 64)->nullable()->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sv_completions', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['type', 'amount', 'paid_at', 'paid_amount', 'payment_reference'], fn ($c) => Schema::hasColumn('sv_completions', $c))));
        });
    }
};
