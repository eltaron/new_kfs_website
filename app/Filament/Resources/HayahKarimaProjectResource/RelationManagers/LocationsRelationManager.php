<?php

namespace App\Filament\Resources\HayahKarimaProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Models\Center;

/** مواقع تنفيذ القطاع — تظهر في صفحة تفاصيل القطاع وعلى الخريطة التفاعلية. */
class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $title = 'مواقع التنفيذ على الخريطة';

    protected static ?string $modelLabel = 'موقع';

    protected static ?string $pluralModelLabel = 'مواقع التنفيذ';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('اسم الموقع')
                ->placeholder('مثال: مدرسة … الابتدائية / الوحدة الصحية بقرية …')
                ->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('center')
                ->label('المركز')
                ->options(fn () => Center::where('is_active', true)->orderBy('sort_order')->pluck('name', 'name'))
                ->searchable(),
            Forms\Components\TextInput::make('village')->label('القرية / المدينة')->maxLength(255),
            Forms\Components\TextInput::make('latitude')
                ->label('خط العرض (Latitude)')
                ->numeric()->minValue(30)->maxValue(32.5)
                ->helperText('من خرائط جوجل: اضغط بالزر الأيمن على الموقع وانسخ الرقم الأول (مثل 31.1107).'),
            Forms\Components\TextInput::make('longitude')
                ->label('خط الطول (Longitude)')
                ->numeric()->minValue(29.5)->maxValue(32)
                ->helperText('الرقم الثاني (مثل 30.9388).'),
            Forms\Components\Select::make('status')
                ->label('الحالة')
                ->options(['تم التنفيذ' => 'تم التنفيذ', 'جارٍ التنفيذ' => 'جارٍ التنفيذ', 'مخطط' => 'مخطط']),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الموقع')->searchable(),
                Tables\Columns\TextColumn::make('center')->label('المركز')->placeholder('—'),
                Tables\Columns\TextColumn::make('village')->label('القرية / المدينة')->placeholder('—'),
                Tables\Columns\IconColumn::make('has_coords')
                    ->label('على الخريطة')
                    ->state(fn ($record) => $record->hasCoordinates())
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()->placeholder('—'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('إضافة موقع')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->emptyStateHeading('لا توجد مواقع بعد')
            ->emptyStateDescription('أضف مواقع التنفيذ بإحداثياتها لتظهر في صفحة القطاع وعلى الخريطة التفاعلية.');
    }
}
