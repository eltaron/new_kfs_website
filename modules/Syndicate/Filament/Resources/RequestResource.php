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
use Modules\Syndicate\Filament\Resources\RequestResource\Pages;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;

class RequestResource extends Resource
{
    protected static ?string $model = SyndicateRequest::class;

    protected static ?string $slug = 'requests';
    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel = 'الطلبات (المعاملات)';
    protected static ?string $modelLabel = 'طلب';
    protected static ?string $pluralModelLabel = 'الطلبات';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'transaction_number';

    public static function canAccess(): bool
    {
        return PanelContext::userCanUsePanel();
    }

    /** الطلبات تُسجل بمجلس المدينة فقط. */
    public static function canCreate(): bool
    {
        return PanelContext::isCouncil() && PanelContext::userCanUsePanel();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SyndicateRequest::open()->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'طلبات مفتوحة';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['transaction_number', 'applicant_name', 'applicant_national_id', 'project.code', 'project.owner_national_id'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['النوع' => $record->type_label, 'الحالة' => $record->status_label];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('المشروع (موقع البناء)')
                ->description('ابحث بكود المشروع أو الرقم القومي للمالك أو رقم الترخيص، أو سجل مشروعاً جديداً.')
                ->schema([
                    Forms\Components\Select::make('project_id')->label('المشروع')
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => Project::query()
                            ->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                                ->orWhere('owner_national_id', 'like', "%{$search}%")
                                ->orWhere('owner_name', 'like', "%{$search}%")
                                ->orWhere('license_number', 'like', "%{$search}%"))
                            ->limit(30)->get()
                            ->mapWithKeys(fn (Project $p) => [$p->getKey() => static::projectLabel($p)])->all())
                        ->getOptionLabelUsing(fn ($value) => ($p = Project::find($value)) ? static::projectLabel($p) : null)
                        ->createOptionForm(ProjectResource::projectFormSchema())
                        ->createOptionUsing(fn (array $data) => app(\Modules\Syndicate\Services\SupervisionService::class)
                            ->findOrRegisterProject($data, \Modules\Syndicate\Support\Actor::fromUser(auth()->user()))->getKey())
                        ->createOptionModalHeading('تسجيل مشروع جديد')
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                            $p = $state ? Project::find($state) : null;
                            if ($p && $get('requested_by') !== SyndicateRequest::BY_ENGINEER) {
                                $set('applicant_name', $p->owner_name);
                                $set('applicant_national_id', $p->owner_national_id);
                                $set('applicant_phone', $p->owner_phone);
                                $set('applicant_email', $p->owner_email);
                            }
                        }),
                    Forms\Components\Placeholder::make('project_status')->label('حالة المشروع')
                        ->content(fn (Forms\Get $get) => ($p = Project::find($get('project_id')))
                            ? $p->work_status_label.($p->activeCertificate ? ' — المهندس: '.$p->activeCertificate->engineer_name : '')
                            : '—'),
                ])->columns(2),
            Forms\Components\Section::make('بيانات الطلب')
                ->schema([
                    Forms\Components\Select::make('type')->label('نوع الطلب')->options(SyndicateRequest::types())
                        ->default(SyndicateRequest::TYPE_SUPERVISION)->required()->live(),
                    Forms\Components\Select::make('requested_by')->label('مقدم الطلب')
                        ->options(fn (Forms\Get $get) => $get('type') === SyndicateRequest::TYPE_SUPERVISION
                            ? array_intersect_key(SyndicateRequest::requesters(), array_flip([SyndicateRequest::BY_CITIZEN, SyndicateRequest::BY_COUNCIL]))
                            : SyndicateRequest::requesters())
                        ->default(SyndicateRequest::BY_CITIZEN)->required()->live()
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                            $p = Project::find($get('project_id'));
                            if ($state === SyndicateRequest::BY_ENGINEER && $c = $p?->activeCertificate) {
                                $set('applicant_name', $c->engineer_name);
                                $set('applicant_national_id', $c->engineer_national_id);
                                $set('applicant_phone', $c->engineer_phone);
                                $set('applicant_email', $c->engineer_email);
                            }
                        }),
                    Forms\Components\TextInput::make('applicant_name')->label('اسم مقدم الطلب')->required()->maxLength(255),
                    Forms\Components\TextInput::make('applicant_national_id')->label('الرقم القومي')->length(14)->regex('/^\d{14}$/'),
                    Forms\Components\TextInput::make('applicant_phone')->label('الهاتف')->tel()->maxLength(30),
                    Forms\Components\TextInput::make('applicant_email')->label('البريد الإلكتروني')->email()->maxLength(255),
                    Forms\Components\Textarea::make('reason')->label(fn (Forms\Get $get) => $get('type') === SyndicateRequest::TYPE_CANCELLATION ? 'سبب طلب الإلغاء' : 'ملاحظات')
                        ->required(fn (Forms\Get $get) => $get('type') === SyndicateRequest::TYPE_CANCELLATION)
                        ->rows(2)->maxLength(1000)->columnSpanFull(),
                ])->columns(2),
        ])->columns(1);
    }

    public static function projectLabel(Project $p): string
    {
        return $p->code.' — '.$p->owner_name.' — '.$p->center.' ('.$p->work_status_label.')';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')->label('رقم المعاملة')->searchable()->sortable()->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => SyndicateRequest::types()[$state] ?? $state)
                    ->color(fn ($state) => $state === SyndicateRequest::TYPE_CANCELLATION ? 'danger' : 'info'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => SyndicateRequest::statuses()[$state] ?? $state)
                    ->color(fn ($state) => SyndicateRequest::statusColors()[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('project.code')->label('المشروع')->searchable(),
                Tables\Columns\TextColumn::make('project.center')->label('المركز')->toggleable(),
                Tables\Columns\TextColumn::make('applicant_name')->label('مقدم الطلب')->searchable()
                    ->description(fn (SyndicateRequest $r) => SyndicateRequest::requesters()[$r->requested_by] ?? null),
                Tables\Columns\TextColumn::make('channel')->label('القناة')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => ['counter' => 'شباك المجلس', 'online' => 'إلكتروني', 'api' => 'API'][$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('certificate.number')->label('الشهادة الصادرة')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التقديم')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(SyndicateRequest::statuses())->multiple(),
                Tables\Filters\SelectFilter::make('type')->label('النوع')->options(SyndicateRequest::types()),
                Tables\Filters\SelectFilter::make('requested_by')->label('مقدم الطلب')->options(SyndicateRequest::requesters()),
                Tables\Filters\SelectFilter::make('center')->label('المركز')
                    ->options(array_combine(config('syndicate.centers'), config('syndicate.centers')))
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('project', fn ($p) => $p->where('center', $data['value'])) : $query),
                Tables\Filters\TernaryFilter::make('open')->label('مفتوحة فقط')
                    ->queries(
                        true: fn (Builder $query) => $query->open(),
                        false: fn (Builder $query) => $query->whereNotIn('status', SyndicateRequest::OPEN_STATUSES),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(fn () => PanelContext::isSyndicate() ? 'فتح ومعالجة' : 'عرض'),
            ])
            ->recordClasses(fn (SyndicateRequest $r) => $r->type === SyndicateRequest::TYPE_CANCELLATION && $r->isOpen() ? 'bg-danger-50 dark:bg-danger-950/30' : null)
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('المعاملة')
                ->schema([
                    Infolists\Components\TextEntry::make('transaction_number')->label('رقم المعاملة')->copyable()->weight('bold'),
                    Infolists\Components\TextEntry::make('type')->label('النوع')->badge()
                        ->formatStateUsing(fn ($state) => SyndicateRequest::types()[$state] ?? $state)
                        ->color(fn ($state) => $state === SyndicateRequest::TYPE_CANCELLATION ? 'danger' : 'info'),
                    Infolists\Components\TextEntry::make('status')->label('الحالة')->badge()
                        ->formatStateUsing(fn ($state) => SyndicateRequest::statuses()[$state] ?? $state)
                        ->color(fn ($state) => SyndicateRequest::statusColors()[$state] ?? 'gray'),
                    Infolists\Components\TextEntry::make('requested_by')->label('مقدم من')
                        ->formatStateUsing(fn ($state) => SyndicateRequest::requesters()[$state] ?? $state),
                    Infolists\Components\TextEntry::make('applicant_name')->label('اسم مقدم الطلب'),
                    Infolists\Components\TextEntry::make('applicant_national_id')->label('الرقم القومي')->placeholder('—'),
                    Infolists\Components\TextEntry::make('applicant_phone')->label('الهاتف')->placeholder('—'),
                    Infolists\Components\TextEntry::make('created_at')->label('تاريخ التقديم')->dateTime('Y-m-d H:i'),
                    Infolists\Components\TextEntry::make('completed_at')->label('تاريخ الإنجاز')->dateTime('Y-m-d H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('reason')->label('السبب / الملاحظات')->placeholder('—')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('rejection_reason')->label('سبب الرفض')->color('danger')
                        ->visible(fn (SyndicateRequest $r) => filled($r->rejection_reason))->columnSpanFull(),
                ])->columns(3),
            Infolists\Components\Section::make('المشروع')
                ->schema([
                    Infolists\Components\TextEntry::make('project.code')->label('كود المشروع')
                        ->url(fn (SyndicateRequest $r) => ProjectResource::getUrl('view', ['record' => $r->project_id])),
                    Infolists\Components\TextEntry::make('project.work_status')->label('حالة الأعمال')->badge()
                        ->formatStateUsing(fn ($state) => Project::workStatuses()[$state] ?? $state)
                        ->color(fn ($state) => Project::workStatusColors()[$state] ?? 'gray'),
                    Infolists\Components\TextEntry::make('project.owner_name')->label('المالك'),
                    Infolists\Components\TextEntry::make('project.owner_national_id')->label('الرقم القومي للمالك'),
                    Infolists\Components\TextEntry::make('project.center')->label('المركز'),
                    Infolists\Components\TextEntry::make('project.license_number')->label('رقم الترخيص')->placeholder('—'),
                    Infolists\Components\TextEntry::make('project.address')->label('العنوان')->columnSpanFull(),
                ])->columns(3),
            Infolists\Components\Section::make('الشهادة المطلوب إلغاؤها')
                ->visible(fn (SyndicateRequest $r) => $r->type === SyndicateRequest::TYPE_CANCELLATION)
                ->schema([
                    Infolists\Components\TextEntry::make('targetCertificate.number')->label('رقم الشهادة')->placeholder('—'),
                    Infolists\Components\TextEntry::make('targetCertificate.engineer_name')->label('المهندس')->placeholder('—'),
                    Infolists\Components\TextEntry::make('targetCertificate.engineer_syndicate_number')->label('رقم العضوية')->placeholder('—'),
                ])->columns(3),
            Infolists\Components\Section::make('الشهادة الصادرة')
                ->visible(fn (SyndicateRequest $r) => (bool) $r->certificate_id)
                ->schema([
                    Infolists\Components\TextEntry::make('certificate.number')->label('رقم الشهادة')
                        ->url(fn (SyndicateRequest $r) => $r->certificate_id ? CertificateResource::getUrl('view', ['record' => $r->certificate_id]) : null),
                    Infolists\Components\TextEntry::make('certificate.kind')->label('النوع')
                        ->formatStateUsing(fn ($state) => \Modules\Syndicate\Models\Certificate::kinds()[$state] ?? $state),
                    Infolists\Components\TextEntry::make('certificate.issued_at')->label('تاريخ الإصدار')->dateTime('Y-m-d H:i'),
                ])->columns(3),
            Infolists\Components\Section::make('الخط الزمني للمشروع')
                ->schema([Infolists\Components\ViewEntry::make('timeline')->view('syndicate::filament.timeline')->columnSpanFull()])
                ->collapsible(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['project', 'certificate']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'view' => Pages\ViewRequest::route('/{record}'),
        ];
    }
}
