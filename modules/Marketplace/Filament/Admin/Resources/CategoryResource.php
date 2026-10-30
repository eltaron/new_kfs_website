<?php

namespace Modules\Marketplace\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Marketplace\Filament\Admin\Resources\CategoryResource\Pages;
use Modules\Marketplace\Models\MarketCategory;

class CategoryResource extends Resource
{
    protected static ?string $model = MarketCategory::class;

    protected static ?string $slug = 'categories';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'البائعون والمنتجات';

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('اسم القسم')->required()->maxLength(100),
            Forms\Components\TextInput::make('slug')->label('الرابط المختصر')->maxLength(120)
                ->unique(ignoreRecord: true)->helperText('يُنشأ تلقائياً من الاسم إذا تُرك فارغاً.'),
            Forms\Components\Select::make('parent_id')->label('القسم الرئيسي')
                ->options(fn (?MarketCategory $record) => MarketCategory::query()
                    ->whereNull('parent_id')
                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                    ->pluck('name', 'id'))
                ->placeholder('— قسم رئيسي —'),
            Forms\Components\TextInput::make('icon')->label('أيقونة Font Awesome')->placeholder('fa-palette')
                ->maxLength(60)->regex('/^fa-[a-z0-9-]+$/'),
            Forms\Components\Textarea::make('description')->label('الوصف')->maxLength(255)->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->integer()->default(0),
            Forms\Components\Toggle::make('is_active')->label('ظاهر في المتجر')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('القسم')->searchable()->weight('bold')
                    ->description(fn (MarketCategory $r) => $r->parent?->name),
                Tables\Columns\TextColumn::make('slug')->label('الرابط')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('products_count')->label('المنتجات')->counts('products'),
                Tables\Columns\ToggleColumn::make('is_active')->label('ظاهر'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
