<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandmarkResource\Pages;
use Modules\Tourism\Models\Landmark;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LandmarkResource extends Resource
{
    protected static ?string $model = Landmark::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'عن المحافظة';
    protected static ?string $modelLabel = 'معلم سياحي';
    protected static ?string $pluralModelLabel = 'المعالم السياحية';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية للمعلم')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المعلم السياحي')
                            ->required()
                            ->maxLength(255),

                        // ✅ حقل العنوان الجديد
                        Forms\Components\Textarea::make('address')
                            ->label('العنوان التفصيلي')
                            ->helperText('أدخل العنوان الكامل للمعلم لتسهيل الوصول إليه')
                            ->placeholder('مثال: شارع الحرية، وسط البلد، القاهرة')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('location_id')
                            ->relationship('location', 'name')
                            ->label('الموقع الجغرافي (من دليل العاصمة)')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('اسم الموقع الجديد')->required(),
                            ]),

                        Forms\Components\TextInput::make('latitude')
                            ->label('خط العرض (Latitude)')
                            ->numeric()
                            ->placeholder('مثال: 31.1143')
                            ->helperText('يمكنك الحصول عليه من جوجل ماب')
                            ->afterStateUpdated(function (Forms\Set $set, ?float $state, ?float $longitude) {
                                // تحديث رابط الخريطة تلقائياً إذا كانت الإحداثيات متوفرة
                                if ($state && $longitude) {
                                    $iframe = sprintf(
                                        '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3456.789!2d%s!3d%s!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%%3A0x0!2zMzHCsDA2JzUxLjQiTiAzMMKwNTYnMjkuOCJF!5e0!3m2!1sen!2seg!4v1234567890" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
                                        $longitude,
                                        $state
                                    );
                                    $set('iframe', $iframe);
                                }
                            }),

                        Forms\Components\TextInput::make('longitude')
                            ->label('خط الطول (Longitude)')
                            ->numeric()
                            ->placeholder('مثال: 30.9416')
                            ->afterStateUpdated(function (Forms\Set $set, ?float $state, ?float $latitude) {
                                // تحديث رابط الخريطة تلقائياً إذا كانت الإحداثيات متوفرة
                                if ($state && $latitude) {
                                    $iframe = sprintf(
                                        '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3456.789!2d%s!3d%s!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%%3A0x0!2zMzHCsDA2JzUxLjQiTiAzMMKwNTYnMjkuOCJF!5e0!3m2!1sen!2seg!4v1234567890" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
                                        $state,
                                        $latitude
                                    );
                                    $set('iframe', $iframe);
                                }
                            }),

                        Forms\Components\Textarea::make('iframe')
                            ->label('كود تضمين الخريطة أو الجولة الافتراضية (iframe)')
                            ->helperText('إذا كان هناك خريطة مخصصة أو جولة افتراضية، انسخ كود iframe هنا.')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('thumbnail')
                            ->label('الصورة المصغرة الرئيسية')
                            ->image()
                            ->directory('landmarks/thumbnails')
                            ->required(),

                        Forms\Components\TextInput::make('order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(0),

                        Forms\Components\RichEditor::make('details')
                            ->label('تفاصيل ومعلومات عن المعلم')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'h2',
                                'h3',
                                'blockquote',
                                'link',
                                'bulletList',
                                'orderedList',
                                'undo',
                                'redo'
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('معرض صور المعلم')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship()
                            ->label('ألبوم الصور')
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->label('ملف الصورة')
                                    ->image()
                                    ->directory('landmarks/gallery')
                                    ->required(),
                                Forms\Components\TextInput::make('caption')->label('تعليق (اختياري)'),
                            ])
                            ->addActionLabel('أضف صورة للمعرض')
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('صورة'),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('لا يوجد عنوان'),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('الموقع')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order', 'asc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
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
            'index' => Pages\ListLandmarks::route('/'),
            'create' => Pages\CreateLandmark::route('/create'),
            'edit' => Pages\EditLandmark::route('/{record}/edit'),
        ];
    }
}