<?php

namespace App\Filament\Resources\ServiceSurveyResource\Pages;

use App\Filament\Resources\ServiceSurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceSurveys extends ListRecords
{
    protected static string $resource = ServiceSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()->get();
                    return response()->streamDownload(function () use ($records) {
                        $out = fopen('php://output', 'w');
                        fwrite($out, "\xEF\xBB\xBF");
                        $headers = ['الاسم', 'رقم الهاتف', 'المركز', 'الفئة العمرية', 'الجنس', 'تاريخ التقييم',
                            'سهولة الوصول', 'وضوح إجراءات طلب الخدمة', 'مدى تلبية الخدمات للاحتياجات',
                            'الإرشاد حول استخدام البوابة', 'تعاون موظفي المركز', 'سهولة ووضوح الخطوات',
                            'سرعة تقديم الخدمة', 'وضوح المدة المتوقعة', 'وضوح تبرير التأخير',
                            'تعامل موظفي المركز', 'اهتمام الموظفين بحل المشكلة', 'سهولة التواصل مع الموظفين',
                            'وضوح وشفافية الرسوم', 'نظافة وتنظيم المركز', 'راحة أماكن الجلوس', 'وسائل ذوي الهمم',
                            'الاقتراحات', 'اسم الموظف المشكو منه', 'سبب الشكوى', 'تمت المراجعة'];
                        fputcsv($out, $headers);
                        foreach ($records as $r) {
                            fputcsv($out, [$r->name, $r->phone, $r->center_name, $r->age_group, $r->gender, $r->created_at,
                                $r->q1_1_accessibility, $r->q1_2_procedure_clarity, $r->q1_3_needs_fulfillment,
                                $r->q1_4_guidance, $r->q1_5_staff_cooperation, $r->q1_6_process_handling,
                                $r->q2_1_service_speed, $r->q2_2_wait_time, $r->q2_3_delay_justification,
                                $r->q3_1_staff_treatment, $r->q3_2_problem_solving, $r->q3_3_communication_ease,
                                $r->q3_4_fees_clarity, $r->q4_1_cleanliness, $r->q4_2_seating_comfort,
                                $r->q4_3_accessibility_tools, $r->suggestions, $r->complaint_employee_name,
                                $r->complaint_reason, $r->is_reviewed ? 'نعم' : 'لا']);
                        }
                        fclose($out);
                    }, 'تقييمات_المراكز.xls');
                }),
        ];
    }
}
