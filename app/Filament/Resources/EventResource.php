<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use Modules\Content\Models\Category; //  <-- مهم جدًا
use Modules\Content\Models\Post;       //  <-- مهم جدًا
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    // ***** 1. الربط بموديل Post *****
    protected static ?string $model = Post::class;

    // ***** 2. تغيير التسميات والأيقونة *****
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'المحتوى';
    protected static ?string $modelLabel = 'حدث';
    protected static ?string $pluralModelLabel = 'الفعاليات والمناسبات';
    protected static ?int $navigationSort = 3;

    // ***** 3. الفلترة لعرض فئة "الأحداث" فقط *****
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('category', function (Builder $query) {
            $query->where('slug', 'events');
        });
    }

    public static function form(Form $form): Form
    {
        // ... الكود من الرد السابق سيعمل هنا، ولكن مع تعديل بسيط
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('معلومات الحدث')->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الحدث')->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),

                        Forms\Components\Hidden::make('category_id')
                            ->default(fn() => Category::where('slug', 'events')->first()?->id),

                        Forms\Components\TextInput::make('slug')->required()->unique(Post::class, 'slug', ignoreRecord: true),

                        Forms\Components\RichEditor::make('content')->label('تفاصيل الحدث')->required()->columnSpanFull(),
                    ])->columns(2),
                ])->columnSpan(2),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('الحالة والنشر')->schema([
                        Forms\Components\Toggle::make('is_published')->label('منشور')->default(true),
                        Forms\Components\DatePicker::make('published_at')->label('تاريخ النشر')->default(now()),
                        Forms\Components\Toggle::make('is_featured')->label('حدث مميز؟'),
                        Forms\Components\Toggle::make('allow_comments')->label('السماح بالتعليقات')->default(false),
                    ]),
                    Forms\Components\Section::make('الصورة الرئيسية')->schema([
                        Forms\Components\FileUpload::make('thumbnail')->label('صورة رئيسية')->image()->directory('events')->required(),
                    ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        // نفس كود الجدول من PostResource يمكن استخدامه مع تعديلات بسيطة
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')->label('صورة'),
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable(),
                Tables\Columns\IconColumn::make('is_published')->label('منشور')->boolean(),
                Tables\Columns\TextColumn::make('published_at')->label('تاريخ النشر')->date()->sortable(),
            ])
            ->filters([
                // ...
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
            $query->where('slug',  'events');
        })->count();
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
