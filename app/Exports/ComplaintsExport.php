<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ComplaintsExport extends RTLExport implements FromCollection, WithHeadings, WithMapping
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
            'A' => 30, 'B' => 18, 'C' => 30, 'D' => 20, 'E' => 35,
            'F' => 60, 'G' => 15, 'H' => 60, 'I' => 20, 'J' => 20,
        ];
    }

    public function headings(): array
    {
        return [
            'الاسم',
            'رقم الهاتف',
            'البريد الإلكتروني',
            'الرقم القومي',
            'الموضوع',
            'نص الشكوى',
            'الحالة',
            'الرد الإداري',
            'تاريخ الرد',
            'تاريخ الاستلام',
        ];
    }

    public function map($record): array
    {
        $status = match ($record->status) {
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
            default => $record->status,
        };

        return [
            $record->name,
            $record->phone,
            $record->email,
            $record->national_id,
            $record->subject,
            $record->message,
            $status,
            strip_tags((string) $record->admin_reply),
            $record->replied_at ? $record->replied_at->format('Y-m-d H:i') : '',
            $record->created_at ? $record->created_at->format('Y-m-d H:i') : '',
        ];
    }
}
