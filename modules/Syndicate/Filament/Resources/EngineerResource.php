<?php

namespace Modules\Syndicate\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Syndicate\Filament\PanelContext;
use Modules\Syndicate\Filament\Resources\EngineerResource\Pages;
use Modules\Syndicate\Models\Engineer;

/** دليل المهندسين والمكاتب (لوحة النقابة فقط). يُزامن أيضاً عبر API: POST engineers/sync */
class EngineerResource extends Resource
{
    protected static ?string $model = Engineer::class;

    protected static ?string $slug = 'engineers';
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'دليل المهندسين';
    protected static ?string $modelLabel = 'مهندس';
    protected static ?string $pluralModelLabel = 'المهندسون';
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return PanelContext::isSyndicate() && PanelContext::userCanUsePanel();
    }

    public static function canDelete(Model $record): bool
    {
        return PanelContext::isSyndicate() && ! $record->certificates()->exists();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'syndicate_number', 'national_id', 'office_name'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المهندس')->schema([
                Forms\Components\TextInput::make('syndicate_number')->label('رقم عضوية النقابة')->required()->maxLength(50)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')->label('الاسم')->required()->maxLength(255),
                Forms\Components\TextInput::make('national_id')->label('الرقم القومي')->length(14)->regex('/^\d{14}$/')->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label('الهاتف')->tel()->maxLength(30),
                Forms\Components\TextInput::make('email')->label('البريد الإلكتروني')->email()->maxLength(255),
                Forms\Components\TextInput::make('specialization')->label('الشعبة / التخصص')->maxLength(100),
                Forms\Components\Select::make('grade')->label('الدرجة')->options(['استشاري' => 'استشاري', 'أول' => 'مهندس أول', 'ممارس' => 'ممارس', 'مساعد' => 'مساعد']),
                Forms\Components\TextInput::make('registration_number')->label('رقم السجل الهندسي')->maxLength(50),
                Forms\Components\Select::make('membership_status')->label('حالة العضوية')->options(Engineer::membershipStatuses())->default('active')->required(),
            ])->columns(3),
            Forms\Components\Section::make('المكتب الهندسي')->schema([
                Forms\Components\TextInput::make('office_name')->label('اسم المكتب')->maxLength(255),
                Forms\Components\TextInput::make('office_registration_number')->label('رقم قيد المكتب')->maxLength(50),
            ])->columns(2),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('syndicate_number')->label('رقم العضوية')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->description(fn (Engineer $e) => $e->office_name),
                Tables\Columns\TextColumn::make('specialization')->label('التخصص')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف')->toggleable(),
                Tables\Columns\TextColumn::make('membership_status')->label('العضوية')->badge()
                    ->formatStateUsing(fn ($state) => Engineer::membershipStatuses()[$state] ?? $state)
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('active_count')->label('مواقع تحت إشرافه')
                    ->getStateUsing(fn (Engineer $e) => $e->certificates()->where('kind', 'supervision')->where('status', 'active')->count()),
                Tables\Columns\TextColumn::make('synced_at')->label('آخر مزامنة')->since()->placeholder('يدوي')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('membership_status')->label('العضوية')->options(Engineer::membershipStatuses()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEngineers::route('/'),
            'create' => Pages\CreateEngineer::route('/create'),
            'edit' => Pages\EditEngineer::route('/{record}/edit'),
        ];
    }
}
