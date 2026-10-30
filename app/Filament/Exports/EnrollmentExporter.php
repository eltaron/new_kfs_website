<?php

namespace App\Filament\Exports;

use Modules\Estidama\Models\Enrollment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EnrollmentExporter extends Exporter
{
    protected static ?string $model = Enrollment::class;

    public static function getColumns(): array
    {
        // Define the columns to be included in the Excel file
        return [
            ExportColumn::make('id')->label('المعرف'),
            ExportColumn::make('user.name')->label('اسم المتدرب'),
            ExportColumn::make('user.national_id')->label('الرقم القومي'),
            ExportColumn::make('user.email')->label('البريد الإلكتروني'),
            ExportColumn::make('user.phone')->label('رقم الهاتف'),
            ExportColumn::make('trainingProgram.title')->label('البرنامج التدريبي'),
            ExportColumn::make('status')->label('الحالة'),
            ExportColumn::make('enrolled_at')->label('تاريخ التسجيل'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'اكتمل تصدير بيانات التسجيلات. تم تصدير ' . number_format($export->successful_rows) . ' صف.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل تصدير ' . number_format($failedRowsCount) . ' صف.';
        }

        return $body;
    }
}
