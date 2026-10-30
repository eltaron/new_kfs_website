<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarkazResource\Pages;
use Modules\Content\Models\Markaz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MarkazResource extends Resource
{
    protected static ?string $model = Markaz::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'مراكز المحافظة';
    protected static ?string $modelLabel = 'مركز';
    protected static ?string $pluralModelLabel = 'المراكز الإدارية';
    protected static ?string $navigationGroup = 'البيانات المكانية';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\TextInput::make('name')->label('اسم المركز')->required(),
                Forms\Components\TextInput::make('g_code')->label('الكود الجغرافي (G-Code)')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('gov_name')->label('المحافظة')->default('كفر الشيخ')->disabled(),
                Forms\Components\TextInput::make('st_area')->label('المساحة (Shape Area)')->numeric(),
                Forms\Components\TextInput::make('st_length')->label('المحيط (Shape Length)')->numeric(),
            ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('المركز')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('g_code')->label('الكود')->badge()->color('gray'),
            Tables\Columns\TextColumn::make('shiakhas_count')->label('عدد الشياخات')->counts('shiakhas'),
            Tables\Columns\TextColumn::make('st_area')->label('المساحة')->numeric(decimalPlaces: 2)->sortable(),
        ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMarkazs::route('/'), 'create' => Pages\CreateMarkaz::route('/create'), 'edit' => Pages\EditMarkaz::route('/{record}/edit')];
    }
}
