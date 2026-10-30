<?php

namespace App\Filament\Estidama\Resources\BadgeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'المستخدمون الحاصلون على هذه الشارة';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('اسم المستخدم'),
                Tables\Columns\TextColumn::make('email')->label('البريد الإلكتروني'),
                Tables\Columns\TextColumn::make('pivot.awarded_at')->label('تاريخ المنح')->since(),
            ])
            ->headerActions([])
            ->actions([
                // You can add an action to "Revoke" the badge if needed
                Tables\Actions\DetachAction::make()->label('إلغاء منح الشارة'),
            ]);
    }
}
