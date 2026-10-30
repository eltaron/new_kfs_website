<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Resources\BadgeResource\Pages;
use App\Filament\Estidama\Resources\BadgeResource\RelationManagers;
use Modules\Estidama\Models\Badge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BadgeResource extends Resource
{
    use \App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;

    protected static ?string $model = Badge::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'إعدادات التدريب';
    protected static ?string $modelLabel = 'شارة / وسام';
    protected static ?string $pluralModelLabel = 'الشارات والأوسمة';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الشارة')
                            ->columnSpanFull()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('image')
                            ->label('صورة الشارة')
                            ->image()
                            ->disk('public')
                            ->directory('badges')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                            ->maxSize(1024)
                            ->imageEditor()
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الشارة (ماذا تعني)')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('قاعدة المنح التلقائي')
                    ->description('تُمنح الشارة تلقائيًا للمتدرب عند تحقق القاعدة (بعد إتمام درس أو برنامج أو امتحان).')
                    ->schema([
                        Forms\Components\Select::make('rule_type')
                            ->label('القاعدة')
                            ->options(Badge::RULES)
                            ->default('manual')
                            ->native(false)
                            ->live(),
                        Forms\Components\TextInput::make('rule_value')
                            ->label(fn (Forms\Get $get) => match ($get('rule_type')) {
                                'high_score' => 'أقل درجة (%)',
                                'programs_count' => 'عدد البرامج',
                                'lessons_count' => 'عدد الدروس',
                                default => 'القيمة',
                            })
                            ->numeric()->minValue(1)->maxValue(10000)
                            ->required(fn (Forms\Get $get) => in_array($get('rule_type'), ['high_score', 'programs_count', 'lessons_count'], true))
                            ->visible(fn (Forms\Get $get) => in_array($get('rule_type'), ['high_score', 'programs_count', 'lessons_count'], true)),
                        Forms\Components\Select::make('training_program_id')
                            ->label('البرنامج')
                            ->relationship('trainingProgram', 'title')
                            ->searchable()->preload()
                            ->required(fn (Forms\Get $get) => $get('rule_type') === 'program_completed')
                            ->visible(fn (Forms\Get $get) => $get('rule_type') === 'program_completed'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('صورة الشارة')
                    ->circular()
                    ->width(60),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50),

                Tables\Columns\TextColumn::make('rule_type')
                    ->label('قاعدة المنح')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Badge::RULES[$state ?? 'manual'] ?? $state)
                    ->placeholder('يدوي'),

                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('ممنوحة لـ (مستخدم)')
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation manager to show which users have this badge
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            'edit' => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}
