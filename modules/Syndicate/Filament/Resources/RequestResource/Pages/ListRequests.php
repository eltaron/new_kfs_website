<?php

namespace Modules\Syndicate\Filament\Resources\RequestResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Syndicate\Filament\Resources\RequestResource;
use Modules\Syndicate\Models\SyndicateRequest;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // البحث السريع برقم المعاملة (الطريقة المعتمدة لدى النقابة).
            Actions\Action::make('lookup')
                ->label('بحث برقم المعاملة')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                ->form([Forms\Components\TextInput::make('transaction_number')->label('رقم المعاملة')
                    ->placeholder('SYN-REQ-2026-000123')->required()->maxLength(40)])
                ->action(function (array $data, Actions\Action $action) {
                    $found = SyndicateRequest::where('transaction_number', strtoupper(trim($data['transaction_number'])))->first();
                    if (! $found) {
                        Notification::make()->danger()->title('لا توجد معاملة بهذا الرقم')->send();
                        $action->halt();
                    }
                    $action->redirect(RequestResource::getUrl('view', ['record' => $found]));
                }),
            Actions\CreateAction::make()->label('تسجيل طلب جديد'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('مفتوحة')->modifyQueryUsing(fn (Builder $query) => $query->open())
                ->badge((string) SyndicateRequest::open()->count())->badgeColor('warning'),
            'supervision' => Tab::make('طلبات إشراف')->modifyQueryUsing(fn (Builder $query) => $query->where('type', SyndicateRequest::TYPE_SUPERVISION)),
            'cancellation' => Tab::make('طلبات إلغاء')->modifyQueryUsing(fn (Builder $query) => $query->where('type', SyndicateRequest::TYPE_CANCELLATION)),
            'all' => Tab::make('الكل'),
        ];
    }
}
