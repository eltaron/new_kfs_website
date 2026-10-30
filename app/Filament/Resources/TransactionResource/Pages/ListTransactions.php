<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Exports\TransactionExporter;
use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make('export')
                ->label('تصدير (Excel)')
                ->exporter(TransactionExporter::class),
        ];
    }
}
