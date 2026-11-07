<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'sort_order')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // ترتيب ظهور الخدمة (الأصغر أولاً) في الصفحة الرئيسية ودليل الخدمات.
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        // Keep today's order (creation order) as the starting order: 10, 20, 30…
        $i = 0;
        foreach (DB::table('services')->orderBy('created_at')->pluck('id') as $id) {
            DB::table('services')->where('id', $id)->update(['sort_order' => (++$i) * 10]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'sort_order')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
