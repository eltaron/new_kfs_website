<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أيام المعاينة لكل مركز (يحددها المركز الجيومكاني) + تأكيد المواطن لموعد المعاينة بعد موافقة المهندس.
 * عند رفض الموعد (من المواطن أو المهندس) يختار يوماً من الأيام المتاحة لمركز الطلب.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sv_centre_schedules')) {
            Schema::create('sv_centre_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('center_id')->unique();
                $table->json('weekdays')->nullable();               // 0 = الأحد ... 6 = السبت (Carbon::dayOfWeek)
                $table->time('start_time')->default('10:00:00');      // ساعة بدء المعاينات في اليوم
                $table->unsignedSmallInteger('daily_capacity')->default(10); // أقصى عدد معاينات في اليوم
                $table->string('notes', 500)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('sv_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('sv_requests', 'appointment_confirmation')) {
                $table->string('appointment_confirmation', 16)->nullable()->after('appointment_notes'); // pending | accepted
            }
            if (! Schema::hasColumn('sv_requests', 'appointment_confirmed_at')) {
                $table->timestamp('appointment_confirmed_at')->nullable()->after('appointment_confirmation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sv_requests', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['appointment_confirmation', 'appointment_confirmed_at'], fn ($c) => Schema::hasColumn('sv_requests', $c))));
        });
        Schema::dropIfExists('sv_centre_schedules');
    }
};
