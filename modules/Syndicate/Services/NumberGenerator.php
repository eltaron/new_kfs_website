<?php

namespace Modules\Syndicate\Services;

use Illuminate\Support\Facades\DB;

/**
 * يولد أرقاماً تسلسلية آمنة من التزامن بالشكل PREFIX-YYYY-000123
 * باستخدام جدول syndicate_sequences مع قفل الصف (SELECT ... FOR UPDATE).
 */
class NumberGenerator
{
    public function next(string $key, ?int $year = null): string
    {
        $prefix = config('syndicate.numbering.' . $key, strtoupper($key));
        $pad = (int) config('syndicate.numbering.pad', 6);
        $year ??= (int) now()->format('Y');
        $name = $prefix . '-' . $year;

        $value = DB::transaction(function () use ($name) {
            DB::table('syndicate_sequences')->insertOrIgnore([
                'name' => $name, 'last_value' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $row = DB::table('syndicate_sequences')->where('name', $name)->lockForUpdate()->first();
            $next = ((int) $row->last_value) + 1;

            DB::table('syndicate_sequences')->where('name', $name)->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        });

        return $name . '-' . str_pad((string) $value, $pad, '0', STR_PAD_LEFT);
    }

    public function project(): string
    {
        return $this->next('project');
    }

    public function request(string $type): string
    {
        return $this->next($type === 'cancellation' ? 'cancellation_request' : 'supervision_request');
    }

    public function certificate(string $kind): string
    {
        return $this->next($kind === 'cancellation' ? 'cancellation_certificate' : 'supervision_certificate');
    }
}
