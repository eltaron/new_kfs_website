<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\CenterGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CenterGuideController extends Controller
{
    /** Integer columns of center_guides (validated as non-negative integers). */
    protected const INTEGER_FIELDS = [
        'projects_count', 'permanent_workers', 'seasonal_workers', 'assumed_workers',
        'total_population', 'males_count', 'females_count', 'youth_count', 'elderly_count',
        'children_count', 'disabled_count', 'water_stations', 'sewage_plants',
        'electricity_transformers', 'gas_served_areas', 'light_poles', 'light_lamps',
        'primary_schools', 'prep_schools', 'secondary_schools', 'technical_schools',
        'total_students', 'male_students', 'female_students', 'classrooms_count', 'class_density',
        'govt_universities', 'ahli_universities', 'private_universities', 'university_density',
        'hospitals_count', 'health_units', 'family_medicine_centers', 'ambulances_count',
        'doctors_count', 'nurses_count', 'youth_centers', 'sports_clubs', 'land_plots_count',
        'licensed_shops', 'unlicensed_shops', 'commercial_shops_licensed', 'commercial_shops_unlicensed',
        'industrial_workshops_licensed', 'industrial_workshops_unlicensed', 'service_shops_licensed',
        'service_shops_unlicensed', 'entertainment_shops_licensed', 'entertainment_shops_unlicensed',
        'tourism_shops_licensed', 'tourism_shops_unlicensed', 'fish_farms_count', 'parks_count',
        'atms_count', 'police_centers', 'police_departments', 'rescue_points',
    ];

    protected const DECIMAL_FIELDS = ['roads_total_km'];

    protected const DATE_FIELDS = ['vital_project_end_date'];

    /** Fields that must never be set from the public form. */
    protected const PROTECTED_FIELDS = ['attachments', 'status', 'admin_notes'];

    /** Upload field => allowed extensions. */
    protected const FILE_FIELDS = [
        'gis_maps' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip',
        'project_photos' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        'tourism_photos' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        'statistical_reports' => 'pdf,doc,docx,xls,xlsx,ppt,pptx',
        'word_excel_files' => 'pdf,doc,docx,xls,xlsx',
    ];

    protected const MAX_FILE_KB = 10240; // 10 MB per file

    protected const MAX_FILES_PER_FIELD = 10;

    public function create()
    {
        return view('center-guides.create');
    }

    public function store(Request $request)
    {
        $textFields = array_values(array_diff(
            (new CenterGuide())->getFillable(),
            self::PROTECTED_FIELDS,
            self::INTEGER_FIELDS,
            self::DECIMAL_FIELDS,
            self::DATE_FIELDS
        ));

        $rules = [];
        foreach ($textFields as $field) {
            $rules[$field] = ['nullable', 'string', 'max:5000'];
        }
        foreach (self::INTEGER_FIELDS as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0', 'max:2000000000'];
        }
        foreach (self::DECIMAL_FIELDS as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0'];
        }
        foreach (self::DATE_FIELDS as $field) {
            $rules[$field] = ['nullable', 'date'];
        }
        $rules['city'] = ['required', 'string', 'max:255'];

        foreach (self::FILE_FIELDS as $field => $extensions) {
            $rules[$field] = ['nullable', 'array', 'max:' . self::MAX_FILES_PER_FIELD];
            $rules[$field . '.*'] = ['file', 'extensions:' . $extensions, 'mimes:' . $extensions, 'max:' . self::MAX_FILE_KB];
        }

        $validated = $request->validate($rules, [
            '*.*.extensions' => 'نوع الملف غير مسموح به.',
            '*.*.mimes' => 'نوع الملف غير مسموح به.',
            '*.*.max' => 'حجم الملف يتجاوز الحد المسموح (10 ميجابايت).',
            'city.required' => 'اسم المركز/المدينة مطلوب.',
        ]);

        // Explicit whitelist: only known model columns, never protected ones.
        $data = collect($validated)
            ->only(array_merge($textFields, self::INTEGER_FIELDS, self::DECIMAL_FIELDS, self::DATE_FIELDS))
            ->all();

        $uploadedFiles = [];
        foreach (array_keys(self::FILE_FIELDS) as $field) {
            foreach ((array) $request->file($field, []) as $file) {
                if ($file && $file->isValid()) {
                    $uploadedFiles[$field][] = $file->store('center-guides/' . $field, 'public');
                }
            }
        }

        $data['attachments'] = $uploadedFiles ?: null;

        $guide = CenterGuide::create($data);

        Log::info('Center guide submitted', ['id' => $guide->id, 'user' => $request->user()?->getKey()]);

        return response()->json(['success' => true]);
    }
}
