<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NationalProjectResource\Pages;
use Modules\Investment\Models\Project; // Important: Use the same Project model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NationalProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    // Custom Labels and Navigation
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'الاستثمار والمشروعات';
    protected static ?string $modelLabel = 'مشروع قومي';
    protected static ?string $pluralModelLabel = 'المشاريع القومية';
    protected static ?string $slug = 'national-projects';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('category', ['قومي', 'حياة كريمة']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('معلومات المشروع')->schema([
                    Forms\Components\TextInput::make('name')->label('اسم المشروع')->required()
                        ->live(onBlur: true)->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),

                    // Specific category selection for this resource
                    Forms\Components\Select::make('category')->label('نوع المشروع')
                        ->options(['قومي' => 'مشروع قومي', 'حياة كريمة' => 'مشروع حياة كريمة'])
                        ->required()->native(false),

                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->label('الرابط (Slug)'),
                    Forms\Components\RichEditor::make('description')->label('وصف المشروع')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Section::make('معرض الصور')->schema([
                    Forms\Components\Repeater::make('images')->relationship()->label('صور إضافية')
                        ->schema([
                            Forms\Components\FileUpload::make('path')->label('الصورة')->image()->directory('projects/gallery')->required(),
                            Forms\Components\TextInput::make('caption')->label('تعليق'),
                        ])->addActionLabel('أضف صورة'),
                ]),
            ])->columnSpan(2),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('الحالة والصورة')->schema([
                    Forms\Components\Toggle::make('is_highlighted')->label('مشروع مميز؟'),
                    Forms\Components\Select::make('type')->label('نوع العرض')->options(['image' => 'صورة', 'logo' => 'شعار'])->required()->default('image')->native(false),
                    Forms\Components\FileUpload::make('thumbnail')->label('الصورة الرئيسية/الشعار')->image()->directory('projects/thumbnails')->required(),
                ]),
                Forms\Components\Section::make('الموقع (اختياري)')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('latitude')->label('خط العرض')->numeric(),
                        Forms\Components\TextInput::make('longitude')->label('خط الطول')->numeric(),
                    ]),
                    Forms\Components\Textarea::make('iframe')->label('كود iframe للخريطة')->rows(4)->columnSpanFull(),
                ]),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')->label('صورة'),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('category')->label('النوع')
                    ->colors(['primary' => 'قومي', 'success' => 'حياة كريمة']),
                Tables\Columns\IconColumn::make('is_highlighted')->label('مميز')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخر تحديث')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('النوع')
                    ->options(['قومي' => 'مشروع قومي', 'حياة كريمة' => 'حياة كريمة']),
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

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
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
            'index' => Pages\ListNationalProjects::route('/'),
            'create' => Pages\CreateNationalProject::route('/create'),
            'edit' => Pages\EditNationalProject::route('/{record}/edit'),
        ];
    }
}
