<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use Modules\Investment\Models\Advertisement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'الإعلانات';
    protected static ?string $modelLabel = 'إعلان';
    protected static ?string $pluralModelLabel = 'الدليل الإرشادي للإعلانات';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الإعلان')
                    ->schema([
                        Forms\Components\TextInput::make('street_name')->label('الشارع')->required()->maxLength(255),
                        Forms\Components\Select::make('type')->label('النوع')->required()
                            ->options(Advertisement::typeOptions())->native(false),
                        Forms\Components\TextInput::make('height')->label('الارتفاع (م)')->numeric()->suffix('م'),
                        Forms\Components\TextInput::make('size')->label('المقاس')->placeholder('مثلاً 3×2 م'),
                        Forms\Components\TextInput::make('lat')->label('خط العرض')->required()->numeric()->step(0.0000001),
                        Forms\Components\TextInput::make('lng')->label('خط الطول')->required()->numeric()->step(0.0000001),
                        Forms\Components\Textarea::make('description')->label('وصف')->maxLength(1000),
                        Forms\Components\Select::make('status')->label('الحالة')->options(['active' => 'نشط', 'inactive' => 'غير نشط'])->default('active')->native(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('street_name')->label('الشارع')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge()->color(fn ($state) => match($state) {'لافتة' => 'info', 'باص' => 'danger', 'تليفزيوني' => 'success', 'إلكتروني' => 'warning', default => 'gray'})->searchable(),
                Tables\Columns\TextColumn::make('height')->label('الارتفاع')->suffix(' م')->sortable(),
                Tables\Columns\TextColumn::make('size')->label('المقاس'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()->color(fn ($state) => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 'active' ? 'نشط' : 'غير نشط'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('النوع')->options(Advertisement::typeOptions()),
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(['active' => 'نشط', 'inactive' => 'غير نشط']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}

