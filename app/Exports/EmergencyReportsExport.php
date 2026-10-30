<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmergencyReportsExport extends RTLExport implements FromCollection, WithHeadings, WithMapping
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

    protected function columnWidths(): array
    {
        return [
            'A' => 30, 'B' => 20, 'C' => 18, 'D' => 25, 'E' => 18,
            'F' => 25, 'G' => 40, 'H' => 60, 'I' => 15, 'J' => 15,
            'K' => 22, 'L' => 60, 'M' => 20,
        ];
    }

    public function headings(): array
    {
        return [
            'المُبلغ',
            'الرقم القومي',
            'رقم الهاتف',
            'نوع البلاغ',
            'المركز',
            'المدينة / القرية',
            'وصف الموقع',
            'تفاصيل البلاغ',
            'خط العرض (Lat)',
            'خط الطول (Long)',
            'الحالة',
            'الرد الإداري',
            'تاريخ البلاغ',
        ];
    }

    public function map($record): array
    {
        $status = match ($record->status) {
            'new' => 'جديد',
            'dispatched' => 'تم توجيه جهة مختصة',
            'resolved' => 'تم الحل',
            default => $record->status,
        };

        return [
            $record->reporter_name,
            $record->reporter_national_id,
            $record->reporter_phone,
            $record->report_type,
            $record->center,
            $record->area,
            $record->location_description,
            $record->details,
            $record->latitude,
            $record->longitude,
            $status,
            strip_tags((string) $record->admin_reply),
            $record->created_at ? $record->created_at->format('Y-m-d H:i') : '',
        ];
    }
}
