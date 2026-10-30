<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmergencyReportRequest;
use Modules\Content\Models\Center;
use Modules\Tourism\Models\CityVillage;
use Modules\Citizen\Models\EmergencyReport;
use App\Support\SecureUploads;

class EmergencyReportController extends Controller
{
    public function create()
    {
        $centers = Center::where('is_active', true)->orderBy('sort_order')->get();
        $reportTypes = ['حريق', 'حادث مروري', 'تلوث بيئي', 'انهيار مبنى', 'تسريب غاز', 'انقطاع مرافق', 'أخرى'];

        return view('emergency.create', compact('centers', 'reportTypes'));
    }

    public function getVillages($centerId)
    {
        $villages = CityVillage::where('center_id', $centerId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        return response()->json($villages);
    }

    public function store(EmergencyReportRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('attachments')) {
            $paths = [];
            foreach ($request->file('attachments') as $file) {
                $paths[] = SecureUploads::store($file, 'emergency_reports');
            }
            $data['attachments'] = $paths;
        }

        $report = EmergencyReport::create($data);

        return back()->with('success', 'تم استلام بلاغك بنجاح! رقم المتابعة: ' . $report->id);
    }
}
