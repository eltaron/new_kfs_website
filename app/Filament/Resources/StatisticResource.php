<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatisticResource\Pages;
use Modules\Content\Models\Statistic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class StatisticResource extends Resource
{
    protected static ?string $model = Statistic::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'عن المحافظة';
    protected static ?string $modelLabel = 'إحصائية';
    protected static ?string $pluralModelLabel = 'الإحصائيات الرسمية';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الإحصائية')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('number')
                            ->label('الرقم')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('order')
                            ->label('الترتيب')
                            ->required()
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('icon_class')
                            ->label('اسم أيقونة Font Awesome')
                            ->live(debounce: 300)
                            ->required()
                            ->helperText('مثال: users, map-marked-alt, building.'),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('number')
                    ->label('الرقم')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn(int $state): string => number_format($state)), // Add commas to numbers

                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order', 'asc');
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
            'index' => Pages\ListStatistics::route('/'),
            'create' => Pages\CreateStatistic::route('/create'),
            'edit' => Pages\EditStatistic::route('/{record}/edit'),
        ];
    }
}
