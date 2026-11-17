<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class GisUsersExport extends RTLExport implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    /** Columns written as text so Excel keeps all 14 digits / leading zeros. */
    private const TEXT_COLUMNS = ['D', 'E'];

    private int $rowNumber = 0;

    public function __construct(protected Collection $records)
    {
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'م',
            'اسم الموظف',
            'البريد الإلكتروني',
            'الرقم القومي',
            'رقم الهاتف',
            'المسمى الوظيفي',
            'الصفة الوظيفية',
            'المركز',
            'العنوان',
            'حالة الحساب',
            'تاريخ الإضافة',
        ];
    }

    public function map($user): array
    {
        return [
            ++$this->rowNumber,
            $user->name,
            $user->email,
            (string) $user->national_id,
            (string) $user->phone,
            $user->job_title,
            $user->roles->pluck('name')->implode('، '),
            $user->center ?: 'بدون مركز',
            $user->address,
            match ($user->status) {
                'approved', 'active' => 'مقبول (نشط)',
                'pending' => 'قيد المراجعة',
                'rejected' => 'مرفوض',
                default => (string) $user->status,
            },
            $user->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (in_array($cell->getColumn(), self::TEXT_COLUMNS, true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder())->bindValue($cell, $value);
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 6, 'B' => 30, 'C' => 32, 'D' => 18, 'E' => 16, 'F' => 22,
            'G' => 30, 'H' => 18, 'I' => 30, 'J' => 16, 'K' => 18,
        ];
    }
}
