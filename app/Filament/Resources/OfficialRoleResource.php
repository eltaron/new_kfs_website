<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficialRoleResource\Pages;
use Modules\Content\Models\OfficialRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OfficialRoleResource extends Resource
{
    protected static ?string $model = OfficialRole::class;

    // We hide this resource from navigation because it's better managed
    // through the 'OfficialResource' Relation Manager.
    protected static bool $shouldRegisterNavigation = false;

    // Basic labels in case you access it directly
    protected static ?string $modelLabel = 'دور وظيفي';
    protected static ?string $pluralModelLabel = 'الأدوار الوظيفية';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('official_id')
                    ->relationship('official', 'name')
                    ->label('المسؤول')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([ // Quick-create new official
                        Forms\Components\TextInput::make('name')->label('اسم المسؤول الجديد')->required(),
                        // Add more fields if necessary
                    ]),

                Forms\Components\Select::make('role_name')
                    ->label('المنصب / الدور')
                    ->options([
                        'governor' => 'محافظ',
                        'deputy_governor' => 'نائب محافظ',
                        'secretary_general' => 'سكرتير عام',
                        'assistant_secretary_general' => 'سكرتير عام مساعد',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Toggle::make('is_current')
                    ->label('هل هو المنصب الحالي؟')
                    ->helperText('تفعيل هذا الخيار سيجعل هذا الدور يظهر على أنه الحالي في الصفحة المخصصة.')
                    ->onColor('success'),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('start_year')->label('سنة بدء الخدمة')->numeric(),
                        Forms\Components\TextInput::make('end_year')->label('سنة انتهاء الخدمة')->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('official.name')
                    ->label('اسم المسؤول')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role_name')
                    ->label('المنصب')
                    ->badge() // To make it visually distinct
                    ->formatStateUsing(fn(string $state): string => __("roles.{$state}")) // Uses lang files
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('حالي؟')
                    ->boolean(),

                Tables\Columns\TextColumn::make('start_year')->label('من عام')->sortable(),
                Tables\Columns\TextColumn::make('end_year')->label('إلى عام')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_name')
                    ->label('تصفية حسب المنصب')
                    ->options([
                        'governor' => 'محافظ',
                        'deputy_governor' => 'نائب محافظ',
                        'secretary_general' => 'سكرتير عام',
                        'assistant_secretary_general' => 'سكرتير عام مساعد',
                    ]),
                Tables\Filters\TernaryFilter::make('is_current')->label('المناصب الحالية فقط'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfficialRoles::route('/'),
            'create' => Pages\CreateOfficialRole::route('/create'),
            'edit' => Pages\EditOfficialRole::route('/{record}/edit'),
        ];
    }
}
