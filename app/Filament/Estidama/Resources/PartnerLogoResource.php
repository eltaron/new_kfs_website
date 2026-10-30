<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Resources\PartnerLogoResource\Pages;
use Modules\Estidama\Models\PartnerLogo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PartnerLogoResource extends Resource
{
    use \App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;

    protected static ?string $model = PartnerLogo::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-thumb-up';
    protected static ?string $navigationGroup = 'المحتوى العام';
    protected static ?string $modelLabel = 'شريك استراتيجي';
    protected static ?string $pluralModelLabel = 'الشركاء الاستراتيجيين';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الشريك')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('link')
                            ->label('الموقع الإلكتروني للشريك (اختياري)')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('logo')
                            ->label('شعار الشريك')
                            ->image()
                            ->directory('partners/logos')->disk('public')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])->maxSize(2048)
                            ->required()
                            ->imageEditor()
                            ->helperText('ارفع صورة شعار الشريك.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('الشعار')
                    ->circular() // Display logo in a circle for a neat look
                    ->width(60),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الشريك')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('link')
                    ->label('الموقع الإلكتروني')
                    ->searchable()
                    ->url(fn(PartnerLogo $record): ?string => $record->link) // Make the link clickable
                    ->openUrlInNewTab()
                    ->placeholder('لا يوجد رابط'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // No filters needed for this simple resource.
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
        // Shows a count of total partners
        return static::getModel()::count();
    }

    // ===== GLOBAL SEARCH CONFIGURATION =====
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'link'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "شريك: " . $record->name;
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
            'index' => Pages\ListPartnerLogos::route('/'),
            'create' => Pages\CreatePartnerLogo::route('/create'),
            'edit' => Pages\EditPartnerLogo::route('/{record}/edit'),
        ];
    }
}
