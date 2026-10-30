<?php

namespace Modules\Marketplace\Filament\Vendor\Resources;

use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource\Pages;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Services\FulfillmentService;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * الطلبات الواردة — the vendor's own lines of confirmed orders (their "sub-order").
 * Lines of other vendors in the same order are never visible here.
 */
class OrderItemResource extends Resource
{
    protected static ?string $model = MarketOrderItem::class;

    protected static ?string $slug = 'orders';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات الواردة';

    protected static ?string $navigationLabel = 'الطلبات الواردة';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', MarketplaceAccess::currentVendorId())
            // Only confirmed orders (paid online or cash-on-delivery); unpaid checkouts are not the vendor's concern.
            ->whereHas('order', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereIn('status', MarketOrder::FULFILLABLE)
                ->orWhereNotNull('paid_at')))
            ->with('order');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order.number')->label('رقم الطلب')->searchable()->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('product_name')->label('المنتج')->searchable()->wrap()
                    ->description(fn (MarketOrderItem $r) => $r->variant !== '' ? $r->variant : null),
                Tables\Columns\TextColumn::make('quantity')->label('الكمية'),
                Tables\Columns\TextColumn::make('line_total')->label('الإجمالي')->money('EGP'),
                Tables\Columns\TextColumn::make('vendor_earning')->label('صافي ربحك')->money('EGP')->color('success'),
                Tables\Columns\TextColumn::make('order.payment_method')->label('الدفع')
                    ->formatStateUsing(fn (string $state) => MarketOrder::METHODS[$state] ?? $state)->toggleable(),
                Tables\Columns\TextColumn::make('order.shipping_center')->label('المركز')->toggleable(),
                Tables\Columns\TextColumn::make('fulfillment_status')->label('حالة التجهيز')->badge()
                    ->formatStateUsing(fn (string $state) => MarketOrderItem::FULFILLMENT_STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => self::fulfillmentColor($state)),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الطلب')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fulfillment_status')->label('حالة التجهيز')
                    ->options(MarketOrderItem::FULFILLMENT_STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('التفاصيل'),
                self::updateStatusAction(Tables\Actions\Action::make('update_status')),
            ])
            ->emptyStateHeading('لا توجد طلبات بعد')
            ->emptyStateDescription('ستظهر هنا الطلبات المؤكدة على منتجاتك.');
    }

    /**
     * Shared "update fulfilment status" action for the table row and the view page.
     *
     * @template T of \Filament\Actions\Action|\Filament\Tables\Actions\Action
     *
     * @param  T  $action
     * @return T
     */
    public static function updateStatusAction($action)
    {
        return $action
            ->label('تحديث الحالة')
            ->icon('heroicon-o-truck')
            ->color('primary')
            ->visible(fn (MarketOrderItem $record) => FulfillmentService::nextOptions($record) !== []
                && $record->order?->isFulfillable()
                && Gate::allows('update', $record))
            ->form(fn (MarketOrderItem $record) => [
                Forms\Components\Radio::make('status')
                    ->label('الحالة الجديدة')
                    ->options(FulfillmentService::nextOptions($record))
                    ->required(),
                Forms\Components\TextInput::make('note')
                    ->label('ملاحظة للمشتري (اختياري)')
                    ->placeholder('مثال: تم الشحن مع شركة ... رقم الشحنة ...')
                    ->maxLength(255),
            ])
            ->action(function (MarketOrderItem $record, array $data) {
                abort_unless(Gate::allows('update', $record), 403);
                app(FulfillmentService::class)->update($record, (string) $data['status'], $data['note'] ?? null);
                Notification::make()->success()->title('تم تحديث الحالة وإبلاغ المشتري.')->send();
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('المنتج')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('product_name')->label('المنتج'),
                    Infolists\Components\TextEntry::make('variant')->label('الخيار')->placeholder('—'),
                    Infolists\Components\TextEntry::make('quantity')->label('الكمية'),
                    Infolists\Components\TextEntry::make('unit_price')->label('سعر القطعة')->money('EGP'),
                    Infolists\Components\TextEntry::make('line_total')->label('الإجمالي')->money('EGP'),
                    Infolists\Components\TextEntry::make('commission_amount')->label('عمولة المتجر')->money('EGP'),
                    Infolists\Components\TextEntry::make('vendor_earning')->label('صافي ربحك')->money('EGP'),
                    Infolists\Components\TextEntry::make('fulfillment_status')->label('حالة التجهيز')->badge()
                        ->formatStateUsing(fn (string $state) => MarketOrderItem::FULFILLMENT_STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => self::fulfillmentColor($state)),
                    Infolists\Components\TextEntry::make('tracking_note')->label('ملاحظة الشحن')->placeholder('—'),
                ]),
            Infolists\Components\Section::make('بيانات الشحن')
                ->description('استخدم هذه البيانات لتوصيل الطلب فقط.')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('order.number')->label('رقم الطلب'),
                    Infolists\Components\TextEntry::make('order.payment_method')->label('طريقة الدفع')
                        ->formatStateUsing(fn (string $state) => MarketOrder::METHODS[$state] ?? $state),
                    Infolists\Components\TextEntry::make('order.shipping_name')->label('اسم المستلم'),
                    Infolists\Components\TextEntry::make('order.shipping_phone')->label('رقم الهاتف'),
                    Infolists\Components\TextEntry::make('order.shipping_center')->label('المركز'),
                    Infolists\Components\TextEntry::make('order.shipping_address')->label('العنوان'),
                    Infolists\Components\TextEntry::make('order.notes')->label('ملاحظات المشتري')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function fulfillmentColor(string $state): string
    {
        return match ($state) {
            MarketOrderItem::FULFILLMENT_DELIVERED => 'success',
            MarketOrderItem::FULFILLMENT_SHIPPED => 'info',
            MarketOrderItem::FULFILLMENT_CANCELLED => 'danger',
            MarketOrderItem::FULFILLMENT_PROCESSING => 'primary',
            default => 'warning',
        };
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('fulfillment_status', MarketOrderItem::FULFILLMENT_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderItems::route('/'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
        ];
    }
}
