<?php

namespace Modules\Syndicate\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Syndicate\Filament\PanelContext;
use Modules\Syndicate\Filament\Resources\ProjectResource\Pages;
use Modules\Syndicate\Models\Project;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $slug = 'projects';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'المشروعات (المواقع)';
    protected static ?string $modelLabel = 'مشروع';
    protected static ?string $pluralModelLabel = 'المشروعات';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'code';

    public static function canAccess(): bool
    {
        return PanelContext::userCanUsePanel();
    }

    public static function canCreate(): bool
    {
        return PanelContext::isCouncil() && PanelContext::userCanUsePanel();
    }

    public static function canEdit(Model $record): bool
    {
        return PanelContext::isCouncil() && PanelContext::userCanUsePanel();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Project::where('work_status', Project::WORK_SUSPENDED)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'مشروعات موقوفة بدون إشراف';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'owner_name', 'owner_national_id', 'license_number', 'requests.transaction_number', 'certificates.number'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['المالك' => $record->owner_name, 'الحالة' => $record->work_status_label];
    }

    /** نموذج بيانات المشروع (يستخدم أيضاً داخل نموذج تسجيل الطلب). */
    public static function projectFormSchema(): array
    {
        $centers = array_combine(config('syndicate.centers'), config('syndicate.centers'));

        return [
            Forms\Components\Section::make('بيانات المالك')
                ->schema([
                    Forms\Components\TextInput::make('owner_name')->label('اسم المالك')->required()->maxLength(255),
                    Forms\Components\TextInput::make('owner_national_id')->label('الرقم القومي للمالك')
                        ->required()->length(14)->regex('/^\d{14}$/')->validationMessages(['regex' => 'الرقم القومي 14 رقماً.']),
                    Forms\Components\TextInput::make('owner_phone')->label('هاتف المالك')->tel()->maxLength(30),
                    Forms\Components\TextInput::make('owner_email')->label('البريد الإلكتروني')->email()->maxLength(255),
                    Forms\Components\TextInput::make('owner_address')->label('عنوان المالك')->maxLength(255)->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('بيانات الموقع')
                ->schema([
                    Forms\Components\Select::make('center')->label('المركز / المدينة')->options($centers)->required()->searchable(),
                    Forms\Components\TextInput::make('village')->label('القرية / الحي')->maxLength(100),
                    Forms\Components\TextInput::make('plot_number')->label('رقم القطعة')->maxLength(50),
                    Forms\Components\Textarea::make('address')->label('عنوان الموقع بالتفصيل')->required()->rows(2)->columnSpanFull(),
                    Forms\Components\TextInput::make('latitude')->label('خط العرض')->numeric(),
                    Forms\Components\TextInput::make('longitude')->label('خط الطول')->numeric(),
                ])->columns(3),
            Forms\Components\Section::make('بيانات الترخيص والأعمال')
                ->schema([
                    Forms\Components\TextInput::make('license_number')->label('رقم الترخيص')->maxLength(50),
                    Forms\Components\DatePicker::make('license_date')->label('تاريخ الترخيص'),
                    Forms\Components\TextInput::make('license_authority')->label('جهة إصدار الترخيص')->maxLength(255),
                    Forms\Components\Select::make('works_type')->label('نوع التدخل')
                        ->options(['execution' => 'تنفيذ', 'demolition' => 'هدم'])->default('execution')->required()->live(),
                    Forms\Components\Select::make('execution_sub_type')->label('نوع التنفيذ')
                        ->options(['creation' => 'إنشاء', 'elevation' => 'تعلية'])
                        ->visible(fn (Forms\Get $get) => $get('works_type') === 'execution'),
                    Forms\Components\TextInput::make('works_value')->label('قيمة الأعمال')->numeric()->prefix('ج.م'),
                    Forms\Components\Textarea::make('works_description')->label('بيان الأعمال المرخص بها')->rows(2)->columnSpanFull(),
                ])->columns(3),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::projectFormSchema())->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('كود المشروع')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('owner_name')->label('المالك')->searchable()
                    ->description(fn (Project $r) => $r->owner_national_id),
                Tables\Columns\TextColumn::make('owner_national_id')->label('الرقم القومي')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('center')->label('المركز')->sortable(),
                Tables\Columns\TextColumn::make('license_number')->label('رقم الترخيص')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('work_status')->label('حالة الأعمال')->badge()
                    ->formatStateUsing(fn ($state) => Project::workStatuses()[$state] ?? $state)
                    ->color(fn ($state) => Project::workStatusColors()[$state] ?? 'gray')
                    ->icon(fn ($state) => $state === Project::WORK_SUSPENDED ? 'heroicon-o-exclamation-triangle' : null),
                Tables\Columns\TextColumn::make('activeCertificate.engineer_name')->label('المهندس المشرف')->placeholder('—'),
                Tables\Columns\TextColumn::make('activeCertificate.number')->label('شهادة الإشراف')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('work_status_changed_at')->label('آخر تغيير للحالة')->since()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('work_status')->label('حالة الأعمال')->options(Project::workStatuses()),
                Tables\Filters\SelectFilter::make('center')->label('المركز')
                    ->options(array_combine(config('syndicate.centers'), config('syndicate.centers'))),
                Tables\Filters\Filter::make('lookup')
                    ->form([Forms\Components\TextInput::make('number')->label('رقم المعاملة أو رقم الشهادة')])
                    ->query(function (Builder $query, array $data) {
                        $n = strtoupper(trim((string) ($data['number'] ?? '')));
                        if ($n === '') {
                            return $query;
                        }

                        return $query->where(fn (Builder $q) => $q
                            ->whereHas('requests', fn ($r) => $r->where('transaction_number', $n))
                            ->orWhereHas('certificates', fn ($c) => $c->where('number', $n)));
                    })
                    ->indicateUsing(fn (array $data) => filled($data['number'] ?? null) ? 'بحث: ' . $data['number'] : null),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),
            ])
            ->recordClasses(fn (Project $r) => $r->isSuspended() ? 'bg-danger-50 dark:bg-danger-950/30' : null)
            ->defaultSort('updated_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\ViewEntry::make('work_status_banner')
                ->view('syndicate::filament.work-status-banner')
                ->columnSpanFull(),
            Infolists\Components\Section::make('بيانات المشروع')
                ->schema([
                    Infolists\Components\TextEntry::make('code')->label('كود المشروع')->copyable(),
                    Infolists\Components\TextEntry::make('work_status')->label('حالة الأعمال')->badge()
                        ->formatStateUsing(fn ($state) => Project::workStatuses()[$state] ?? $state)
                        ->color(fn ($state) => Project::workStatusColors()[$state] ?? 'gray'),
                    Infolists\Components\TextEntry::make('work_status_changed_at')->label('تاريخ آخر تغيير')->dateTime('Y-m-d H:i'),
                    Infolists\Components\TextEntry::make('owner_name')->label('المالك'),
                    Infolists\Components\TextEntry::make('owner_national_id')->label('الرقم القومي'),
                    Infolists\Components\TextEntry::make('owner_phone')->label('الهاتف')->placeholder('—'),
                    Infolists\Components\TextEntry::make('center')->label('المركز'),
                    Infolists\Components\TextEntry::make('village')->label('القرية / الحي')->placeholder('—'),
                    Infolists\Components\TextEntry::make('plot_number')->label('رقم القطعة')->placeholder('—'),
                    Infolists\Components\TextEntry::make('address')->label('العنوان')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('license_number')->label('رقم الترخيص')->placeholder('—'),
                    Infolists\Components\TextEntry::make('license_date')->label('تاريخ الترخيص')->date('Y-m-d')->placeholder('—'),
                    Infolists\Components\TextEntry::make('works_value')->label('قيمة الأعمال')->money('EGP')->placeholder('—'),
                ])->columns(3)->collapsible(),
            Infolists\Components\Section::make('الإشراف الحالي')
                ->schema([
                    Infolists\Components\TextEntry::make('activeCertificate.number')->label('رقم الشهادة')->placeholder('لا توجد شهادة سارية'),
                    Infolists\Components\TextEntry::make('activeCertificate.engineer_name')->label('المهندس المشرف')->placeholder('—'),
                    Infolists\Components\TextEntry::make('activeCertificate.engineer_syndicate_number')->label('رقم العضوية')->placeholder('—'),
                    Infolists\Components\TextEntry::make('activeCertificate.office_name')->label('المكتب الهندسي')->placeholder('—'),
                    Infolists\Components\TextEntry::make('activeCertificate.engineer_phone')->label('هاتف المهندس')->placeholder('—'),
                    Infolists\Components\TextEntry::make('activeCertificate.issued_at')->label('تاريخ الإصدار')->dateTime('Y-m-d')->placeholder('—'),
                ])->columns(3),
            Infolists\Components\Section::make('الشهادات والمعاملات')
                ->schema([
                    Infolists\Components\ViewEntry::make('records')->view('syndicate::filament.project-records')->columnSpanFull(),
                ])->collapsible(),
            Infolists\Components\Section::make('الخط الزمني للمشروع')
                ->schema([
                    Infolists\Components\ViewEntry::make('timeline')->view('syndicate::filament.timeline')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('activeCertificate');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
