<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use Modules\Tourism\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'دليل المدينة';
    protected static ?string $modelLabel = 'موقع';
    protected static ?string $pluralModelLabel = 'المواقع الجغرافية';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('city_guide_category_id')
                    ->relationship('cityGuideCategory', 'name')
                    ->label('فئة الموقع')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('اسم الموقع (مثال: مستشفى كفر الشيخ العام)')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Grid::make(2) // Group lat/lng side by side
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('خط العرض (Latitude)')
                            ->required()
                            ->numeric()
                            ->helperText('يمكنك الحصول عليه من خرائط Google.'),
                        Forms\Components\TextInput::make('longitude')
                            ->label('خط الطول (Longitude)')
                            ->required()
                            ->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الموقع')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cityGuideCategory.name')
                    ->label('الفئة')
                    ->badge() // Use a badge for better visual separation
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('latitude')
                    ->label('خط العرض')
                    ->toggleable(isToggledHiddenByDefault: true), // Hide by default as it's not very readable

                Tables\Columns\TextColumn::make('longitude')
                    ->label('خط الطول')
                    ->toggleable(isToggledHiddenByDefault: true),

                // A custom column to show a link to Google Maps
                // Tables\Columns\TextColumn::make('google_maps_link')
                //     ->label('عرض على الخريطة')
                //     ->html()
                //     ->formatStateUsing(function ($record) {
                //         if (empty($record->latitude) || empty($record->longitude)) {
                //             return '';
                //         }
                //         $url = "https://www.google.com/maps?q={$record->latitude},{$record->longitude}";
                //         return new HtmlString("<a href='{$url}' target='_blank' class='text-primary-600 hover:underline'>فتح في خرائط Google</a>");
                //     }),


                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city_guide_category_id')
                    ->relationship('cityGuideCategory', 'name')
                    ->label('تصفية حسب الفئة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
