<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يوسّع جدول syndicate_certificates الموجود (لا يعيد إنشاءه) ليدعم:
 * الترقيم، نوع الشهادة (إشراف/إلغاء)، الربط بالمشروع والطلب، بيانات المكتب، الحالة...
 * ثم ينقل الصفوف القديمة إلى مشروعات (data migration) دون فقد أي بيانات.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('syndicate_certificates')) {
            // تثبيت جديد بدون الجدول القديم: أنشئ الهيكل الأساسي أولاً.
            Schema::create('syndicate_certificates', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['execution', 'demolition'])->nullable();
                $table->enum('execution_sub_type', ['creation', 'elevation'])->nullable();
                $table->string('engineer_name')->nullable();
                $table->string('engineer_address')->nullable();
                $table->string('engineer_national_id')->nullable();
                $table->string('engineer_syndicate_number')->nullable();
                $table->string('engineer_department')->nullable();
                $table->string('engineer_registration_number')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('owner_address')->nullable();
                $table->string('owner_national_id')->nullable();
                $table->string('license_number')->nullable();
                $table->date('license_date')->nullable();
                $table->string('license_authority')->nullable();
                $table->text('project_address')->nullable();
                $table->string('city')->nullable();
                $table->string('governorate')->nullable();
                $table->text('licensed_works_description')->nullable();
                $table->decimal('works_value_numbers', 15, 2)->nullable();
                $table->string('works_value_words')->nullable();
                $table->string('certificate_file')->nullable();
                $table->timestamps();
            });
        }

        // 1) الأعمدة الجديدة (كل عمود محمي بفحص وجوده).
        $add = [
            'number' => fn (Blueprint $t) => $t->string('number', 40)->nullable()->unique()->after('id'),
            'kind' => fn (Blueprint $t) => $t->enum('kind', ['supervision', 'cancellation'])->default('supervision')->index()->after('number'),
            'status' => fn (Blueprint $t) => $t->enum('status', ['active', 'cancelled', 'superseded'])->default('active')->index()->after('kind'),
            'project_id' => fn (Blueprint $t) => $t->unsignedBigInteger('project_id')->nullable()->index()->after('status'),
            'request_id' => fn (Blueprint $t) => $t->unsignedBigInteger('request_id')->nullable()->index()->after('project_id'),
            'cancels_certificate_id' => fn (Blueprint $t) => $t->unsignedBigInteger('cancels_certificate_id')->nullable()->index()->after('request_id'),
            'engineer_id' => fn (Blueprint $t) => $t->unsignedBigInteger('engineer_id')->nullable()->index(),
            'engineer_phone' => fn (Blueprint $t) => $t->string('engineer_phone', 30)->nullable(),
            'engineer_email' => fn (Blueprint $t) => $t->string('engineer_email')->nullable(),
            'office_name' => fn (Blueprint $t) => $t->string('office_name')->nullable(),
            'office_registration_number' => fn (Blueprint $t) => $t->string('office_registration_number', 50)->nullable(),
            'issued_at' => fn (Blueprint $t) => $t->timestamp('issued_at')->nullable(),
            'issued_by' => fn (Blueprint $t) => $t->string('issued_by')->nullable()->comment('user:<uuid> | api:<account id>'),
            'issued_by_name' => fn (Blueprint $t) => $t->string('issued_by_name')->nullable(),
            'cancelled_at' => fn (Blueprint $t) => $t->timestamp('cancelled_at')->nullable(),
            'superseded_at' => fn (Blueprint $t) => $t->timestamp('superseded_at')->nullable(),
            'verification_code' => fn (Blueprint $t) => $t->string('verification_code', 16)->nullable(),
            'pdf_path' => fn (Blueprint $t) => $t->string('pdf_path')->nullable(),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
        ];

        foreach ($add as $column => $definition) {
            if (! Schema::hasColumn('syndicate_certificates', $column)) {
                Schema::table('syndicate_certificates', function (Blueprint $table) use ($definition) {
                    $definition($table);
                });
            }
        }

        // 2) الأعمدة القديمة الإلزامية تصبح اختيارية (شهادة الإلغاء لا تحتاج بيانات الترخيص مثلاً).
        Schema::table('syndicate_certificates', function (Blueprint $table) {
            $table->enum('type', ['execution', 'demolition'])->nullable()->comment('نوع التدخل: تنفيذ أو هدم')->change();
            foreach (['engineer_name', 'engineer_address', 'engineer_national_id', 'engineer_syndicate_number',
                'engineer_department', 'engineer_registration_number', 'owner_name', 'owner_address',
                'owner_national_id', 'license_number', 'license_authority', 'city', 'governorate', 'works_value_words'] as $col) {
                $table->string($col)->nullable()->change();
            }
            $table->date('license_date')->nullable()->change();
            $table->text('project_address')->nullable()->change();
            $table->text('licensed_works_description')->nullable()->change();
            $table->decimal('works_value_numbers', 15, 2)->nullable()->change();
        });

        // 3) نقل البيانات القديمة: كل شهادة بلا رقم تأخذ رقماً، وتُربط بمشروع (يُنشأ إن لزم).
        $this->migrateLegacyRows();
    }

    protected function migrateLegacyRows(): void
    {
        $rows = DB::table('syndicate_certificates')->whereNull('project_id')->orderBy('id')->get();

        foreach ($rows as $row) {
            $projectId = null;

            if (Schema::hasTable('syndicate_projects')) {
                $projectId = DB::table('syndicate_projects')
                    ->where('owner_national_id', substr((string) $row->owner_national_id, 0, 14))
                    ->where('license_number', $row->license_number)
                    ->value('id');

                if (! $projectId) {
                    $projectId = DB::table('syndicate_projects')->insertGetId([
                        'code' => 'SYN-PRJ-L-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
                        'owner_name' => $row->owner_name ?: 'غير محدد',
                        'owner_national_id' => substr((string) $row->owner_national_id, 0, 14),
                        'owner_address' => $row->owner_address,
                        'address' => $row->project_address ?: '-',
                        'center' => $row->city ?: 'كفر الشيخ',
                        'license_number' => $row->license_number,
                        'license_date' => $row->license_date,
                        'license_authority' => $row->license_authority,
                        'works_type' => $row->type ?: 'execution',
                        'execution_sub_type' => $row->execution_sub_type,
                        'works_description' => $row->licensed_works_description,
                        'works_value' => $row->works_value_numbers,
                        'work_status' => 'pending',
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('syndicate_certificates')->where('id', $row->id)->update([
                'number' => $row->number ?: 'SYN-SUP-L-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
                'kind' => 'supervision',
                'project_id' => $projectId,
                'issued_at' => $row->issued_at ?? $row->created_at,
                'verification_code' => $row->verification_code ?? strtoupper(substr(bin2hex(random_bytes(8)), 0, 10)),
            ]);
        }

        if (! Schema::hasTable('syndicate_projects')) {
            return;
        }

        // شهادة واحدة نشطة فقط لكل مشروع: الأحدث تبقى نشطة، والأقدم "مستبدلة".
        $projectIds = DB::table('syndicate_certificates')->whereNotNull('project_id')
            ->where('kind', 'supervision')->distinct()->pluck('project_id');

        foreach ($projectIds as $projectId) {
            $active = DB::table('syndicate_certificates')
                ->where('project_id', $projectId)->where('kind', 'supervision')->where('status', 'active')
                ->orderByDesc('issued_at')->orderByDesc('id')->get();

            if ($active->isEmpty()) {
                continue;
            }

            $latest = $active->first();
            $older = $active->skip(1)->pluck('id')->all();
            if ($older) {
                DB::table('syndicate_certificates')->whereIn('id', $older)
                    ->update(['status' => 'superseded', 'superseded_at' => now()]);
            }

            DB::table('syndicate_projects')->where('id', $projectId)->whereNull('active_certificate_id')->update([
                'active_certificate_id' => $latest->id,
                'work_status' => 'active',
                'work_status_changed_at' => $latest->issued_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        // لا نحذف بيانات الشهادات: نُسقط الأعمدة المضافة فقط.
        $columns = ['number', 'kind', 'status', 'project_id', 'request_id', 'cancels_certificate_id', 'engineer_id',
            'engineer_phone', 'engineer_email', 'office_name', 'office_registration_number', 'issued_at', 'issued_by',
            'issued_by_name', 'cancelled_at', 'superseded_at', 'verification_code', 'pdf_path', 'notes'];

        foreach ($columns as $column) {
            if (Schema::hasColumn('syndicate_certificates', $column)) {
                Schema::table('syndicate_certificates', function (Blueprint $table) use ($column) {
                    if ($column === 'number') {
                        $table->dropUnique(['number']);
                    }
                    if (in_array($column, ['kind', 'status', 'project_id', 'request_id', 'cancels_certificate_id', 'engineer_id'], true)) {
                        $table->dropIndex([$column]);
                    }
                    $table->dropColumn($column);
                });
            }
        }
    }
};
