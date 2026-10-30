<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Resources\EstidamaEventResource\Pages;
use Modules\Estidama\Models\EstidamaEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EstidamaEventResource extends Resource
{
    use \App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;

    protected static ?string $model = EstidamaEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'المحتوى العام';
    protected static ?string $modelLabel = 'حدث استدامة';
    protected static ?string $pluralModelLabel = 'أحداث مركز استدامة';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الحدث')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image')
                            ->label('صورة الحدث')
                            ->image()
                            ->directory('estidama/events')->disk('public')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(4096)
                            ->required()
                            ->imageEditor()
                            ->helperText('يفضل أن تكون الصور بأبعاد متقاربة.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('الصورة')
                    ->square()
                    ->width(80),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                // No filters needed for this simple resource yet.
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
            ->defaultSort('created_at', 'desc');
    }

    // ===== NAVIGATION BADGE (COUNT) =====
    public static function getNavigationBadge(): ?string
    {
        // Shows a count of total events in the sidebar
        return static::getModel()::count();
    }

    // ===== GLOBAL SEARCH CONFIGURATION =====
    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        // What title to display in the search results
        return "حدث استدامة: " . $record->title;
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
            'index' => Pages\ListEstidamaEvents::route('/'),
            'create' => Pages\CreateEstidamaEvent::route('/create'),
            'edit' => Pages\EditEstidamaEvent::route('/{record}/edit'),
        ];
    }
}
