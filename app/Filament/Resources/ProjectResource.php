<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use Modules\Investment\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    // 1. Update Labels and Navigation for "Investment Projects"
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $modelLabel = 'فرصة استثمارية';
    protected static ?string $pluralModelLabel = 'الفرص الاستثمارية';
    protected static ?string $navigationGroup = 'الاستثمار والمشروعات';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'investment-projects'; // Custom URL for clarity

    // 2. Filter the main query to ONLY show 'استثماري' projects
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('category', 'استثماري');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('معلومات المشروع')->schema([
                    Forms\Components\TextInput::make('name')->label('اسم المشروع')->required()
                        ->live(onBlur: true)->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),

                    // --- THE IMPORTANT CHANGE: Automatically set the category ---
                    Forms\Components\Hidden::make('category')->default('استثماري'),

                    Forms\Components\TextInput::make('slug')->label('الرابط (Slug)')->required()->unique(ignoreRecord: true),

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
                    Forms\Components\Select::make('type')->label('نوع العرض')->options(['image' => 'صورة', 'logo' => 'شعار'])->required()->default('image'),
                    Forms\Components\FileUpload::make('thumbnail')->label('الصورة الرئيسية')->image()->directory('projects/thumbnails')->required(),
                ]),

                Forms\Components\Section::make('التصنيف والموقع')->schema([
                    Forms\Components\Select::make('investment_id')->relationship('investment', 'title')->label('يتبع فرصة استثمارية')->searchable()->preload(),
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
                Tables\Columns\ImageColumn::make('thumbnail')->label('الصورة'),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('investment.title')->label('يتبع')->badge()->placeholder('مستقل'),
                Tables\Columns\IconColumn::make('is_highlighted')->label('مميز')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخر تحديث')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('investment_id')->label('الفرصة الاستثمارية')->relationship('investment', 'title'),
                Tables\Filters\TernaryFilter::make('is_highlighted')->label('المشاريع المميزة'),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
