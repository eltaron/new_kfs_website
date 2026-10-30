<?php

namespace Modules\Syndicate\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Modules\Syndicate\Filament\PanelContext;
use Modules\Syndicate\Filament\Resources\CertificateResource;
use Modules\Syndicate\Filament\Resources\RequestResource;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Engineer;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;
use Modules\Syndicate\Support\SyndicateException;

/**
 * إجراءات Filament المشتركة. كل إجراء يمر عبر SupervisionService (آلة الحالات)؛
 * أخطاء قواعد العمل تظهر كإشعار ولا تُغير البيانات.
 */
class SupervisionActions
{
    // ───────────── مجلس المدينة: تسجيل الطلبات على مشروع ─────────────

    public static function registerSupervisionRequest(): Action
    {
        return Action::make('registerSupervisionRequest')
            ->label('تسجيل طلب إشراف')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->modalHeading('تسجيل طلب إشراف على الموقع')
            ->modalDescription('سيصدر رقم معاملة فريد ويظهر الطلب فوراً لدى نقابة المهندسين.')
            ->visible(fn (Project $record) => PanelContext::isCouncil()
                && ! $record->requests()->where('type', SyndicateRequest::TYPE_SUPERVISION)->open()->exists())
            ->fillForm(fn (Project $record) => [
                'requested_by' => SyndicateRequest::BY_CITIZEN,
                'applicant_name' => $record->owner_name,
                'applicant_national_id' => $record->owner_national_id,
                'applicant_phone' => $record->owner_phone,
                'applicant_email' => $record->owner_email,
            ])
            ->form(static::applicantSchema([SyndicateRequest::BY_CITIZEN, SyndicateRequest::BY_COUNCIL], false))
            ->action(fn (Project $record, array $data, Action $action) => static::submit($record, SyndicateRequest::TYPE_SUPERVISION, $data, $action));
    }

    public static function registerCancellationRequest(): Action
    {
        return Action::make('registerCancellationRequest')
            ->label('تسجيل طلب إلغاء إشراف')
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->modalHeading('تسجيل طلب إلغاء إشراف')
            ->modalDescription('بعد إصدار النقابة شهادة الإلغاء يصبح المشروع "موقوف - بدون إشراف" ويجب إيقاف الأعمال.')
            ->visible(fn (Project $record) => PanelContext::isCouncil() && $record->isActive()
                && ! $record->requests()->where('type', SyndicateRequest::TYPE_CANCELLATION)->open()->exists())
            ->fillForm(fn (Project $record) => [
                'requested_by' => SyndicateRequest::BY_CITIZEN,
                'applicant_name' => $record->owner_name,
                'applicant_national_id' => $record->owner_national_id,
                'applicant_phone' => $record->owner_phone,
                'applicant_email' => $record->owner_email,
            ])
            ->form(static::applicantSchema(array_keys(SyndicateRequest::requesters()), true))
            ->action(fn (Project $record, array $data, Action $action) => static::submit($record, SyndicateRequest::TYPE_CANCELLATION, $data, $action));
    }

    /** @param  array<int,string>  $requesters */
    public static function applicantSchema(array $requesters, bool $reasonRequired): array
    {
        return [
            Forms\Components\Select::make('requested_by')->label('مقدم الطلب')
                ->options(array_intersect_key(SyndicateRequest::requesters(), array_flip($requesters)))
                ->required()->live()
                ->afterStateUpdated(function (Forms\Set $set, ?string $state, $record) {
                    if ($state === SyndicateRequest::BY_ENGINEER && $record instanceof Project && $record->activeCertificate) {
                        $c = $record->activeCertificate;
                        $set('applicant_name', $c->engineer_name);
                        $set('applicant_national_id', $c->engineer_national_id);
                        $set('applicant_phone', $c->engineer_phone);
                        $set('applicant_email', $c->engineer_email);
                    } elseif ($record instanceof Project) {
                        $set('applicant_name', $record->owner_name);
                        $set('applicant_national_id', $record->owner_national_id);
                        $set('applicant_phone', $record->owner_phone);
                        $set('applicant_email', $record->owner_email);
                    }
                }),
            Forms\Components\TextInput::make('applicant_name')->label('اسم مقدم الطلب')->required()->maxLength(255),
            Forms\Components\TextInput::make('applicant_national_id')->label('الرقم القومي')->length(14)->regex('/^\d{14}$/'),
            Forms\Components\TextInput::make('applicant_phone')->label('الهاتف')->tel()->maxLength(30),
            Forms\Components\TextInput::make('applicant_email')->label('البريد الإلكتروني')->email()->maxLength(255),
            Forms\Components\Textarea::make('reason')->label($reasonRequired ? 'سبب طلب الإلغاء' : 'ملاحظات / سبب الطلب')
                ->required($reasonRequired)->rows(2)->maxLength(1000)->columnSpanFull(),
        ];
    }

