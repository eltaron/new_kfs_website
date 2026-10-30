<?php

namespace Modules\SiteValidity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvRequestFilters;

/**
 * التقرير المطبوع لـ«خريطة الطلبات»: نفس فلاتر الصفحة (من رابط زر «طباعة التقرير»)،
 * خريطة المراكز + ملخص الحالات + جدول المراكز + قائمة الطلبات. لمسؤول الخدمة فقط.
 */
class ReportController extends Controller
{
    /** أقصى عدد صفوف في القائمة التفصيلية للتقرير المطبوع. */
    private const MAX_ROWS = 1000;

    public function map(Request $request): View
    {
        abort_unless(SvAccess::isAdmin($request->user()), 403);

        $filters = SvRequestFilters::normalize($request->query());
        $query = SvRequestFilters::query($filters, $request->user());
        $centres = SvRequestFilters::byCentre($query);
        $total = $centres->sum('total');

        $rows = (clone $query)->with('serviceType')->latest('id')->limit(self::MAX_ROWS)->get();

        return view('sitevalidity::reports.map', [
            'points' => $rows->filter->hasLocation()->map(fn ($r) => [$r->latitude, $r->longitude, $r->status, $r->number])->values(),
            'unlocated' => $rows->reject->hasLocation()->count(),
            'filters' => SvRequestFilters::describe($filters),
            'centres' => $centres,
            'statuses' => SvRequestFilters::byStatus($query),
            'total' => $total,
            'paid' => $centres->sum('paid'),
            'revenue' => $centres->sum('revenue'),
            'rows' => $rows,
            'truncated' => $total > self::MAX_ROWS,
            'maxRows' => self::MAX_ROWS,
            'statusHex' => SvRequestFilters::STATUS_HEX,
            'statusShort' => SvRequestFilters::STATUS_SHORT,
            'centreIds' => Centre::query()->pluck('id', 'name')->all(),
            'selected' => $filters['center_id'],
            'mapConfig' => [
                'boundaries' => asset((string) config('site-validity.map.boundaries')),
                'center' => config('site-validity.map.center'),
                'zoom' => (int) config('site-validity.map.zoom', 10),
                'points' => (array) config('site-validity.map.points', []),
            ],
            'generatedBy' => $request->user()->name,
        ]);
    }
}
