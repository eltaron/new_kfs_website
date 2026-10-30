<?php

namespace App\Filament\Resources\SuggestionResource\Pages;

use App\Exports\SuggestionsExport;
use App\Filament\Resources\SuggestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListSuggestions extends ListRecords
{
    protected static string $resource = SuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()->get();
                    return Excel::download(
                        new SuggestionsExport($records),
                        'المقترحات_' . now()->format('Y-m-d_H-i') . '.xlsx'
                    );
                }),
        ];
    }
}
