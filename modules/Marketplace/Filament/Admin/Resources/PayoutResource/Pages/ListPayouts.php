<?php

namespace Modules\Marketplace\Filament\Admin\Resources\PayoutResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Filament\Admin\Resources\PayoutResource;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketPayout;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Services\PayoutService;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_payout')
                ->label('إنشاء تحويل لبائع')
                ->icon('heroicon-o-plus')
                ->visible(fn () => Gate::allows('create', MarketPayout::class))
                ->form([
                    Forms\Components\Select::make('vendor_id')
                        ->label('البائع')
                        ->options(fn () => $this->vendorsWithBalance())
                        ->searchable()
                        ->required()
                        ->helperText('يظهر فقط البائعون الذين لديهم مستحقات (منتجات مدفوعة إلكترونياً وتم تسليمها).'),
                    Forms\Components\Textarea::make('notes')->label('ملاحظات')->maxLength(500),
                ])
                ->action(function (array $data) {
                    $vendor = MarketVendor::withTrashed()->findOrFail($data['vendor_id']);
                    $payout = app(PayoutService::class)->createFor($vendor, auth()->user(), $data['notes'] ?? null);
                    Notification::make()->success()
                        ->title('تم إنشاء تحويل بمبلغ '.number_format((float) $payout->amount, 2).' ج.م لـ '.$vendor->store_name)
                        ->send();
                }),
        ];
    }

    /** @return array<int, string> */
    private function vendorsWithBalance(): array
    {
        $ids = MarketOrderItem::query()->select('vendor_id')->distinct()->pluck('vendor_id');

        return MarketVendor::withTrashed()->whereIn('id', $ids)->get()
            ->mapWithKeys(function (MarketVendor $v) {
                $available = (float) PayoutService::eligibleItems($v->getKey())->sum('vendor_earning');

                return $available > 0 ? [$v->getKey() => $v->store_name.' — '.number_format($available, 2).' ج.م'] : [];
            })
            ->all();
    }
}
