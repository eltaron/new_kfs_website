<?php

namespace Modules\Marketplace\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Filament\Admin\Resources\PayoutResource\Pages;
use Modules\Marketplace\Models\MarketPayout;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Services\PayoutService;

/**
 * سجل التحويلات — bundle each vendor's delivered, e-Finance-paid earnings into
 * a payout, then record the bank/wallet transfer reference.
 */
class PayoutResource extends Resource
{
    protected static ?string $model = MarketPayout::class;

    protected static ?string $slug = 'payouts';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'الطلبات والمالية';

    protected static ?string $modelLabel = 'تحويل';

    protected static ?string $pluralModelLabel = 'تحويلات البائعين';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['vendor', 'creator'])->withCount('items');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->prefix('#'),
                Tables\Columns\TextColumn::make('vendor.store_name')->label('البائع')->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('المبلغ')->money('EGP')->weight('bold')->sortable(),
                Tables\Columns\TextColumn::make('items_count')->label('عدد البنود'),
                Tables\Columns\TextColumn::make('method')->label('الوسيلة')
                    ->formatStateUsing(fn (?string $state) => MarketVendor::PAYOUT_METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => MarketPayout::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        MarketPayout::STATUS_PAID => 'success',
                        MarketPayout::STATUS_PENDING => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('reference')->label('مرجع التحويل')->placeholder('—'),
                Tables\Columns\TextColumn::make('creator.name')->label('أنشأه')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->date('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(MarketPayout::STATUSES),
                Tables\Filters\SelectFilter::make('vendor_id')->label('البائع')
                    ->options(fn () => MarketVendor::query()->orderBy('store_name')->pluck('store_name', 'id'))->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('payout_destination')
                    ->label('بيانات التحويل')->icon('heroicon-o-identification')->color('gray')
                    ->modalSubmitAction(false)->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (MarketPayout $record) => new \Illuminate\Support\HtmlString(
                        '<dl class="grid grid-cols-2 gap-2">'.collect((array) ($record->vendor?->payout_details ?? []))
                            ->map(fn ($v, $k) => '<dt class="font-bold">'.e(['wallet_number' => 'رقم المحفظة', 'bank_name' => 'البنك', 'account_name' => 'اسم الحساب', 'account_number' => 'رقم الحساب'][$k] ?? $k).'</dt><dd>'.e((string) $v).'</dd>')
                            ->implode('').'</dl>'
                    ))
                    ->visible(fn (MarketPayout $record) => $record->status === MarketPayout::STATUS_PENDING),
                Tables\Actions\Action::make('mark_paid')
                    ->label('تم التحويل')->icon('heroicon-o-check')->color('success')
                    ->form([Forms\Components\TextInput::make('reference')->label('رقم مرجع التحويل')->required()->maxLength(100)])
                    ->visible(fn (MarketPayout $record) => $record->status === MarketPayout::STATUS_PENDING && Gate::allows('update', $record))
                    ->action(function (MarketPayout $record, array $data) {
                        app(PayoutService::class)->markPaid($record, $data['reference']);
                        Notification::make()->success()->title('تم تسجيل التحويل.')->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('إلغاء')->icon('heroicon-o-x-mark')->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('ستعود المبالغ إلى رصيد البائع المتاح للتحويل.')
                    ->visible(fn (MarketPayout $record) => $record->status === MarketPayout::STATUS_PENDING && Gate::allows('update', $record))
                    ->action(function (MarketPayout $record) {
                        app(PayoutService::class)->cancel($record);
                        Notification::make()->success()->title('تم إلغاء التحويل.')->send();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false; // created through the "create payout" header action (computed amounts only)
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
        ];
    }
}
