<?php

namespace App\Filament\Exports;

use App\Filament\Resources\TransactionResource;
use Modules\Payments\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('رقم المعاملة'),
            ExportColumn::make('sender_request_number')->label('رقم العملية'),
            ExportColumn::make('payment_request_number')->label('رقم المرجع e-finance'),
            ExportColumn::make('authorization_code')->label('كود التفويض'),
            ExportColumn::make('user.name')->label('المستخدم'),
            ExportColumn::make('user.national_id')->label('الرقم القومي'),
            ExportColumn::make('transactionable_type')->label('نوع المعاملة')
                ->formatStateUsing(fn (?string $state) => TransactionResource::TYPE_OPTIONS[$state] ?? $state),
            ExportColumn::make('transactionable_id')->label('معرف الطلب'),
            ExportColumn::make('description')->label('البيان'),
            ExportColumn::make('amount')->label('المبلغ'),
            ExportColumn::make('collection_fees')->label('رسوم التحصيل'),
            ExportColumn::make('status')->label('الحالة')
                ->formatStateUsing(fn (?string $state) => Transaction::STATUSES[$state] ?? $state),
            ExportColumn::make('failure_reason')->label('سبب الفشل'),
            ExportColumn::make('created_at')->label('تاريخ البدء'),
            ExportColumn::make('completed_at')->label('تاريخ الإتمام'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'اكتمل تصدير المعاملات المالية: ' . number_format($export->successful_rows) . ' صف.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل تصدير ' . number_format($failedRowsCount) . ' صف.';
        }

        return $body;
    }
}
