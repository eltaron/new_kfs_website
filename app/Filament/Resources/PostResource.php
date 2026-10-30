<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use Modules\Content\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'المحتوى';
    protected static ?string $modelLabel = 'خبر';
    protected static ?string $pluralModelLabel = 'الأخبار المنشورة';
    protected static ?int $navigationSort = 2;
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('category', function (Builder $query) {
            $query->where('slug', '!=', 'events');
        });
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('المحتوى الأساسي')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان المقال')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpanFull()
                                    ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->label('الرابط (Slug)')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Post::class, 'slug', ignoreRecord: true)
                                    ->disabledOn('create')
                                    ->columnSpanFull()
                                    ->readOnlyOn('edit'),

                                Forms\Components\RichEditor::make('content')
                                    ->label('محتوى المقال')
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Section::make('معرض الصور')
                            ->schema([
                                Forms\Components\Repeater::make('images')
                                    ->relationship()
                                    ->label('صور إضافية للمقال')
                                    ->schema([
                                        Forms\Components\FileUpload::make('path')->label('ملف الصورة')->image()->directory('posts/gallery')->required(),
                                        Forms\Components\TextInput::make('caption')->label('تعليق (اختياري)'),
                                    ])
                                    ->addActionLabel('أضف صورة')
                                    ->collapsible(),
                            ]),
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('الحالة والظهور')
                            ->schema([
                                Forms\Components\Toggle::make('is_published')
                                    ->label('منشور')
                                    ->helperText('سيتم عرض هذا المقال في الموقع.')
                                    ->default(true),

                                Forms\Components\DatePicker::make('published_at')
                                    ->label('تاريخ النشر')
                                    ->default(now()),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('مقالة مميزة')
                                    ->helperText('ستظهر هذه المقالة بشكل بارز في بعض أقسام الموقع.'),

                                Forms\Components\Toggle::make('allow_comments')
                                    ->label('السماح بالتعليقات')
                                    ->default(true),
                            ]),

                        Forms\Components\Section::make('التصنيف')
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->label('الفئة')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('الصورة المصغرة')
                            ->schema([
                                Forms\Components\FileUpload::make('thumbnail')
                                    ->label('صورة رئيسية')
                                    ->image()
                                    ->directory('posts/thumbnails')
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('بيانات التفاعل (للعرض فقط)')
                            ->schema([
                                Forms\Components\TextInput::make('likes_count')
                                    ->label('عدد الإعجابات')
                                    ->readOnly()
                                    ->default(0),
                                Forms\Components\TextInput::make('shares_count')
                                    ->label('عدد المشاركات')
                                    ->readOnly()
                                    ->default(0),
                            ])->columns(2),
                    ])->columnSpan(1),

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')->label('صورة'),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('الفئة')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('likes_count')
                    ->label('إعجابات')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'name')->label('الفئة'),
                Tables\Filters\TernaryFilter::make('is_published')->label('حالة النشر'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('مقالات مميزة'),
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
            ->defaultSort('published_at', 'desc');
    }
    public static function getNavigationBadge(): ?string
    {
        // Update badge count to reflect the filtered query
        return static::getModel()::whereHas('category', function (Builder $query) {
            $query->where('slug', '!=', 'events');
        })->count();
    }
    // You can create a RelationManager for comments here for a better UX
    public static function getRelations(): array
    {
        return [
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
