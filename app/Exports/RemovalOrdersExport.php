<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RemovalOrdersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'التسلسل',
            'نوع المخالفة',
            'رقم الترخيص',
            'تاريخ الترخيص',
            'الأعمال المرخصة',
            'المركز',
            'الوحدة المحلية',
            'الشارع',
            'منطقة المخالفة',
            'الحي',
            'اسم المالك',
            'الرقم القومي للمالك',
            'مركز المالك',
            'وحدة المالك',
            'شارع المالك',
            'حي المالك',
            'محافظة المالك',
            'اسم المهندس',
            'الرقم القومي للمهندس',
            'اسم المقاول',
            'الرقم القومي للمقاول',
            'رقم القطعة',
            'أبعاد المخالفة',
            'الحد الشرقي',
            'الحد الغربي',
            'الحد الشمالي',
            'الحد الجنوبي',
            'البعد الشرقي',
            'البعد الشمالي',
            'البعد الجنوبي',
            'البعد الغربي',
            'المساحة',
            'وصف المهندس',
            'تكلفة المخالفة',
            'أعمال المخالفة',
            'رقم قرار الإيقاف',
            'تاريخ قرار الإيقاف',
            'رقم محضر المخالفة',
            'تاريخ المحضر',
            'تاريخ الإعلان',
            'الحالة',
            'خط العرض (Lat)',
            'خط الطول (Long)',
            'تاريخ الإنشاء',
            'المرحلة الحالية',
            'ملاحظات المراجعة',
            'رقم قرار المحافظ',
            'تاريخ قرار المحافظ',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->violation_type === 'new_violation' ? 'بدون ترخيص' : 'مخالفة ترخيص',
            $record->license_number,
            $record->license_date,
            $record->licensed_works,
            $record->center,
            $record->local_unit,
            $record->street,
            $record->violation_area,
            $record->district,
            $record->owner_name,
            $record->owner_national_id,
            $record->owner_center,
            $record->owner_unit,
            $record->owner_street,
            $record->owner_district,
            $record->owner_governorate,
            $record->engineer_name,
            $record->engineer_national_id,
            $record->contractor_name,
            $record->contractor_national_id,
            $record->violation_plot,
            $record->violation_dimensions,
            $record->border_east,
            $record->border_west,
            $record->border_north,
            $record->border_south,
            $record->dimension_east,
            $record->dimension_north,
            $record->dimension_south,
            $record->dimension_west,
            $record->area,
            $record->engineer_description,
            $record->violation_cost,
            strip_tags($record->violation_works), // تنظيف النص من الـ HTML
            $record->stop_order_number,
            $record->stop_order_date,
            $record->violation_report_number,
            $record->report_date,
            $record->announcement_date,
            $record->status,
            $record->latitude,
            $record->longitude,
            $record->created_at ? $record->created_at->format('Y-m-d H:i') : '',
            $record->stage,
            $record->review_notes,
            $record->governor_decision_number, // الحقل الجديد
            $record->governor_decision_date,
        ];
    }
}
