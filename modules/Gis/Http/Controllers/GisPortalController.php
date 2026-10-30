<?php

namespace Modules\Gis\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Gis\Models\GisMarkaz;
use Modules\Gis\Models\GisShiakha;
use Modules\Gis\Models\GisVillage;
use Modules\Gis\Models\GisSubService;
use Modules\Gis\Models\GisSubmission;
use Modules\Gis\Models\GisServiceType;
use Modules\Gis\Models\GisStudyReason;
use App\Services\ArcGisService;
use Illuminate\Http\Request;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Services\Payments\Pricing\GisPricing;
use Illuminate\Support\Facades\Log;

class GisPortalController extends Controller
{
    /**
     * 1. عرض الصفحة الرئيسية للبوابة
     */
    public function index()
    {
        $categories = GisServiceType::with('subServices')->orderBy('created_at')->get();
        return view('gis.index', compact('categories'));
    }

    /**
     * 2. صفحة تفاصيل الخدمة
     */
    public function showService($slug, GisPricing $pricing)
    {
        $service = GisSubService::where('slug', $slug)->with('serviceType')->firstOrFail();
        // أقل مبلغ (بدون مدخلات) — نفس حساب الخادم عند التقديم.
        $quote = $pricing->quote($service, []);

        return view('gis.services.show', compact('service', 'quote'));
    }

    /**
     * 3. API: جلب الوحدات المحلية
     */
    public function getLocalUnits($markazId)
    {
        $units = GisShiakha::where('gis_markaz_id', $markazId)->select('id', 'name')->get();
        return response()->json($units);
    }

    /**
     * 4. API: جلب القرى
     */
    public function getVillages($unitId)
    {
        $villages = GisVillage::where('gis_shiakha_id', $unitId)->select('id', 'name', 'is_ezba')->get();
        return response()->json($villages);
    }

    /**
     * 5. عرض صفحة التقديم (Wizard)
     */
    public function createApplication($slug, GisPricing $pricing)
    {
        $service = GisSubService::where('slug', $slug)->with('serviceType')->firstOrFail();
        $markazs = GisMarkaz::all();
        $reasons = GisStudyReason::all();
        // الحقول التي تغذي حاسبة الرسوم (تختلف أسماؤها من خدمة لأخرى).
        $priceFields = $pricing->inputFields($service);

        return view('gis.apply.start', compact('service', 'markazs', 'reasons', 'priceFields'));
    }

    /**
     * 6. معالجة وحفظ الطلب ثم التحويل لبوابة الدفع الموحدة
     */
    public function storeApplication(Request $request, $slug, GisPricing $pricing, PaymentService $payments, ArcGisService $arcGis)
    {
        $service = GisSubService::where('slug', $slug)->with('serviceType')->firstOrFail();

        $documentRule = ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];

        // أ- التحقق من البيانات الأساسية
        $rules = [
            'applicant_type' => 'required|in:owner,agent,other',
            'markaz_id' => 'required|exists:gis_markazs,id',
            'shiakha_id' => 'required|exists:gis_shiakhas,id',
            'village_id' => 'required|exists:gis_villages,id',
            'request_type' => 'required|in:new,restudy,duplicate',
            'owner_id_card' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'proxy_doc' => array_merge(['required_if:applicant_type,agent', 'nullable'], $documentRule),
            'agent_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ];

        // ب- الحقول الديناميكية (فقط في حالة "جديد")
        if ($request->request_type === 'new' && $service->dynamic_fields) {
            foreach ($service->dynamic_fields as $field) {
                if (empty($field['name'])) {
                    continue;
                }
                $key = 'form_data.' . $field['name'];
                $rule = [!empty($field['is_required']) ? 'required' : 'nullable'];
                $rule = match ($field['type'] ?? 'text') {
                    'file' => array_merge($rule, $documentRule),
                    'number' => array_merge($rule, ['numeric', 'min:0', 'max:100000000']),
                    default => array_merge($rule, ['max:2000']),
                };
                $rules[$key] = $rule;
            }
        }

        // المدخلات المؤثرة في السعر: أرقام موجبة فقط (تمنع المبالغ السالبة)
        foreach ($pricing->numericInputs($service) as $name) {
            $key = 'form_data.' . $name;
            $rules[$key] = array_values(array_unique(array_merge(
                array_filter((array) ($rules[$key] ?? ['nullable']), fn($r) => in_array($r, ['required', 'nullable'], true)),
                ['numeric', 'min:0', 'max:100000000']
            )));
        }

        $request->validate($rules);

        // ج- رفع الملفات على قرص خاص (غير متاح للعامة)
        $attachments = [
            'disk' => 'local',
            'id_front' => $request->file('owner_id_card')->store('gis/submissions/ids', 'local'),
        ];

        if ($request->applicant_type === 'agent' && $request->hasFile('proxy_doc')) {
            $attachments['proxy_doc'] = $request->file('proxy_doc')->store('gis/submissions/proxy', 'local');
        }

        // د- الحقول الديناميكية والملفات
        $formData = $request->request_type === 'new' ? (array) $request->input('form_data', []) : [];
        if ($request->request_type === 'new' && $request->hasFile('form_data')) {
            foreach ((array) $request->file('form_data') as $key => $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $formData[$key] = $file->store('gis/submissions/fields', 'local');
                }
            }
        }

