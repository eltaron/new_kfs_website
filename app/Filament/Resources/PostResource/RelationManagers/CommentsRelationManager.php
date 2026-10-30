<?php

namespace App\Filament\Resources\PostResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $title = 'التعليقات على هذا المقال';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    } // View only, no creation from here
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                Tables\Columns\TextColumn::make('author_name')->label('الكاتب')
                    ->formatStateUsing(fn($record) => $record->user?->name ?? $record->author_name),
                Tables\Columns\TextColumn::make('content')->label('التعليق')->limit(50),
                Tables\Columns\IconColumn::make('is_approved')->label('الحالة')->boolean(),
            ])
            ->headerActions([])
            ->actions([
                // Quick edit and delete
                Tables\Actions\EditAction::make()->url(fn($record) => \App\Filament\Resources\CommentResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
