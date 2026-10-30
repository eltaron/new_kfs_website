<?php

namespace Modules\SiteValidity\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Models\Geo\Village;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;

/**
 * الفلتر المتقدم لطلبات صلاحية الموقع — مشترك بين «خريطة الطلبات» وتقريرها المطبوع
 * (فيطابق التقرير ما يظهر على الشاشة تماماً).
 *
 * المفاتيح: search, statuses[], service_types[], center_id, village_id, paid (paid|unpaid), from, to, overdue, source (portal|tech)
 */
final class SvRequestFilters
{
    /** ألوان الحالات على الخريطة والتقرير (نفس ألوان تبويبات قائمة الطلبات). */
    public const STATUS_HEX = [
        SvStatus::SUBMITTED => '#B7791F',
        SvStatus::APPOINTMENT_SET => '#147C92',
        SvStatus::APPROVED => '#5B5BD6',
        SvStatus::INSPECTED => '#00788A',
        SvStatus::ATTACHMENTS_UPLOADED => '#C2570C',
        SvStatus::COMPLETION_REQUESTED => '#A63A32',
        SvStatus::COMPLETION_SUBMITTED => '#D97706',
        SvStatus::COMPLETED => '#3D7A50',
        SvStatus::REJECTED => '#78756A',
    ];

    /** تسميات مختصرة للحالات (للمخططات والجداول الضيقة). */
    public const STATUS_SHORT = [
        SvStatus::SUBMITTED => 'جديد',
        SvStatus::APPOINTMENT_SET => 'موعد محدد',
        SvStatus::APPROVED => 'معتمد',
        SvStatus::INSPECTED => 'تمت المعاينة',
        SvStatus::ATTACHMENTS_UPLOADED => 'قرار المهندس',
        SvStatus::COMPLETION_REQUESTED => 'استيفاء مطلوب',
        SvStatus::COMPLETION_SUBMITTED => 'تم الاستيفاء',
        SvStatus::COMPLETED => 'منتهي',
        SvStatus::REJECTED => 'مرفوض',
    ];

    public const KEYS = ['search', 'statuses', 'service_types', 'center_id', 'village_id', 'paid', 'from', 'to', 'overdue', 'source'];

    /** تنظيف المدخلات (من Livewire أو من رابط الطباعة). */
    public static function normalize(array $f): array
    {
        $ids = fn ($v) => array_values(array_filter(array_map('intval', (array) $v)));

        return [
            'search' => trim(mb_substr((string) ($f['search'] ?? ''), 0, 100)),
            'statuses' => array_values(array_intersect((array) ($f['statuses'] ?? []), SvStatus::all())),
            'service_types' => $ids($f['service_types'] ?? []),
            'center_id' => (int) ($f['center_id'] ?? 0) ?: null,
            'village_id' => (int) ($f['village_id'] ?? 0) ?: null,
            'paid' => in_array($f['paid'] ?? null, ['paid', 'unpaid'], true) ? $f['paid'] : null,
            'from' => self::date($f['from'] ?? null),
            'to' => self::date($f['to'] ?? null),
            'overdue' => filter_var($f['overdue'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'source' => in_array($f['source'] ?? null, ['portal', 'tech'], true) ? $f['source'] : null,
        ];
    }

    public static function apply(Builder $query, array $f): Builder
    {
        $f = self::normalize($f);

        return $query
            ->when($f['search'] !== '', function (Builder $q) use ($f) {
                $term = '%' . addcslashes($f['search'], '%_\\') . '%';
                $q->where(fn (Builder $w) => $w->where('number', 'like', $term)
                    ->orWhere('applicant_name', 'like', $term)
                    ->orWhere('national_id', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('village_name', 'like', $term));
            })
            ->when($f['statuses'], fn (Builder $q) => $q->whereIn('status', $f['statuses']))
            ->when($f['service_types'], fn (Builder $q) => $q->whereIn('service_type_id', $f['service_types']))
            ->when($f['center_id'], fn (Builder $q) => $q->where('center_id', $f['center_id']))
            ->when($f['village_id'], fn (Builder $q) => $q->where('village_id', $f['village_id']))
            ->when($f['paid'] === 'paid', fn (Builder $q) => $q->whereNotNull('paid_at'))
            ->when($f['paid'] === 'unpaid', fn (Builder $q) => $q->whereNull('paid_at'))
            ->when($f['from'], fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($f['from'])->startOfDay()))
            ->when($f['to'], fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($f['to'])->endOfDay()))
            ->when($f['overdue'], fn (Builder $q) => $q->overdue())
            ->when($f['source'] === 'portal', fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('created_by')->orWhereColumn('created_by', 'user_id')))
            ->when($f['source'] === 'tech', fn (Builder $q) => $q->whereNotNull('created_by')->whereColumn('created_by', '!=', 'user_id'));
    }

