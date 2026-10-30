<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * من أدخل الطلب: المواطن نفسه من البوابة، أو موظف «المركز التكنولوجي» من اللوحة نيابةً عنه.
 * (user_id يبقى صاحب الطلب — المواطن إن وُجد له حساب بنفس الرقم القومي.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sv_requests', 'created_by')) {
            Schema::table('sv_requests', function (Blueprint $table) {
                $table->char('created_by', 36)->nullable()->after('user_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sv_requests', 'created_by')) {
            Schema::table('sv_requests', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }
    }
};