        // هـ- حساب التكلفة على الخادم فقط
        $quote = $pricing->quote($service, $formData);

        // و- حفظ المعاملة
        $submission = GisSubmission::create([
            'user_id' => auth()->id(),
            'gis_sub_service_id' => $service->id,
            'request_type' => $request->request_type,
            'status' => 'received',
            'payment_status' => 'pending', // بانتظار الدفع
            'total_amount' => $quote['total'],
            'applicant_info' => [
                'type' => $request->applicant_type,
                'name' => auth()->user()->name,
                'agent_name' => $request->input('agent_name'),
            ],
            'address_info' => [
                'markaz_id' => $request->markaz_id,
                'shiakha_id' => $request->shiakha_id,
                'village_id' => $request->village_id,
                'details' => $request->input('address'),
            ],
            'form_data' => $formData,
            'attachments' => $attachments,
        ]);

        // ز- مزامنة ArcGIS (غير مدفوع بعد) بعد إرسال الرد — لا تعطل التقديم ولا تبطئه
        $arcGisData = [
            'RequestNumber' => 'REQ-' . $submission->id,
            'Name' => auth()->user()->name,
            'NationalID' => auth()->user()->national_id,
            'MobileNumber' => auth()->user()->phone,
            'Address' => $request->input('address'),
            'Markaz_Name' => $request->markaz_id,
            'Servicetype' => $service->serviceType?->name,
            'Subservicetype' => $service->name,
            'Cost' => $quote['total'],
            'CreatedDate' => now()->format('Y-m-d H:i:s'),
            'IsPaid' => 0,
        ];
        dispatch(function () use ($arcGis, $arcGisData, $submission) {
            try {
                $arcGis->syncToArcGis($arcGisData);
            } catch (\Throwable $e) {
                Log::error('ArcGIS sync failed', ['submission_id' => $submission->id, 'error' => $e->getMessage()]);
            }
        })->afterResponse();

        // ح- التحويل لبوابة الدفع الموحدة
        try {
            $initiation = $payments->initiate($submission);
        } catch (PaymentException $e) {
            return redirect()->route('citizen.dashboard.gis')
                ->with('error', 'تم حفظ طلبك، لكن ' . $e->getMessage() . ' يمكنك السداد لاحقاً من لوحة التحكم.');
        }

        return view('services.efinance_redirect', [
            'url' => $initiation->redirect->url,
            'params' => $initiation->redirect->fields,
            'transaction' => $initiation->transaction,
        ]);
    }


    /**
     * 7. تتبع الطلب
     */
    public function track(Request $request)
    {
        $id = $request->input('ticket');
        $submission = GisSubmission::with('subService')->find($id);

        if (!$submission) {
            return back()->with('error', 'عفراً، كود المعاملة غير صحيح.');
        }

        return view('gis.tracking.results', compact('submission'));
    }

    /**
     * 8. صفحة النجاح
     */
    public function success(Request $request, $id)
    {
        $submission = GisSubmission::where('user_id', $request->user()->id)->findOrFail($id);

        if (! $submission->isPaid()) {
            return redirect()->route('citizen.dashboard.gis')
                ->with('info', 'تم حفظ طلبك ولم يتم تأكيد السداد بعد. إذا قمت بالدفع للتو فسيتم التحديث خلال لحظات.');
        }

        return view('gis.apply.success', compact('submission'));
    }
}
