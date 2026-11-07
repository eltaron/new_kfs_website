<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'national_id')) {
                $table->string('national_id', 14)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('national_id');
            }
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('job_title');
            }
            if (! Schema::hasColumn('users', 'national_id_image')) {
                $table->string('national_id_image')->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('pending')->after('national_id_image');
            }
            if (! Schema::hasColumn('users', 'fcm_token')) {
                $table->text('fcm_token')->nullable()->after('status');
            }
        });

        // Add type field to investments
        Schema::table('investments', function (Blueprint $table) {
            if (! Schema::hasColumn('investments', 'type')) {
                $table->tinyInteger('type')->default(0)->after('order');
            }
        });

        // Add missing fields to services
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'parent_id')) {
                $table->foreignUuid('parent_id')->nullable()->after('id')->constrained('services')->nullOnDelete();
            }
            if (! Schema::hasColumn('services', 'title')) {
                $table->string('title')->after('parent_id');
            }
            if (! Schema::hasColumn('services', 'pricing_type')) {
                $table->string('pricing_type')->default('fixed')->after('sms_fee');
            }
            if (! Schema::hasColumn('services', 'category_pricing')) {
                $table->json('category_pricing')->nullable()->after('pricing_type');
            }
            if (! Schema::hasColumn('services', 'insurance_fee')) {
                $table->decimal('insurance_fee', 8, 2)->default(0)->after('category_pricing');
            }
            if (! Schema::hasColumn('services', 'insurance_percentage')) {
                $table->decimal('insurance_percentage', 8, 2)->default(0)->after('insurance_fee');
            }
        });

        // Add address to landmarks
        Schema::table('landmarks', function (Blueprint $table) {
            if (! Schema::hasColumn('landmarks', 'address')) {
                $table->text('address')->nullable()->after('order');
            }
        });

        // Add national_id to suggestions
        Schema::table('suggestions', function (Blueprint $table) {
            if (! Schema::hasColumn('suggestions', 'national_id')) {
                $table->string('national_id', 14)->after('message');
            }
        });

        // Add missing fields to service_submissions
        Schema::table('service_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('service_submissions', 'admin_notes')) {
                $table->longText('admin_notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('service_submissions', 'total_amount')) {
                $table->double('total_amount')->nullable()->after('admin_notes');
            }
            if (! Schema::hasColumn('service_submissions', 'payment_request_number')) {
                $table->text('payment_request_number')->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('service_submissions', 'authorization_code')) {
                $table->text('authorization_code')->nullable()->after('payment_request_number');
            }
            if (! Schema::hasColumn('service_submissions', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('authorization_code');
            }
            if (! Schema::hasColumn('service_submissions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('transaction_number');
            }
        });

        // Add final_file_path to investment_plans
        Schema::table('investment_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('investment_plans', 'final_file_path')) {
                $table->string('final_file_path')->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->dropColumn('final_file_path');
        });
        Schema::table('service_submissions', function (Blueprint $table) {
            $table->dropColumn(['admin_notes', 'total_amount', 'payment_request_number', 'authorization_code', 'transaction_number', 'paid_at']);
        });
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropColumn('national_id');
        });
        Schema::table('landmarks', function (Blueprint $table) {
            $table->dropColumn('address');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'title', 'pricing_type', 'category_pricing', 'insurance_fee', 'insurance_percentage']);
        });
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['national_id', 'phone', 'job_title', 'address', 'national_id_image', 'status', 'fcm_token']);
        });
    }
};
