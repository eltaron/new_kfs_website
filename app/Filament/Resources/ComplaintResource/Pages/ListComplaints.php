<?php

namespace App\Filament\Resources\ComplaintResource\Pages;

use App\Exports\ComplaintsExport;
use App\Filament\Resources\ComplaintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListComplaints extends ListRecords
{
    protected static string $resource = ComplaintResource::class;

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
                        new ComplaintsExport($records),
                        'الشكاوى_' . now()->format('Y-m-d_H-i') . '.xlsx'
                    );
                }),
        ];
    }
}
