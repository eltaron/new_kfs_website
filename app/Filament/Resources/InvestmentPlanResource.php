<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentPlanResource\Pages;
use Modules\Investment\Models\InvestmentPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class InvestmentPlanResource extends Resource
{
    protected static ?string $model = InvestmentPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'الاستثمار والمشروعات';
    protected static ?string $modelLabel = 'خطة استثمارية';
    protected static ?string $pluralModelLabel = 'الخطط الاستثمارية';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('year_range')
                            ->label('إصدار عام / النطاق الزمني')
                            ->placeholder('مثال: 2024-2025')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('ملف الخطة (يفضل PDF)')
                            ->directory('investment-plans')
                            ->required()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(15240),

                        Forms\Components\FileUpload::make('final_file_path')
                            ->label('ملف ختامي الخطة (يفضل PDF)')
                            ->directory('investment-plans-final')
                            ->required()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year_range')
                    ->label('إصدار عام')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('download')
                    ->label('الملف')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(
                        fn($record) =>
                        $record && $record->file_path
                            ? Storage::url($record->file_path)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->visible(
                        fn($record) =>
                        $record && filled($record->file_path)
                    ),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Tables\Actions\Action::make('download')
                    ->label('تحميل الخطه')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(
                        fn($record) =>
                        $record && $record->file_path
                            ? Storage::url($record->file_path)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->visible(
                        fn($record) =>
                        $record && filled($record->file_path)
                    ),
                Tables\Actions\Action::make('download')
                    ->label('تحميل نهائي الخطة')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(
                        fn($record) =>
                        $record && $record->final_file_path
                            ? Storage::url($record->final_file_path)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->visible(
                        fn($record) =>
                        $record && filled($record->final_file_path)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('year_range', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentPlans::route('/'),
            'create' => Pages\CreateInvestmentPlan::route('/create'),
            'edit' => Pages\EditInvestmentPlan::route('/{record}/edit'),
        ];
    }
}
