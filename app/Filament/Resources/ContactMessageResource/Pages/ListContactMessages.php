<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()->get();
                    return response()->streamDownload(function () use ($records) {
                        $out = fopen('php://output', 'w');
                        fwrite($out, "\xEF\xBB\xBF");
                        fputcsv($out, ['الاسم', 'رقم الهاتف', 'البريد الإلكتروني', 'الموضوع', 'نص الرسالة', 'تمت القراءة', 'الرد', 'تاريخ الإرسال']);
                        foreach ($records as $r) {
                            fputcsv($out, [$r->name, $r->phone, $r->email, $r->subject, $r->message, $r->is_read ? 'نعم' : 'لا', strip_tags($r->admin_reply ?? ''), $r->created_at]);
                        }
                        fclose($out);
                    }, 'رسائل_الدعم_الفني.xls');
                }),
        ];
    }
}
