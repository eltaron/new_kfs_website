<?php

namespace Modules\Marketplace\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Filament\Admin\Resources\ReviewResource\Pages;
use Modules\Marketplace\Models\MarketReview;

/**
 * التقييمات — only verified buyers can review; admins hide abusive ones.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = MarketReview::class;

    protected static ?string $slug = 'reviews';

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'البائعون والمنتجات';

    protected static ?string $modelLabel = 'تقييم';

    protected static ?string $pluralModelLabel = 'التقييمات';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('المنتج')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('user.name')->label('المشتري'),
                Tables\Columns\TextColumn::make('rating')->label('التقييم')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning'),
                Tables\Columns\TextColumn::make('comment')->label('التعليق')->wrap()->limit(120)->placeholder('—'),
                Tables\Columns\ToggleColumn::make('is_visible')->label('ظاهر'),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->date('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')->label('ظاهر'),
                Tables\Filters\SelectFilter::make('rating')->label('التقييم')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReviews::route('/'),
        ];
    }
}
