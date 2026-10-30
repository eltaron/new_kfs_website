<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * صفة مقدم الطلب (المالك نفسه / وكيل عنه — يرفق صورة التوكيل)
 * ونوع الصلاحية (إنشاء جديد / تعلية — يرفق ملف موقف الأدوار القائمة).
 * استمارة المعاينة: ارتفاع المبنى القائم (للتعلية) + آخر تعديل للاستمارة بعد حفظها،
 * كود استلام المعاملة من المركز التكنولوجي (يُولد عند اعتماد مدير التنظيم).
 * وكل بنود المعاينة الحالية تصبح اختيارية (يمكن لمسؤول الخدمة إعادة أي بند إلزامياً من «بنود المعاينة»).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sv_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('sv_requests', 'applicant_capacity')) {
                $table->string('applicant_capacity', 16)->default('owner')->after('phone');
            }
            if (! Schema::hasColumn('sv_requests', 'construction_type')) {
                $table->string('construction_type', 16)->default('new')->after('service_type_id')->index();
            }
            if (! Schema::hasColumn('sv_requests', 'existing_building_height')) {
                $table->decimal('existing_building_height', 6, 2)->nullable()->after('inspection_notes');
            }
            if (! Schema::hasColumn('sv_requests', 'pickup_code')) {
                $table->string('pickup_code', 16)->nullable()->unique()->after('completed_at');
            }
            if (! Schema::hasColumn('sv_requests', 'inspection_updated_at')) {
                $table->timestamp('inspection_updated_at')->nullable()->after('existing_building_height');
            }
        });

        DB::table('sv_inspection_fields')->update(['is_required' => false]);
    }

    public function down(): void
    {
        Schema::table('sv_requests', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['applicant_capacity', 'construction_type', 'existing_building_height', 'inspection_updated_at', 'pickup_code'], fn ($c) => Schema::hasColumn('sv_requests', $c))));
        });
    }
};
