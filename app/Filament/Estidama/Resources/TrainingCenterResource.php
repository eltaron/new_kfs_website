<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Resources\TrainingCenterResource\Pages;
use App\Filament\Estidama\Resources\TrainingCenterResource\RelationManagers;
use Modules\Estidama\Models\TrainingCenter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TrainingCenterResource extends Resource
{
    use \App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;

    protected static ?string $model = TrainingCenter::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'إعدادات التدريب';
    protected static ?string $modelLabel = 'مركز تدريب';
    protected static ?string $pluralModelLabel = 'مراكز التدريب';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المركز')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('address')
                            ->label('عنوان المركز (اختياري)')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المركز')
                    ->searchable()
                    ->sortable(),

                // Show count of programs offered by this center
                Tables\Columns\TextColumn::make('training_programs_count')
                    ->counts('trainingPrograms')
                    ->label('عدد البرامج')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    // ===== NAVIGATION BADGE (COUNT) =====
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    // ===== GLOBAL SEARCH CONFIGURATION =====
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'address'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getRelations(): array
    {
        // To show related Training Programs in a table on the edit page
        return [
            RelationManagers\TrainingProgramsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingCenters::route('/'),
            'create' => Pages\CreateTrainingCenter::route('/create'),
            'edit' => Pages\EditTrainingCenter::route('/{record}/edit'),
        ];
    }
}