    protected static function submit(Project $project, string $type, array $data, Action $action): void
    {
        $request = static::guard($action, fn () => app(SupervisionService::class)->submitRequest(
            $project,
            $type,
            [
                'name' => $data['applicant_name'],
                'national_id' => $data['applicant_national_id'] ?? null,
                'phone' => $data['applicant_phone'] ?? null,
                'email' => $data['applicant_email'] ?? null,
            ],
            $data['requested_by'],
            Actor::fromUser(auth()->user()),
            'counter',
            $data['reason'] ?? null,
        ));

        Notification::make()->success()
            ->title('تم تسجيل الطلب برقم المعاملة '.$request->transaction_number)
            ->body('سلّم المواطن رقم المعاملة لمتابعة الطلب لدى النقابة.')
            ->persistent()->send();

        $action->redirect(RequestResource::getUrl('view', ['record' => $request]));
    }

    // ───────────── نقابة المهندسين: معالجة الطلب ─────────────

    public static function markUnderReview(): Action
    {
        return Action::make('markUnderReview')
            ->label('بدء المراجعة')
            ->icon('heroicon-o-magnifying-glass')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (SyndicateRequest $record) => PanelContext::isSyndicate() && $record->status === SyndicateRequest::STATUS_SUBMITTED)
            ->action(function (SyndicateRequest $record, Action $action) {
                static::guard($action, fn () => app(SupervisionService::class)->markUnderReview($record, Actor::fromUser(auth()->user())));
                Notification::make()->success()->title('الطلب قيد المراجعة')->send();
                $action->redirect(RequestResource::getUrl('view', ['record' => $record]));
            });
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('اعتماد الطلب')
            ->icon('heroicon-o-check')
            ->color('primary')
            ->requiresConfirmation()
            ->modalDescription('اعتماد الطلب يعني استيفاء المستندات؛ يلي ذلك إصدار الشهادة.')
            ->visible(fn (SyndicateRequest $record) => PanelContext::isSyndicate()
                && in_array($record->status, [SyndicateRequest::STATUS_SUBMITTED, SyndicateRequest::STATUS_UNDER_REVIEW], true))
            ->action(function (SyndicateRequest $record, Action $action) {
                static::guard($action, fn () => app(SupervisionService::class)->approve($record, Actor::fromUser(auth()->user())));
                Notification::make()->success()->title('تم اعتماد الطلب')->send();
                $action->redirect(RequestResource::getUrl('view', ['record' => $record]));
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض الطلب')
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->visible(fn (SyndicateRequest $record) => PanelContext::isSyndicate() && $record->isOpen())
            ->form([Forms\Components\Textarea::make('reason')->label('سبب الرفض')->required()->maxLength(2000)])
            ->action(function (SyndicateRequest $record, array $data, Action $action) {
                static::guard($action, fn () => app(SupervisionService::class)->reject($record, $data['reason'], Actor::fromUser(auth()->user())));
                Notification::make()->warning()->title('تم رفض الطلب وإخطار مقدمه ومجلس المدينة')->send();
                $action->redirect(RequestResource::getUrl('view', ['record' => $record]));
            });
    }

    public static function issueSupervision(): Action
    {
        return Action::make('issueSupervision')
            ->label('إصدار شهادة إشراف')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalHeading('إصدار شهادة إشراف على الموقع')
            ->modalDescription('ستُخطر الجهات: المالك، المهندس / المكتب، ومجلس المدينة. وأي شهادة سارية سابقة على المشروع تصبح مستبدلة.')
            ->modalWidth('3xl')
            ->visible(fn (SyndicateRequest $record) => PanelContext::isSyndicate()
                && $record->type === SyndicateRequest::TYPE_SUPERVISION && $record->isOpen())
            ->form(static::engineerSchema())
            ->action(function (SyndicateRequest $record, array $data, Action $action) {
                $certificate = static::guard($action, fn () => app(SupervisionService::class)->issueSupervisionCertificate(
                    $record,
                    [
                        'syndicate_number' => $data['syndicate_number'],
                        'name' => $data['name'] ?? null,
                        'national_id' => $data['national_id'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'specialization' => $data['specialization'] ?? null,
                        'registration_number' => $data['registration_number'] ?? null,
                        'office_name' => $data['office_name'] ?? null,
                        'office_registration_number' => $data['office_registration_number'] ?? null,
                    ],
                    Actor::fromUser(auth()->user()),
                    $data['notes'] ?? null,
                ));

                Notification::make()->success()->title('صدرت شهادة الإشراف رقم '.$certificate->number)->persistent()->send();
                $action->redirect(CertificateResource::getUrl('view', ['record' => $certificate]));
            });
    }

    public static function issueCancellation(): Action
    {
        return Action::make('issueCancellation')
            ->label('إصدار شهادة إلغاء إشراف')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('إصدار شهادة إلغاء إشراف')
            ->modalDescription('سيصبح المشروع "موقوف - بدون إشراف" ويُخطر المالك والمهندس ومجلس المدينة بوجوب إيقاف الأعمال فوراً.')
            ->modalSubmitActionLabel('إصدار وإيقاف الأعمال')
            ->visible(fn (SyndicateRequest $record) => PanelContext::isSyndicate()
                && $record->type === SyndicateRequest::TYPE_CANCELLATION && $record->isOpen())
            ->form([Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(2)->maxLength(2000)])
            ->action(function (SyndicateRequest $record, array $data, Action $action) {
                $certificate = static::guard($action, fn () => app(SupervisionService::class)
                    ->issueCancellationCertificate($record, Actor::fromUser(auth()->user()), $data['notes'] ?? null));

                Notification::make()->danger()->title('صدرت شهادة إلغاء الإشراف رقم '.$certificate->number)
                    ->body('المشروع الآن موقوف - بدون إشراف.')->persistent()->send();
                $action->redirect(CertificateResource::getUrl('view', ['record' => $certificate]));
            });
    }

    public static function engineerSchema(): array
    {
        $fill = function (Forms\Set $set, $state) {
            $engineer = $state ? Engineer::find($state) : null;
            if (! $engineer) {
                return;
            }
            foreach (['syndicate_number', 'name', 'national_id', 'phone', 'email', 'specialization',
                'registration_number', 'office_name', 'office_registration_number'] as $field) {
                $set($field, $engineer->{$field});
            }
        };

        return [
            Forms\Components\Select::make('engineer_id')->label('اختيار من دليل المهندسين')
                ->helperText('أو أدخل البيانات يدوياً؛ سيُضاف المهندس للدليل تلقائياً.')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search) => Engineer::query()
                    ->where('membership_status', 'active')
                    ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('syndicate_number', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhere('office_name', 'like', "%{$search}%"))
                    ->limit(30)->get()->mapWithKeys(fn (Engineer $e) => [$e->getKey() => $e->display_name])->all())
                ->getOptionLabelUsing(fn ($value) => Engineer::find($value)?->display_name)
                ->live()->afterStateUpdated($fill)->dehydrated(false)->columnSpanFull(),
            Forms\Components\Section::make('المهندس المشرف')->schema([
                Forms\Components\TextInput::make('syndicate_number')->label('رقم عضوية النقابة')->required()->maxLength(50),
                Forms\Components\TextInput::make('name')->label('اسم المهندس')->required()->maxLength(255),
                Forms\Components\TextInput::make('national_id')->label('الرقم القومي')->length(14)->regex('/^\d{14}$/'),
                Forms\Components\TextInput::make('phone')->label('الهاتف')->tel()->maxLength(30),
                Forms\Components\TextInput::make('email')->label('البريد الإلكتروني')->email()->maxLength(255),
                Forms\Components\TextInput::make('specialization')->label('الشعبة / التخصص')->maxLength(100),
                Forms\Components\TextInput::make('registration_number')->label('رقم السجل الهندسي')->maxLength(50),
            ])->columns(2),
            Forms\Components\Section::make('المكتب الهندسي (اختياري)')->schema([
                Forms\Components\TextInput::make('office_name')->label('اسم المكتب')->maxLength(255),
                Forms\Components\TextInput::make('office_registration_number')->label('رقم قيد المكتب')->maxLength(50),
            ])->columns(2)->collapsible(),
            Forms\Components\Textarea::make('notes')->label('ملاحظات على الشهادة')->rows(2)->maxLength(2000)->columnSpanFull(),
        ];
    }

    // ───────────── مشترك ─────────────

    public static function printCertificate(string $name = 'print', ?\Closure $resolve = null): Action
    {
        $resolve ??= fn ($record) => $record instanceof Certificate ? $record : null;

        return Action::make($name)
            ->label('طباعة / PDF')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->visible(fn ($record) => (bool) $resolve($record))
            ->url(fn ($record) => $resolve($record)?->printUrl(), shouldOpenInNewTab: true);
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function guard(Action $action, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (SyndicateException $e) {
            Notification::make()->danger()->title('تعذر تنفيذ العملية')->body($e->getMessage())->persistent()->send();
            $action->halt();
        }

        return null;
    }
}
