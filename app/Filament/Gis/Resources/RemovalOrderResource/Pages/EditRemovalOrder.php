<?php

namespace App\Filament\Gis\Resources\RemovalOrderResource\Pages;

use App\Filament\Gis\Resources\RemovalOrderResource;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRemovalOrder extends EditRecord
{
    protected static string $resource = RemovalOrderResource::class;

    /**
     * تحديث الحالة والمرحلة تلقائياً عند حفظ التعديلات بواسطة المهندس
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        $record = $this->record; // السجل الحالي قبل الحفظ

        // إذا كان المستخدم هو مهندس التنظيم
        if ($user && $user->hasRole('مهندس التنظيم')) {

            // الحالة الأولى: إذا كان القرار مرجعاً للمهندس من أخصائي النظم
            if ($record->stage === RemovalOrder::STAGE_ENGINEERING_REVIEW) {

                // 1. إعادة المرحلة مباشرة إلى أخصائي النظم (مرحلة انتظار الـ PDF)
                $data['stage'] = RemovalOrder::STAGE_SPATIAL_DIMENSIONED;

                // 2. تحديث الحالة النصية
                $data['status'] = 'تم تعديل الملاحظات - جاهز للاعتماد';

                // 3. مسح ملاحظات المراجعة القديمة لأن المهندس أصلح الخطأ
                $data['review_notes'] = null;
            }

            // الحالة الثانية: إذا كان القرار جديداً (قيد الإنشاء)
            elseif ($record->stage === RemovalOrder::STAGE_CREATED) {

                // 1. نقل المرحلة إلى "انتظار الكشف المساحي"
                $data['stage'] = RemovalOrder::STAGE_SPATIAL_PENDING;

                // 2. تحديث الحالة النصية
                $data['status'] = 'تمت المراجعة الهندسية';
            }

            // في كل الأحوال، يتم تسجيل المهندس المسؤول
            $data['engineering_engineer_id'] = $user->id;
        }

        return $data;
    }

    /**
     * تخصيص رسالة النجاح بناءً على الحالة
     */
    protected function getSavedNotificationTitle(): ?string
    {
        if ($this->record->stage === RemovalOrder::STAGE_ENGINEERING_REVIEW) {
            return 'تم حفظ التعديلات وإعادة القرار إلى أخصائي النظم بنجاح';
        }

        return 'تم اعتماد المراجعة الهندسية وتحويل القرار للكشف المساحي';
    }

    protected function getHeaderActions(): array
    {
        return [
            // يمكنك تفعيل الحذف أو العرض هنا إذا أردت
            // Actions\DeleteAction::make(),
        ];
    }

    /**
     * توجيه المستخدم بعد الحفظ
     * يمكنك إرجاعه للجدول الرئيسي أو صفحة مسار العمل
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
