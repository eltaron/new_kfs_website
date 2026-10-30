<?php

namespace Modules\Marketplace\Filament\Vendor\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Filament\Vendor\Resources\PayoutResource\Pages;
use Modules\Marketplace\Models\MarketPayout;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * أرباحي — read-only ledger of transfers made to the vendor.
 */
class PayoutResource extends Resource
{
    protected static ?string $model = MarketPayout::class;

    protected static ?string $slug = 'payouts';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'تحويل';

    protected static ?string $pluralModelLabel = 'الأرباح والتحويلات';

    protected static ?string $navigationLabel = 'الأرباح والتحويلات';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', MarketplaceAccess::currentVendorId())
            ->withCount('items');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('رقم التحويل')->prefix('#'),
                Tables\Columns\TextColumn::make('amount')->label('المبلغ')->money('EGP')->weight('bold'),
                Tables\Columns\TextColumn::make('items_count')->label('عدد المنتجات المسددة'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => MarketPayout::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        MarketPayout::STATUS_PAID => 'success',
                        MarketPayout::STATUS_PENDING => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('reference')->label('مرجع التحويل')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->date('Y-m-d'),
                Tables\Columns\TextColumn::make('paid_at')->label('تاريخ التحويل')->date('Y-m-d')->placeholder('—'),
            ])
            ->emptyStateHeading('لا توجد تحويلات بعد')
            ->emptyStateDescription('تُحوَّل أرباح المنتجات المدفوعة إلكترونياً بعد تسليمها للمشتري.');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
        ];
    }
}
