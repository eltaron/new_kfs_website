<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VillageResource\Pages;
use Modules\Content\Models\Village;
use Modules\Content\Models\Shiakha;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VillageResource extends Resource
{
    protected static ?string $model = Village::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationLabel = 'القرى والعزب';
    protected static ?string $modelLabel = 'قرية/عزبة';
    protected static ?string $pluralModelLabel = 'القرى والعزب';
    protected static ?string $navigationGroup = 'البيانات المكانية';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل المنطقة الريفية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم القرية / العزبة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: قرية برج مغيزل'),

                        Forms\Components\Select::make('shiakha_code')
                            ->label('الوحدة المحلية (الشياخة) التابع لها')
                            ->options(Shiakha::all()->pluck('name', 'shiakha_g_code'))
                            ->searchable()
                            ->required()
                            ->preload()
                            ->helperText('اختر الوحدة المحلية الأم التي تتبعها هذه القرية جغرافياً'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم القرية/العزبة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shiakha.name')
                    ->label('الوحدة المحلية')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                // لإظهار اسم المركز المرتبط عبر الشياخة
                Tables\Columns\TextColumn::make('shiakha.markaz.name')
                    ->label('المركز الرئيسي')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // فلتر حسب الوحدة المحلية لسهولة الوصول
                Tables\Filters\SelectFilter::make('shiakha_code')
                    ->label('تصفية حسب الوحدة المحلية')
                    ->options(Shiakha::all()->pluck('name', 'shiakha_g_code')),
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
            'index' => Pages\ListVillages::route('/'),
            'create' => Pages\CreateVillage::route('/create'),
            'edit' => Pages\EditVillage::route('/{record}/edit'),
        ];
    }
}
