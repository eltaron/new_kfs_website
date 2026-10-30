<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HayahKarimaProjectResource\Pages;
use Modules\Investment\Models\HayahKarimaProject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HayahKarimaProjectResource extends Resource
{
    protected static ?string $model = HayahKarimaProject::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'مشروعات حياة كريمة';
    protected static ?string $modelLabel = 'قطاع حياة كريمة';
    protected static ?string $pluralModelLabel = 'مبادرة حياة كريمة';
    protected static ?string $navigationGroup = 'الاستثمار والمشروعات';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('تفاصيل القطاع')
                ->schema([
                    Forms\Components\TextInput::make('sector_name')
                        ->label('اسم القطاع (مثلاً: الصحة)')
                        ->required()
                        ->lazy(),

                    Forms\Components\TextInput::make('icon')
                        ->label('كود الأيقونة (FontAwesome)')
                        ->placeholder('fas fa-heartbeat'),

                    Forms\Components\TextInput::make('progress')
                        ->label('نسبة الإنجاز (%)')
                        ->numeric()
                        ->default(100)
                        ->required(),

                    Forms\Components\FileUpload::make('image')
                        ->label('صورة توضيحية للقطاع')
                        ->image()
                        ->directory('hayah-karima'),

                    Forms\Components\RichEditor::make('description')
                        ->label('شرح تفصيلي لما تم إنجازه')
                        ->required()
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('sector_name')->label('القطاع')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('progress')
                ->label('نسبة الإنجاز')
                ->badge()
                ->formatStateUsing(fn($state) => $state . '%')
                ->color(fn(string $state): string => $state == '100' ? 'success' : 'warning'),
            Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإضافة')->date(),
        ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHayahKarimaProjects::route('/'),
            'create' => Pages\CreateHayahKarimaProject::route('/create'),
            'edit' => Pages\EditHayahKarimaProject::route('/{record}/edit'),
        ];
    }
}
