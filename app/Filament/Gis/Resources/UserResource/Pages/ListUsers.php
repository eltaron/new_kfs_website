<?php

namespace App\Filament\Gis\Resources\UserResource\Pages;

use App\Exports\GisUsersExport;
use App\Filament\Gis\Resources\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('تصدير Excel (حسب الفلتر الحالي)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Same query the table shows: resource scope + active filters + search + sort.
                    $users = $this->getFilteredSortedTableQuery()->with('roles')->get();

                    if ($users->isEmpty()) {
                        Notification::make()->title('لا يوجد مستخدمون مطابقون للفلتر الحالي')->warning()->send();

                        return null;
                    }

                    return Excel::download(
                        new GisUsersExport($users),
                        'gis-users-' . now()->format('Y-m-d_H-i') . '.xlsx'
                    );
                }),

            Actions\CreateAction::make(),
        ];
    }
}
