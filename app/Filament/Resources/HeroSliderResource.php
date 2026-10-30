<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroSliderResource\Pages;
use Modules\Content\Models\HeroSlider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HeroSliderResource extends Resource
{
    protected static ?string $model = HeroSlider::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'المحتوى';
    protected static ?string $modelLabel = 'سلايدر رئيسي';
    protected static ?string $pluralModelLabel = 'السلايدر الرئيسي';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المحتوى الرئيسي')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان الرئيسي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعال (ظاهر في الموقع)')
                            ->default(true),
                    ])->columnSpan(2),

                Forms\Components\Section::make('الوسائط (صورة أو فيديو)')
                    ->schema([
                        Forms\Components\FileUpload::make('media_path')
                            ->label('ملف الوسائط')
                            ->helperText('ارفع صورة أو ملف فيديو قصير.')
                            ->directory('hero-slider')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'image/*',
                                'video/mp4',
                                'video/webm',
                                'video/ogg',
                                'video/quicktime',
                            ])
                            ->maxSize(102400)
                            ->rules(
                                fn(callable $get) =>
                                $get('media_type') === 'video'
                                    ? ['file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime']
                                    : ['image']
                            )
                            ->required(),

                        Forms\Components\Select::make('media_type')
                            ->label('نوع الوسائط')
                            ->options([
                                'image' => 'صورة',
                                'video' => 'فيديو',
                            ])
                            ->required()
                            ->default('image'),
                    ])->columnSpan(1),

                Forms\Components\Section::make('الرابط وزر الإجراء')
                    ->schema([
                        Forms\Components\TextInput::make('link_url')
                            ->label('رابط الزر (اختياري)')
                            ->url()
                            ->placeholder('https://example.com/news/post-1'),
                        Forms\Components\TextInput::make('link_text')
                            ->label('نص الزر (اختياري)')
                            ->placeholder('مثال: قراءة المزيد'),
                    ])->columns(2),

                Forms\Components\TextInput::make('order')
                    ->label('الترتيب')
                    ->helperText('يتم عرض الشرائح بترتيب تصاعدي (الأصغر أولاً).')
                    ->required()
                    ->numeric()
                    ->default(0),

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('media_path')
                    ->label('معاينة')
                    ->width(120)
                    ->height(80),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),

                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('فعال')
                    ->falseLabel('غير فعال'),
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
            ->reorderable('order') //  <--  إضافة السحب والإفلات
            ->defaultSort('order'); // ترتيب افتراضي حسب حقل الترتيب
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
            'index' => Pages\ListHeroSliders::route('/'),
            'create' => Pages\CreateHeroSlider::route('/create'),
            'edit' => Pages\EditHeroSlider::route('/{record}/edit'),
        ];
    }
}