    public static function query(array $f, $user = null): Builder
    {
        return self::apply(SiteValidityRequest::query()->visibleTo($user ?? auth()->user()), $f);
    }

    /**
     * أعداد كل مركز حسب الحالة + الإجمالي والرسوم.
     *
     * @return Collection<int, array{id:int, name:string, total:int, paid:int, revenue:float, statuses:array<string,int>}>
     */
    public static function byCentre(Builder $query): Collection
    {
        $rows = (clone $query)->toBase()
            ->selectRaw('center_id, center_name, status, COUNT(*) as c, SUM(CASE WHEN paid_at IS NOT NULL THEN 1 ELSE 0 END) as p, COALESCE(SUM(paid_amount), 0) as rev')
            ->groupBy('center_id', 'center_name', 'status')
            ->get();

        return $rows->groupBy('center_id')->map(function (Collection $g, $id) {
            $statuses = $g->mapWithKeys(fn ($r) => [$r->status => (int) $r->c])->all();

            return [
                'id' => (int) $id,
                'name' => (string) $g->first()->center_name,
                'total' => array_sum($statuses),
                'paid' => (int) $g->sum('p'),
                'revenue' => (float) $g->sum('rev'),
                'statuses' => $statuses,
            ];
        })->sortByDesc('total')->values();
    }

    /** @return array<string,int> */
    public static function byStatus(Builder $query): array
    {
        return (clone $query)->toBase()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->map(fn ($c) => (int) $c)->all();
    }

    /** @return Collection<int, array{name:string, total:int}> */
    public static function byVillage(Builder $query, int $limit = 12): Collection
    {
        return (clone $query)->toBase()
            ->selectRaw('village_name, COUNT(*) as c')
            ->groupBy('village_name')->orderByDesc('c')->limit($limit)->get()
            ->map(fn ($r) => ['name' => (string) $r->village_name, 'total' => (int) $r->c]);
    }

    /** وصف نصي للفلاتر النشطة (رأس التقرير المطبوع). @return list<string> */
    public static function describe(array $f): array
    {
        $f = self::normalize($f);
        $out = [];
        if ($f['search'] !== '') {
            $out[] = 'بحث: «' . $f['search'] . '»';
        }
        if ($f['statuses']) {
            $out[] = 'الحالة: ' . implode('، ', array_map(fn ($s) => self::STATUS_SHORT[$s] ?? $s, $f['statuses']));
        }
        if ($f['service_types']) {
            $out[] = 'الخدمة: ' . ServiceType::query()->whereIn('id', $f['service_types'])->pluck('name')->implode('، ');
        }
        if ($f['center_id']) {
            $out[] = 'المركز: ' . (Centre::query()->whereKey($f['center_id'])->value('name') ?? '—');
        }
        if ($f['village_id']) {
            $out[] = 'القرية: ' . (Village::query()->whereKey($f['village_id'])->value('name') ?? '—');
        }
        if ($f['paid']) {
            $out[] = $f['paid'] === 'paid' ? 'المدفوعة فقط' : 'غير المدفوعة فقط';
        }
        if ($f['from'] || $f['to']) {
            $out[] = 'الفترة: ' . ($f['from'] ?? '…') . ' ← ' . ($f['to'] ?? '…');
        }
        if ($f['overdue']) {
            $out[] = 'المواعيد المتأخرة فقط';
        }
        if ($f['source']) {
            $out[] = $f['source'] === 'tech' ? 'مُدخلة من المركز التكنولوجي' : 'مقدمة من البوابة';
        }

        return $out;
    }

    private static function date(mixed $v): ?string
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }
}
