<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('removal_orders', 'created_by')) {
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('photo_file');
            }
            if (! Schema::hasColumn('removal_orders', 'assigned_to')) {
                $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }
            if (! Schema::hasColumn('removal_orders', 'assigned_by')) {
                $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete()->after('assigned_to');
            }
            if (! Schema::hasColumn('removal_orders', 'stage')) {
                $table->string('stage')->default('created')->after('assigned_by');
            }
            if (! Schema::hasColumn('removal_orders', 'spatial_data')) {
                $table->json('spatial_data')->nullable()->after('stage');
            }
            if (! Schema::hasColumn('removal_orders', 'pdf_file')) {
                $table->string('pdf_file')->nullable()->after('spatial_data');
            }
            if (! Schema::hasColumn('removal_orders', 'visa_file')) {
                $table->string('visa_file')->nullable()->after('pdf_file');
            }
            if (! Schema::hasColumn('removal_orders', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('visa_file');
            }
            if (! Schema::hasColumn('removal_orders', 'engineering_engineer_id')) {
                $table->foreignUuid('engineering_engineer_id')->nullable()->constrained('users')->nullOnDelete()->after('review_notes');
            }
            if (! Schema::hasColumn('removal_orders', 'spatial_manager_id')) {
                $table->foreignUuid('spatial_manager_id')->nullable()->constrained('users')->nullOnDelete()->after('engineering_engineer_id');
            }
            if (! Schema::hasColumn('removal_orders', 'spatial_member_id')) {
                $table->foreignUuid('spatial_member_id')->nullable()->constrained('users')->nullOnDelete()->after('spatial_manager_id');
            }
            if (! Schema::hasColumn('removal_orders', 'systems_specialist_id')) {
                $table->foreignUuid('systems_specialist_id')->nullable()->constrained('users')->nullOnDelete()->after('spatial_member_id');
            }
            if (! Schema::hasColumn('removal_orders', 'governor_office_id')) {
                $table->foreignUuid('governor_office_id')->nullable()->constrained('users')->nullOnDelete()->after('systems_specialist_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('removal_orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['assigned_by']);
            $table->dropForeign(['engineering_engineer_id']);
            $table->dropForeign(['spatial_manager_id']);
            $table->dropForeign(['spatial_member_id']);
            $table->dropForeign(['systems_specialist_id']);
            $table->dropForeign(['governor_office_id']);
            $table->dropColumn([
                'created_by','assigned_to','assigned_by','engineering_engineer_id',
                'spatial_manager_id','spatial_member_id','systems_specialist_id',
                'governor_office_id','stage','spatial_data','pdf_file','visa_file','review_notes'
            ]);
        });
    }
};
