<?php

namespace Modules\SiteValidity\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\SiteValidity\Filament\Resources\ServiceTypeResource\Pages;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Support\LatinKey;
use Modules\SiteValidity\Support\SvAccess;

/** أنواع الخدمة والرسوم (مسؤول الخدمة فقط). */
class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static ?string $slug = 'service-types';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'إعدادات الخدمة';

    protected static ?string $navigationLabel = 'أنواع الخدمة والرسوم';

    protected static ?string $modelLabel = 'نوع خدمة';

    protected static ?string $pluralModelLabel = 'أنواع الخدمة';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('اسم الخدمة')->required()->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set, ?ServiceType $record) => $record ? null : $set('slug', LatinKey::make($state, '-', 80, ServiceType::class, 'slug', 'service'))),
            Forms\Components\TextInput::make('slug')->label('المعرف (لاتيني)')->required()->maxLength(80)
                ->regex('/^[a-z0-9\-]+$/')->unique(ignoreRecord: true)
                ->helperText('يُولَّد تلقائياً من ترجمة اسم الخدمة — يمكنك تعديله قبل الحفظ.'),
            Forms\Components\TextInput::make('fee')->label('الرسوم (ج.م)')->numeric()->required()->minValue(0)->maxValue(1000000)->default(500),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Forms\Components\Textarea::make('description')->label('وصف مختصر للمواطن')->maxLength(500)->rows(2)->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->label('مفعلة')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الخدمة')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('fee')->label('الرسوم')->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ج.م')->sortable(),
                Tables\Columns\TextColumn::make('requests_count')->counts('requests')->label('عدد الطلبات'),
                Tables\Columns\ToggleColumn::make('is_active')->label('مفعلة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn (ServiceType $record) => ! $record->requests()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageServiceTypes::route('/')];
    }
}
