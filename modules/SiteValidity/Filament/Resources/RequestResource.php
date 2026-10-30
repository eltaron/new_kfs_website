<?php

namespace Modules\SiteValidity\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\SiteValidity\Filament\Resources\RequestResource\Pages;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Models\InspectionField;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

class RequestResource extends Resource
{
    protected static ?string $model = SiteValidityRequest::class;

    protected static ?string $slug = 'requests';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'الطلبات';

    protected static ?string $navigationLabel = 'طلبات صلاحية الموقع';

    protected static ?string $modelLabel = 'طلب صلاحية موقع';

    protected static ?string $pluralModelLabel = 'طلبات صلاحية الموقع';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user())->with(['serviceType']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'pickup_code', 'applicant_name', 'national_id', 'phone'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['الحالة' => SvStatus::label($record->status), 'مقدم الطلب' => $record->applicant_name];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (SvAccess::isTech($user) && ! SvAccess::isStaff($user)) {
            return null; // المركز التكنولوجي لا يعالج الطلبات
        }
        $statuses = match (true) {
            SvAccess::isAdmin($user) => [],
            SvAccess::isGeo($user) && SvAccess::isEngineer($user) => [SvStatus::SUBMITTED, SvStatus::APPOINTMENT_SET, SvStatus::INSPECTED, SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_SUBMITTED],
            SvAccess::isGeo($user) => [SvStatus::SUBMITTED, SvStatus::INSPECTED],
            SvAccess::isEngineer($user) => [SvStatus::APPOINTMENT_SET, SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_SUBMITTED],
            SvAccess::isDirector($user) => [SvStatus::FINAL_UPLOADED],
            default => [],
        };
        if ($statuses === []) {
            $statuses = [SvStatus::SUBMITTED];
        }
        $count = SiteValidityRequest::query()->whereIn('status', $statuses)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ───────────────────────────────── form (admin edit only)

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الطلب (تعديل إداري — يُسجل في الخط الزمني)')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('service_type_id')->label('نوع الخدمة')
                        ->options(fn () => ServiceType::query()->orderBy('sort_order')->pluck('name', 'id'))->required(),
                    Forms\Components\Select::make('construction_type')->label('نوع الصلاحية')->options(SiteValidityRequest::CONSTRUCTION_TYPES)->required(),
                    Forms\Components\Select::make('applicant_capacity')->label('صفة مقدم الطلب')->options(SiteValidityRequest::CAPACITIES)->required(),
                    Forms\Components\TextInput::make('fee')->label('الرسوم (ج.م)')->numeric()->minValue(0)->maxValue(1000000)->required(),
                    Forms\Components\TextInput::make('applicant_name')->label('اسم مقدم الطلب')->required()->maxLength(120),
                    Forms\Components\TextInput::make('national_id')->label('الرقم القومي')->required()->length(14)->regex('/^\d{14}$/'),
                    Forms\Components\TextInput::make('phone')->label('الهاتف')->maxLength(20)->regex('/^[0-9+]{8,20}$/'),
                    Forms\Components\Select::make('center_id')->label('المركز')
                        ->options(fn () => Centre::query()->active()->pluck('name', 'id'))->required(),
                    Forms\Components\TextInput::make('address')->label('عنوان الموقع')->maxLength(300)->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('ملاحظات المواطن')->maxLength(1000)->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    // ───────────────────────────────── table

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('رقم الطلب')->searchable()->sortable()->weight('bold')->copyable(),
                Tables\Columns\TextColumn::make('pickup_code')->label('كود الاستلام')->searchable()->copyable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('applicant_name')->label('مقدم الطلب')->searchable()->limit(28),
                Tables\Columns\TextColumn::make('national_id')->label('الرقم القومي')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('serviceType.name')->label('الخدمة')->limit(26)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('construction_type')->label('نوع الصلاحية')->badge()
                    ->formatStateUsing(fn (?string $state) => SiteValidityRequest::CONSTRUCTION_TYPES[$state] ?? $state)
                    ->color(fn (?string $state) => $state === SiteValidityRequest::CONSTRUCTION_EXTENSION ? 'warning' : 'primary')->toggleable(),
                Tables\Columns\TextColumn::make('applicant_capacity')->label('الصفة')
                    ->formatStateUsing(fn (?string $state) => SiteValidityRequest::CAPACITIES[$state] ?? $state)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('center_name')->label('الموقع')
                    ->formatStateUsing(fn ($state, SiteValidityRequest $record) => $record->locationLabel())->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => SvStatus::label($state))
                    ->color(fn (string $state) => SvStatus::color($state)),
                Tables\Columns\TextColumn::make('appointment_at')->label('موعد المعاينة')->dateTime('Y-m-d H:i')->sortable()->placeholder('—'),
                Tables\Columns\IconColumn::make('paid_at')->label('مدفوع')->boolean()
                    ->state(fn (SiteValidityRequest $record) => $record->paid_at !== null),
                Tables\Columns\TextColumn::make('fee')->label('الرسوم')->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ج.م')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التقديم')->dateTime('Y-m-d')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(SvStatus::options())->multiple(),
                Tables\Filters\SelectFilter::make('service_type_id')->label('نوع الخدمة')
                    ->options(fn () => ServiceType::query()->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('construction_type')->label('نوع الصلاحية')->options(SiteValidityRequest::CONSTRUCTION_TYPES),
                Tables\Filters\SelectFilter::make('applicant_capacity')->label('صفة مقدم الطلب')->options(SiteValidityRequest::CAPACITIES),
                Tables\Filters\SelectFilter::make('center_id')->label('المركز')
                    ->options(fn () => Centre::query()->active()->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('paid')->label('السداد')
                    ->trueLabel('مدفوع')->falseLabel('غير مدفوع')
                    ->queries(true: fn (Builder $q) => $q->whereNotNull('paid_at'), false: fn (Builder $q) => $q->whereNull('paid_at')),
                Tables\Filters\Filter::make('overdue')->label('مواعيد متأخرة')->query(fn (Builder $q) => $q->overdue()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('فتح'),
            ])
            ->bulkActions([]);
    }

    // ───────────────────────────────── infolist

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('ملخص الطلب')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('number')->label('رقم الطلب')->weight('bold')->copyable(),
                    Infolists\Components\TextEntry::make('status')->label('الحالة')->badge()
                        ->formatStateUsing(fn (string $state) => SvStatus::label($state))
                        ->color(fn (string $state) => SvStatus::color($state)),
                    Infolists\Components\TextEntry::make('serviceType.name')->label('نوع الخدمة'),
                    Infolists\Components\TextEntry::make('construction_type')->label('نوع الصلاحية')->badge()
                        ->state(fn (SiteValidityRequest $record) => $record->constructionTypeLabel())
                        ->color(fn (SiteValidityRequest $record) => $record->construction_type === SiteValidityRequest::CONSTRUCTION_EXTENSION ? 'warning' : 'primary'),
                    Infolists\Components\TextEntry::make('created_at')->label('تاريخ التقديم')->dateTime('Y-m-d H:i'),
                    Infolists\Components\TextEntry::make('appointment_at')->label('موعد المعاينة')->dateTime('Y-m-d H:i')->placeholder('لم يحدد بعد')
                        ->color(fn (SiteValidityRequest $record) => $record->appointment_at?->isPast() && in_array($record->status, [SvStatus::APPOINTMENT_SET, SvStatus::APPROVED], true) ? 'danger' : null),
                    Infolists\Components\TextEntry::make('appointment_notes')->label('ملاحظات الموعد')->placeholder('—'),
                    Infolists\Components\TextEntry::make('appointment_confirmation')->label('تأكيد المواطن للموعد')->badge()
                        ->visible(fn (SiteValidityRequest $record) => $record->appointment_confirmation !== null)
                        ->formatStateUsing(fn (?string $state, SiteValidityRequest $record) => $state === SiteValidityRequest::CONFIRMATION_ACCEPTED
                            ? 'أكد المواطن ' . $record->appointment_confirmed_at?->format('Y-m-d H:i') : 'بانتظار تأكيد المواطن')
                        ->color(fn (?string $state) => $state === SiteValidityRequest::CONFIRMATION_ACCEPTED ? 'success' : 'warning'),
                    Infolists\Components\TextEntry::make('fee')->label('الرسوم')->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ج.م'),
                    Infolists\Components\TextEntry::make('pickup_code')->label('كود الاستلام')->copyable()->weight('bold')
                        ->visible(fn (SiteValidityRequest $record) => filled($record->pickup_code)),
                    Infolists\Components\TextEntry::make('paid_at')->label('السداد')
                        ->state(fn (SiteValidityRequest $record) => $record->paid_at ? 'مدفوع ' . $record->paid_at->format('Y-m-d') : 'غير مدفوع')
                        ->badge()->color(fn (SiteValidityRequest $record) => $record->paid_at ? 'success' : 'warning')
                        ->tooltip(fn (SiteValidityRequest $record) => $record->payment_reference ? 'مرجع الدفع: ' . $record->payment_reference : null),
                ]),
            Infolists\Components\Section::make('بيانات مقدم الطلب والموقع')
                ->columns(3)
                ->collapsible()
                ->schema([
                    Infolists\Components\TextEntry::make('applicant_name')->label('الاسم'),
                    Infolists\Components\TextEntry::make('applicant_capacity')->label('صفة مقدم الطلب')->badge()
                        ->state(fn (SiteValidityRequest $record) => $record->capacityLabel())
                        ->color(fn (SiteValidityRequest $record) => $record->applicant_capacity === SiteValidityRequest::CAPACITY_AGENT ? 'warning' : 'gray'),
                    Infolists\Components\TextEntry::make('national_id')->label('الرقم القومي')->copyable(),
                    Infolists\Components\TextEntry::make('phone')->label('الهاتف')->placeholder('—'),
                    Infolists\Components\TextEntry::make('center_name')->label('المركز'),
                    Infolists\Components\TextEntry::make('village_name')->label('المدينة أو القرية'),
                    Infolists\Components\TextEntry::make('address')->label('عنوان الموقع')->placeholder('—'),
                    Infolists\Components\TextEntry::make('notes')->label('ملاحظات المواطن')->placeholder('—')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('geoUser.name')->label('المركز الجيومكاني المسند')->placeholder('كل حسابات المركز'),
                    Infolists\Components\TextEntry::make('engineerUser.name')->label('مهندس التنظيم المسند')->placeholder('كل مهندسي التنظيم'),
                ]),
            Infolists\Components\Section::make('المعاينة')
                ->columns(2)
                ->collapsible()
                ->schema([
                    Infolists\Components\TextEntry::make('geo_inspected_at')->label('المركز الجيومكاني: تمت المعاينة')->dateTime('Y-m-d H:i')->placeholder('لم تُسجل')
                        ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock'),
                    Infolists\Components\TextEntry::make('engineer_inspected_at')->label('مهندس التنظيم: تمت المعاينة')->dateTime('Y-m-d H:i')->placeholder('لم تُسجل')
                        ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock'),
                    Infolists\Components\TextEntry::make('geo_inspection_notes')->label('ملاحظات المركز الجيومكاني')->placeholder('—'),
                    Infolists\Components\TextEntry::make('inspection_notes')->label('ملاحظات المهندس')->placeholder('—'),
                    Infolists\Components\TextEntry::make('existing_building_height')->label('ارتفاع المبنى القائم')
                        ->visible(fn (SiteValidityRequest $record) => $record->construction_type === SiteValidityRequest::CONSTRUCTION_EXTENSION)
                        ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) . ' م' : null)->placeholder('لم يُسجل'),
                    Infolists\Components\TextEntry::make('inspection_updated_at')->label('آخر تعديل للاستمارة')->dateTime('Y-m-d H:i')
                        ->visible(fn (SiteValidityRequest $record) => $record->inspection_updated_at !== null)->icon('heroicon-o-pencil-square')->color('warning'),
                    Infolists\Components\ViewEntry::make('inspection_data')->label('استمارة المعاينة')
                        ->visible(fn (SiteValidityRequest $record) => ! empty($record->inspection_data))
                        ->view('sitevalidity::filament.inspection')->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('الملفات')
                ->collapsible()
                ->schema([Infolists\Components\ViewEntry::make('files')->hiddenLabel()->view('sitevalidity::filament.files')->columnSpanFull()]),
            Infolists\Components\Section::make('الاستيفاء والقرار')
                ->collapsible()
                ->visible(fn (SiteValidityRequest $record) => $record->completion_rounds > 0 || $record->final_notes || $record->rejection_reason)
                ->schema([Infolists\Components\ViewEntry::make('completions')->hiddenLabel()->view('sitevalidity::filament.completions')->columnSpanFull()]),
            Infolists\Components\Section::make('الخط الزمني (سجل التدقيق)')
                ->collapsible()
                ->schema([Infolists\Components\ViewEntry::make('timeline')->hiddenLabel()->view('sitevalidity::filament.timeline')->columnSpanFull()]),
        ]);
    }

    /** إدخال طلب نيابةً عن المواطن: المركز التكنولوجي ومسؤول الخدمة فقط. */
    public static function canCreate(): bool
    {
        return SvAccess::canCreateRequests(auth()->user());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'view' => Pages\ViewRequest::route('/{record}'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }
}
