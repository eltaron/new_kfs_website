<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiakhaResource\Pages;
use Modules\Content\Models\Shiakha;
use Modules\Content\Models\Markaz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShiakhaResource extends Resource
{
    protected static ?string $model = Shiakha::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'الشياخات';
    protected static ?string $modelLabel = 'شياخة';
    protected static ?string $pluralModelLabel = 'الشياخات والمناطق';
    protected static ?string $navigationGroup = 'البيانات المكانية';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الشياخة والموقع الجغرافي')->schema([
                Forms\Components\TextInput::make('name')->label('اسم الشياخة')->required(),

                // اختيار المركز بناءً على الـ g_code المخزن في الداتابيز
                Forms\Components\Select::make('markaz_code')
                    ->label('المركز التابع له')
                    ->options(Markaz::all()->pluck('name', 'g_code'))
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('municipality_name')->label('اسم المدينة / الوحدة المحلية'),
                Forms\Components\TextInput::make('shiakha_g_code')->label('الكود الجغرافي للشياخة')->required(),
                Forms\Components\TextInput::make('st_area')->label('المساحة')->numeric(),
                Forms\Components\TextInput::make('st_length')->label('المحيط')->numeric(),
            ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('الشياخة')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('markaz.name')->label('المركز التابع')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('municipality_name')->label('المدينة')->toggleable(),
            Tables\Columns\TextColumn::make('st_area')->label('المساحة')->numeric(decimalPlaces: 0)->sortable(),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('markaz_code')
                    ->label('فلترة حسب المركز')
                    ->options(Markaz::all()->pluck('name', 'g_code'))
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListShiakhas::route('/'), 'create' => Pages\CreateShiakha::route('/create'), 'edit' => Pages\EditShiakha::route('/{record}/edit')];
    }
}
