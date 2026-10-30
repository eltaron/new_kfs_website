<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityGuideCategoryResource\Pages;
use Modules\Tourism\Models\CityGuideCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CityGuideCategoryResource extends Resource
{
    protected static ?string $model = CityGuideCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'دليل المدينة';
    protected static ?string $modelLabel = 'فئة دليل';
    protected static ?string $pluralModelLabel = 'فئات الدليل';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الفئة')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('icon_class')
                    ->label('اسم أيقونة Font Awesome')
                    ->live(debounce: 500) // Update preview after 500ms
                    ->required()
                    ->helperText('مثال: bed, hospital, tree. سيتم استخدام مجموعة solid (fas) بشكل افتراضي.'),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icon_class')
                    ->label('الأيقونة')
                    ->formatStateUsing(function ($state) {
                        if (!str_contains($state, 'fa-')) {
                            $state = "fa-solid fa-$state";
                        }
                        return "<i class=\"$state\" style='font-size: 20px'></i>" . $state;
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('locations_count')->counts('locations')->label('عدد المواقع')->sortable(),

                Tables\Columns\TextColumn::make('updated_at')->label('آخر تحديث')->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCityGuideCategories::route('/'),
            'create' => Pages\CreateCityGuideCategory::route('/create'),
            'edit' => Pages\EditCityGuideCategory::route('/{record}/edit'),
        ];
    }

    // ===============================================
    // ===== GLOBAL SEARCH (For consistency) =====
    // ===============================================

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }
}
