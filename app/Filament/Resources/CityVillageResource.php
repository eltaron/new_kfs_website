<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityVillageResource\Pages;
use App\Filament\Resources\CityVillageResource\RelationManagers;
use Modules\Tourism\Models\CityVillage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CityVillageResource extends Resource
{
    protected static ?string $model = CityVillage::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'المراكز والقرى';
    protected static ?string $modelLabel = 'قرية / مدينة';
    protected static ?string $pluralModelLabel = 'القرى والمدن';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('center_id')->label('المركز')->relationship('center', 'name')->required()->native(false)->searchable(),
                Forms\Components\TextInput::make('name')->label('الاسم')->required(),
                Forms\Components\Select::make('type')->label('النوع')->options(['village' => 'قرية', 'city' => 'مدينة'])->default('village')->required()->native(false),
                Forms\Components\Toggle::make('is_active')->label('مفعل')->default(true),
                Forms\Components\TextInput::make('sort_order')->label('ترتيب')->numeric()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('center.name')->label('المركز')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')
                    ->badge()->color(fn ($state) => $state === 'city' ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => $state === 'city' ? 'مدينة' : 'قرية'),
                Tables\Columns\IconColumn::make('is_active')->label('مفعل')->boolean()->sortable(),
            ])
            ->defaultSort('center_id', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('center_id')->label('المركز')->relationship('center', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCityVillages::route('/'),
        ];
    }
}
