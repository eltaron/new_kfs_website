<?php

namespace App\Filament\Resources\OfficialResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';
    protected static ?string $title = 'الأدوار والمناصب';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role_name')
                    ->label('المنصب')
                    ->options([
                        'governor' => 'محافظ',
                        'deputy_governor' => 'نائب محافظ',
                        'secretary_general' => 'سكرتير عام',
                        'assistant_secretary_general' => 'سكرتير عام مساعد',
                    ])
                    ->required(),

                Forms\Components\Toggle::make('is_current')
                    ->label('هل هو المنصب الحالي؟')
                    ->onColor('success'),

                Forms\Components\TextInput::make('start_year')->label('سنة البدء')->numeric(),
                Forms\Components\TextInput::make('end_year')->label('سنة الانتهاء')->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('role_name')
            ->columns([
                Tables\Columns\TextColumn::make('role_name')->label('المنصب')
                    ->formatStateUsing(fn(string $state) => __("roles.{$state}")), // For translation

                Tables\Columns\IconColumn::make('is_current')->label('حالي؟')->boolean(),

                Tables\Columns\TextColumn::make('start_year')->label('من'),
                Tables\Columns\TextColumn::make('end_year')->label('إلى'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة دور جديد'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
