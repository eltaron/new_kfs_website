<?php

namespace Modules\SiteValidity\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\SiteValidity\Filament\Resources\AttachmentTypeResource\Pages;
use Modules\SiteValidity\Models\AttachmentType;
use Modules\SiteValidity\Support\LatinKey;
use Modules\SiteValidity\Support\SvAccess;

/** أنواع المرفقات الفنية التي يرفعها المركز الجيومكاني (مسؤول الخدمة فقط). */
class AttachmentTypeResource extends Resource
{
    protected static ?string $model = AttachmentType::class;

    protected static ?string $slug = 'attachment-types';

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationGroup = 'إعدادات الخدمة';

    protected static ?string $navigationLabel = 'أنواع المرفقات';

    protected static ?string $modelLabel = 'نوع مرفق';

    protected static ?string $pluralModelLabel = 'أنواع المرفقات';

    protected static ?int $navigationSort = 12;

    public static function canViewAny(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    public static function form(Form $form): Form
    {
        $safe = (array) config('site-validity.staff_extensions_whitelist');

        return $form->schema([
            Forms\Components\TextInput::make('name')->label('اسم المرفق')->required()->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set, ?AttachmentType $record) => $record ? null : $set('key', LatinKey::make($state, '_', 64, AttachmentType::class, 'key', 'attachment'))),
            Forms\Components\TextInput::make('key')->label('المعرف (لاتيني)')->required()->maxLength(64)
                ->regex('/^[a-z][a-z0-9_]*$/')->unique(ignoreRecord: true)
                ->helperText('يُولَّد تلقائياً من ترجمة اسم المرفق — يمكنك تعديله قبل الحفظ.'),
            Forms\Components\CheckboxList::make('extensions')->label('الامتدادات المسموحة')
                ->options(array_combine($safe, $safe))->columns(5)->required()
                ->afterStateHydrated(fn (Forms\Components\CheckboxList $component, $state) => $component->state(is_string($state) ? array_filter(explode(',', $state)) : (array) $state))
                ->dehydrateStateUsing(fn ($state) => implode(',', array_values(array_intersect((array) $state, (array) config('site-validity.staff_extensions_whitelist')))))
                ->columnSpanFull(),
            Forms\Components\TextInput::make('max_size_kb')->label('الحد الأقصى للحجم (ك.ب)')->numeric()->required()->minValue(100)->maxValue(102400)->default(20480),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Forms\Components\TextInput::make('description')->label('وصف/تعليمات')->maxLength(255)->columnSpanFull(),
            Forms\Components\Toggle::make('is_required')->label('إلزامي قبل الإحالة للمهندس'),
            Forms\Components\Toggle::make('is_active')->label('مفعل')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('المرفق')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('extensions')->label('الامتدادات'),
                Tables\Columns\TextColumn::make('max_size_kb')->label('الحجم الأقصى (ك.ب)'),
                Tables\Columns\IconColumn::make('is_required')->label('إلزامي')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('مفعل'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAttachmentTypes::route('/')];
    }
}
