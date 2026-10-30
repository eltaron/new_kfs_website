<?php

namespace Modules\SiteValidity\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\SiteValidity\Filament\Resources\InspectionFieldResource\Pages;
use Modules\SiteValidity\Models\InspectionField;
use Modules\SiteValidity\Support\LatinKey;
use Modules\SiteValidity\Support\SvAccess;

/** بنود استمارة المعاينة الديناميكية لمهندس التنظيم (مسؤول الخدمة فقط). */
class InspectionFieldResource extends Resource
{
    protected static ?string $model = InspectionField::class;

    protected static ?string $slug = 'inspection-fields';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'إعدادات الخدمة';

    protected static ?string $navigationLabel = 'بنود استمارة المعاينة';

    protected static ?string $modelLabel = 'بند معاينة';

    protected static ?string $pluralModelLabel = 'بنود المعاينة';

    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('label')->label('نص السؤال/البند')->required()->maxLength(200)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set, ?InspectionField $record) => $record ? null : $set('key', LatinKey::make($state, '_', 64, InspectionField::class, 'key', 'field'))),
            Forms\Components\TextInput::make('key')->label('المعرف (لاتيني)')->required()->maxLength(64)
                ->regex('/^[a-z][a-z0-9_]*$/')->unique(ignoreRecord: true)
                ->helperText('يُولَّد تلقائياً من ترجمة نص البند — حروف لاتينية صغيرة وأرقام و _ ، ولا تغيّره بعد الاستخدام.'),
            Forms\Components\Select::make('type')->label('نوع الإجابة')->options(InspectionField::TYPES)->required()->default('yes_no')->live(),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Forms\Components\TagsInput::make('options')->label('خيارات القائمة')
                ->visible(fn (Forms\Get $get) => $get('type') === 'select')
                ->required(fn (Forms\Get $get) => $get('type') === 'select')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('help_text')->label('نص مساعد')->maxLength(255)->columnSpanFull(),
            Forms\Components\Toggle::make('is_required')->label('إلزامي'),
            Forms\Components\Toggle::make('is_active')->label('مفعل')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(fn (Tables\Actions\Action $action, bool $isReordering) => $action
                ->button()
                ->label($isReordering ? 'إنهاء وحفظ الترتيب' : 'إعادة ترتيب البنود')
                ->icon($isReordering ? 'heroicon-m-check' : 'heroicon-m-arrows-up-down')
                ->color($isReordering ? 'success' : 'gray'))
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#'),
                Tables\Columns\TextColumn::make('label')->label('البند')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge()->formatStateUsing(fn ($state) => InspectionField::TYPES[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_required')->label('إلزامي')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('مفعل'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInspectionFields::route('/')];
    }
}
