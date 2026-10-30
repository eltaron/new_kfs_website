<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentResource\Pages;
use App\Filament\Resources\InvestmentResource\RelationManagers;
use Modules\Investment\Models\Investment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentResource extends Resource
{
    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'الاستثمار والمشروعات';
    protected static ?string $modelLabel = ' فرصة استثمارية';
    protected static ?string $pluralModelLabel = ' الفرص الاستثمارية';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        // Tab 1: Basic Information
                        Forms\Components\Tabs\Tab::make('المعلومات الأساسية')
                            ->schema([
                                Forms\Components\TextInput::make('title')->label('عنوان الفرصة الاستثمارية')->required()->columnSpanFull(),
                                Forms\Components\FileUpload::make('thumbnail')->label('الصورة المصغرة الرئيسية')->image()->directory('investments/thumbnails')->required(),
                                Forms\Components\TextInput::make('order')->label('الترتيب')->numeric()->default(0),
                            ])->columns(2),

                        // Tab 2: Detailed Description
                        Forms\Components\Tabs\Tab::make('الوصف التفصيلي')
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->label('محتوى صفحة الفرصة الاستثمارية')
                                    ->helperText('يمكنك إضافة صور، جداول، وقوائم هنا.')
                                    ->columnSpanFull(),
                            ]),

                        // Tab 3: Map & Gallery
                        Forms\Components\Tabs\Tab::make('الخريطة ومعرض الصور')
                            ->schema([
                                Forms\Components\Textarea::make('map_iframe')
                                    ->label('كود تضمين الخريطة (iframe)')
                                    ->helperText('انسخ والصق كود <iframe...> هنا. تأكد من أن العرض (width) 100%.')
                                    ->rows(5)
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('images')
                                    ->relationship()
                                    ->label('صور إضافية للمعرض')
                                    ->schema([
                                        Forms\Components\FileUpload::make('path')->label('ملف الصورة')->image()->directory('investments/gallery')->required(),
                                        Forms\Components\TextInput::make('caption')->label('تعليق (اختياري)'),
                                    ])
                                    ->addActionLabel('إضافة صورة جديدة')
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(), // Make Tabs component take full width
            ]);
    }

    public static function table(Table $table): Table
    {
        // The table code from your example is already excellent. No changes needed.
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')->label('الصورة')->width(100)->height(80),
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('projects_count')->counts('projects')->label('عدد المشاريع المرتبطة'),
                Tables\Columns\TextColumn::make('order')->label('الترتيب')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخر تحديث')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([ /* ... */])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([ /* ... */])
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
            RelationManagers\ProjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestments::route('/'),
            'create' => Pages\CreateInvestment::route('/create'),
            'edit' => Pages\EditInvestment::route('/{record}/edit'),
        ];
    }
}
