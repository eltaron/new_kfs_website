<?php

namespace App\Filament\Resources\EmergencyReportResource\Pages;

use App\Exports\EmergencyReportsExport;
use App\Filament\Resources\EmergencyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListEmergencyReports extends ListRecords
{
    protected static string $resource = EmergencyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()->get();
                    return Excel::download(
                        new EmergencyReportsExport($records),
                        'بلاغات_الطوارئ_' . now()->format('Y-m-d_H-i') . '.xlsx'
                    );
                }),
        ];
    }
}
