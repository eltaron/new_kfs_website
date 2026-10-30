<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FamousPersonResource\Pages;
use Modules\Content\Models\FamousPeople;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FamousPersonResource extends Resource
{
    protected static ?string $model = FamousPeople::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'عن المحافظة';
    protected static ?string $modelLabel = 'شخصية مشهورة';
    protected static ?string $pluralModelLabel = 'مشاهير المحافظة';
    protected static ?int $navigationSort = 2;
    public static function canAccess(): bool
    {
        // يمكنك هنا وضع أسماء الأدوار التي يحق لها دخول هذه الصفحة تحديداً
        return auth()->user()->hasAnyRole([
           'super_admin', 
            'إدارة اﻻﺣﺼﺎء واﻟﺘﻘﺎرﻳﺮ والنشر اﻹﻟﻜﱰوﻧﻲ'
        ]);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الشخصية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم بالكامل')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('category')
                            ->label('التصنيف/المجال')
                            ->required()
                            ->options([
                                'العلوم والتكنولوجيا' => 'العلوم والتكنولوجيا',
                                'القيادات الأمنية' => 'القيادات الأمنية',
                                'القيادات الدينية' => 'القيادات الدينية',
                                'القيادات السياسية' => 'القيادات السياسية',
                                'القيادات العسكرية' => 'القيادات العسكرية',
                                'القيادات القانونية' => 'القيادات القانونية',
                                'رجال الدين' => 'رجال الدين',
                                'الآداب والعلوم الإنسانية' => 'الآداب والعلوم الإنسانية',
                                'ريادة الأعمال' => 'ريادة الأعمال',
                                'الرياضة واللياقة البدنية' => 'الرياضة واللياقة البدنية',
                                'الصحافة والإعلام' => 'الصحافة والإعلام',
                                'الفنون' => 'الفنون',
                            ])
                            ->native(false),

                        Forms\Components\FileUpload::make('image')
                            ->label('صورة الشخصية')
                            ->image()
                            ->directory('famous_people')
                            ->imageEditor(),

                        Forms\Components\Textarea::make('bio')
                            ->label('نبذة مختصرة/سيرة ذاتية')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0)
                            ->helperText('الرقم الأقل يظهر أولاً'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('الصورة')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->defaultSort('sort_order') // الترتيب الافتراضي بالجدول
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('فلترة حسب المجال')
                    ->options([
                        'العلوم والتكنولوجيا' => 'العلوم والتكنولوجيا',
                        'القيادات' => 'القيادات',
                        'رجال الدين' => 'رجال الدين',
                    ]),
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
            'index' => Pages\ListFamousPeople::route('/'),
            'create' => Pages\CreateFamousPerson::route('/create'),
            'edit' => Pages\EditFamousPerson::route('/{record}/edit'),
        ];
    }
}
