<?php

namespace Modules\Marketplace\Filament\Admin\Resources;

use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Filament\Admin\Resources\OrderResource\Pages;
use Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Services\CheckoutService;

/**
 * نظرة عامة على الطلبات — every order with its per-vendor sub-orders.
 */
class OrderResource extends Resource
{
    protected static ?string $model = MarketOrder::class;

    protected static ?string $slug = 'orders';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'الطلبات والمالية';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user')->withCount('items');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('رقم الطلب')->searchable()->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')->label('المشتري')->searchable(),
                Tables\Columns\TextColumn::make('items_count')->label('المنتجات'),
                Tables\Columns\TextColumn::make('total')->label('الإجمالي')->money('EGP')->sortable(),
                Tables\Columns\TextColumn::make('commission_total')->label('العمولة')->money('EGP')->toggleable(),
                Tables\Columns\TextColumn::make('payment_method')->label('الدفع')
                    ->formatStateUsing(fn (string $state) => MarketOrder::METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => MarketOrder::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => self::statusColor($state)),
                Tables\Columns\IconColumn::make('needs_review')->label('يحتاج مراجعة')->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')->trueColor('danger')->falseIcon('heroicon-o-minus')->falseColor('gray')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(MarketOrder::STATUSES),
                Tables\Filters\SelectFilter::make('payment_method')->label('طريقة الدفع')->options(MarketOrder::METHODS),
                Tables\Filters\Filter::make('needs_review')->label('تحتاج مراجعة')->query(fn (Builder $query) => $query->where('needs_review', true)),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('من'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    /** Admin actions on a single order (view page). */
    public static function adminActions(): array
    {
        return [
            \Filament\Actions\Action::make('resolve')
                ->label('تمت المعالجة')->icon('heroicon-o-check-circle')->color('success')
                ->requiresConfirmation()
                ->modalDescription('استخدمها بعد معالجة المشكلة (مثل استرداد مبلغ منتج ملغي أو نقص المخزون).')
                ->visible(fn (MarketOrder $record) => $record->needs_review && Gate::allows('update', $record))
                ->action(function (MarketOrder $record) {
                    $record->forceFill(['needs_review' => false])->save();
                    Notification::make()->success()->title('تم إغلاق المراجعة.')->send();
                }),
            \Filament\Actions\Action::make('complete')
                ->label('إغلاق الطلب كمكتمل')->icon('heroicon-o-flag')->color('primary')
                ->requiresConfirmation()
                ->visible(fn (MarketOrder $record) => $record->status === MarketOrder::STATUS_DELIVERED && Gate::allows('update', $record))
                ->action(function (MarketOrder $record) {
                    $record->forceFill(['status' => MarketOrder::STATUS_COMPLETED])->save();
                    Notification::make()->success()->title('تم إغلاق الطلب.')->send();
                }),
            \Filament\Actions\Action::make('cancel')
                ->label('إلغاء الطلب غير المدفوع')->icon('heroicon-o-x-circle')->color('danger')
                ->requiresConfirmation()
                ->visible(fn (MarketOrder $record) => ! $record->isPaid()
                    && in_array($record->status, [MarketOrder::STATUS_PENDING_PAYMENT, MarketOrder::STATUS_PAYMENT_FAILED], true)
                    && Gate::allows('update', $record))
                ->action(function (MarketOrder $record) {
                    app(CheckoutService::class)->cancelUnpaid($record);
                    Notification::make()->success()->title('تم إلغاء الطلب وتحرير المخزون المحجوز.')->send();
                }),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('الطلب')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('number')->label('رقم الطلب'),
                    Infolists\Components\TextEntry::make('status')->label('الحالة')->badge()
                        ->formatStateUsing(fn (string $state) => MarketOrder::STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => self::statusColor($state)),
                    Infolists\Components\TextEntry::make('payment_method')->label('طريقة الدفع')
                        ->formatStateUsing(fn (string $state) => MarketOrder::METHODS[$state] ?? $state),
                    Infolists\Components\TextEntry::make('paid_at')->label('تاريخ الدفع')->dateTime('Y-m-d H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('subtotal')->label('المنتجات')->money('EGP'),
                    Infolists\Components\TextEntry::make('shipping_total')->label('الشحن')->money('EGP'),
                    Infolists\Components\TextEntry::make('commission_total')->label('عمولة المتجر')->money('EGP'),
                    Infolists\Components\TextEntry::make('total')->label('الإجمالي')->money('EGP')->weight('bold'),
                    Infolists\Components\IconEntry::make('needs_review')->label('يحتاج مراجعة')->boolean(),
                ]),
            Infolists\Components\Section::make('المشتري والشحن')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label('حساب المشتري'),
                    Infolists\Components\TextEntry::make('shipping_name')->label('المستلم'),
                    Infolists\Components\TextEntry::make('shipping_phone')->label('الهاتف'),
                    Infolists\Components\TextEntry::make('shipping_center')->label('المركز'),
                    Infolists\Components\TextEntry::make('shipping_address')->label('العنوان')->columnSpan(2),
                    Infolists\Components\TextEntry::make('notes')->label('ملاحظات')->placeholder('—')->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('المنتجات حسب البائع')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('items')->label('')
                        ->columns(6)
                        ->schema([
                            Infolists\Components\TextEntry::make('vendor.store_name')->label('البائع'),
                            Infolists\Components\TextEntry::make('product_name')->label('المنتج')
                                ->helperText(fn (MarketOrderItem $record) => $record->variant !== '' ? $record->variant : null),
                            Infolists\Components\TextEntry::make('quantity')->label('الكمية'),
                            Infolists\Components\TextEntry::make('line_total')->label('الإجمالي')->money('EGP'),
                            Infolists\Components\TextEntry::make('vendor_earning')->label('مستحق البائع')->money('EGP'),
                            Infolists\Components\TextEntry::make('fulfillment_status')->label('التجهيز')->badge()
                                ->formatStateUsing(fn (string $state) => MarketOrderItem::FULFILLMENT_STATUSES[$state] ?? $state)
                                ->color(fn (string $state) => OrderItemResource::fulfillmentColor($state)),
                        ]),
                ]),
        ]);
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            MarketOrder::STATUS_PAID, MarketOrder::STATUS_DELIVERED, MarketOrder::STATUS_COMPLETED => 'success',
            MarketOrder::STATUS_PENDING_PAYMENT, MarketOrder::STATUS_PROCESSING => 'warning',
            MarketOrder::STATUS_SHIPPED => 'info',
            default => 'danger',
        };
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketOrder::query()->where('needs_review', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
