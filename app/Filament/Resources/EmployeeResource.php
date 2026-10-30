<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use Modules\Content\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'الموظفين';
    protected static ?string $modelLabel = 'موظف';
    protected static ?string $pluralModelLabel = 'قاعدة بيانات الموظفين';
    protected static ?string $navigationGroup = 'الشؤون الإدارية';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('اسم الموظف رباعي')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('national_id')
                        ->label('الرقم القومي (14 رقم)')
                        ->required()
                        ->length(14)
                        ->numeric()
                        ->unique(ignoreRecord: true)
                        ->helperText('يجب أن يتكون من 14 رقم ويبدأ بـ 2 أو 3'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('national_id')->label('الرقم القومي')->copyable()->searchable(),
            Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإضافة')->dateTime('Y-m-d')->sortable(),
        ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEmployees::route('/'), 'create' => Pages\CreateEmployee::route('/create'), 'edit' => Pages\EditEmployee::route('/{record}/edit')];
    }
}
