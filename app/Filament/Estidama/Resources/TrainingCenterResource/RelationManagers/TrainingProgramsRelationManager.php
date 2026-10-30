<?php

namespace App\Filament\Estidama\Resources\TrainingCenterResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrainingProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'trainingPrograms';
    protected static ?string $title = 'البرامج المقدمة في هذا المركز';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    } // View only

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان البرنامج'),
                Tables\Columns\BadgeColumn::make('status')->label('الحالة'),
                Tables\Columns\TextColumn::make('start_date')->label('تاريخ البدء')->date(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn($record) => \App\Filament\Estidama\Resources\TrainingProgramResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
