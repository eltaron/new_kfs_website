<?php

namespace App\Filament\Gis\Pages;

use Modules\RemovalGovernance\Models\RemovalOrder;
use App\Models\User;
use Filament\Pages\Page;
use App\Filament\Gis\Pages\SpatialSurvey;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction; // إضافة هذا
use Filament\Tables\Actions\BulkActionGroup; // إضافة هذا
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Forms;
use Illuminate\Database\Eloquent\Collection; // إضافة هذا
use App\Exports\RemovalOrdersExport; // الكلاس اللي عملناه في الخطوة السابقة
use Maatwebsite\Excel\Facades\Excel;

class WorkflowDashboard extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    // protected static ?string $navigationLabel = 'مسار اعتماد القرارات';
    protected static ?string $navigationLabel = 'التسجيل المكاني';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.workflow-dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole([
            'super_admin',
            'Admin',
            'مدير المركز',
            'مدير الادارة الهندسية',
            'مدير التنظيم',
            'فني التنظيم',
            'مدير المتغيرات',
            'عضو المتغيرات',
            'محللي النظم',
            'مكتب المحافظ',
            'مدير الوحدة الفرعية',
            'أخصائي النظم',
            'النائب المختص بمتابعة المنظومة طبقا لكشف الحضور',
            'رئيس المركز والمدينة',
        ]);
    }

    public function getTitle(): string
    {
        return 'التسجيل المكاني';
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query($this->buildQuery($user))
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('owner_name')->label('المالك')->searchable()->limit(20),
                TextColumn::make('center')->label('المركز')->searchable(),
                TextColumn::make('street')->label('الشارع')->searchable()->limit(20),
                TextColumn::make('violation_type')->label('نوع المخالفة')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'new_violation' ? 'بدون ترخيص' : 'مرخص')
                    ->color(fn($state) => $state === 'new_violation' ? 'danger' : 'warning'),
                TextColumn::make('stage')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn($state) => RemovalOrder::stages()[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        RemovalOrder::STAGE_CREATED => 'gray',
                        RemovalOrder::STAGE_ENGINEERING_REVIEW => 'warning',
                        RemovalOrder::STAGE_SPATIAL_PENDING => 'info',
                        RemovalOrder::STAGE_SPATIAL_DIMENSIONED => 'primary',
                        RemovalOrder::STAGE_PDF_READY => 'secondary',
                        RemovalOrder::STAGE_SYSTEMS_PROCESSING => 'purple',
                        RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL => 'teal',
                        RemovalOrder::STAGE_GIS_PENDING => 'purple',
                        RemovalOrder::STAGE_VISA_PENDING => 'indigo',
                        RemovalOrder::STAGE_COMPLETED => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable(),
                TextColumn::make('citizen_notification_time')
                    ->label('إخطار المواطن')
                    ->dateTime()
                    ->placeholder('لم يتم')
                    ->sortable(),
                TextColumn::make('police_notification_time')
                    ->label('إخطار الشرطة')
                    ->dateTime()
                    ->placeholder('لم يتم')
                    ->sortable(),
            ])
            ->filters([])
            ->actions(array_filter([
                Action::make('view_record')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn(RemovalOrder $record) => url('/gis/removal-orders/' . $record->id))
                    ->openUrlInNewTab(),
                Action::make('edit_record')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->color('gray')
                    ->url(fn(RemovalOrder $record) => \App\Filament\Gis\Resources\RemovalOrderResource\Pages\EditRemovalOrder::getUrl([$record->id]))
                    ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'مدير المركز', 'مهندس التنظيم'])),

                // --- الزر الجديد: إرجاع للمهندس ---
                Action::make('return_to_engineer')
                    ->label('إرجاع للمهندس')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إرجاع القرار للمراجعة الهندسية')
                    ->modalDescription('سيتم إعادة القرار إلى مرحلة "المراجعة الهندسية" ليتمكن المهندس من تعديله.')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('سبب الإرجاع / ملاحظات التعديل')
                            ->placeholder('يرجى ذكر الأسباب التي تستوجب التعديل...')
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_ENGINEERING_REVIEW, // إرجاعه لمرحلة المهندس
                            'review_notes' => $data['review_notes'],           // حفظ الملاحظات
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('تم إرجاع القرار للمهندس بنجاح')
                            ->body('القرار الآن في مرحلة المراجعة الهندسية.')
                            ->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        auth()->user()->hasAnyRole(['أخصائي النظم', 'محللي النظم']) &&
                            $record->stage === RemovalOrder::STAGE_SPATIAL_DIMENSIONED
                    ),
                // محضر عدم تنفيذ قرار الإزالة (فني التنظيم - بعد اعتماد المحافظ)
                Action::make('failure_to_execute_the_demolition_order')
                    ->label('محضر عدم تنفيذ قرار الإزالة')
                    ->icon('heroicon-o-document-minus')
                    ->color('info')
                    ->modalHeading('رفع محضر عدم تنفيذ قرار الإزالة')
                    ->form([
                        Forms\Components\FileUpload::make('failure_to_execute_the_demolition_order_file')
                            ->label('ملف محضر عدم التنفيذ')
                            ->directory('removal-orders/legal')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'failure_to_execute_the_demolition_order_file' => $data['failure_to_execute_the_demolition_order_file'],
                        ]);
                        Notification::make()->success()->title('تم رفع محضر عدم التنفيذ بنجاح')->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        auth()->user()->hasRole('فني التنظيم') &&
                            $record->stage === RemovalOrder::STAGE_COMPLETED
                    ),
                // إخطار الإيرادات بتحصيل 1% (فني التنظيم - بعد اعتماد المحافظ)
                Action::make('notification_to_the_revenue_department')
                    ->label('إخطار الإيرادات بتحصيل 1%')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->modalHeading('رفع إخطار الإيرادات بتحصيل 1%')
                    ->form([
                        Forms\Components\FileUpload::make('notification_to_the_revenue_department_file')
                            ->label('ملف إخطار الإيرادات')
                            ->directory('removal-orders/legal')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'notification_to_the_revenue_department_file' => $data['notification_to_the_revenue_department_file'],
                        ]);
                        Notification::make()->success()->title('تم رفع إخطار الإيرادات بنجاح')->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        auth()->user()->hasRole('فني التنظيم') &&
                            $record->stage === RemovalOrder::STAGE_COMPLETED
                    ),
                // إخطار المواطن (فني التنظيم - بعد اعتماد المحافظ)
                Action::make('notify_citizen')
                    ->label('إخطار المواطن')
                    ->icon('heroicon-o-envelope-open')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('تسجيل إخطار المواطن')
                    ->modalDescription('اختر وقت الإخطار وارفع ملف الإخطار المرفق.')
                    ->form([
                        Forms\Components\DateTimePicker::make('citizen_notification_time')
                            ->label('وقت إخطار المواطن')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Forms\Components\FileUpload::make('citizen_notification_file')
                            ->label('ملف الإخطار')
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'citizen_notification_time' => $data['citizen_notification_time'],
                            'citizen_notification_file' => $data['citizen_notification_file'],
                        ]);
                        Notification::make()
                            ->success()
                            ->title('تم تسجيل إخطار المواطن بنجاح')
                            ->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        auth()->user()->hasRole('فني التنظيم') &&
                            $record->stage === RemovalOrder::STAGE_COMPLETED &&
                            !$record->citizen_notification_time
                    ),
                // إخطار الشرطة (فني التنظيم - بعد اعتماد المحافظ)
                Action::make('notify_police')
                    ->label('إخطار الشرطة')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('تسجيل إخطار الشرطة')
                    ->modalDescription('اختر وقت الإخطار وارفع ملف الإخطار المرفق.')
                    ->form([
                        Forms\Components\DateTimePicker::make('police_notification_time')
                            ->label('وقت إخطار الشرطة')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Forms\Components\FileUpload::make('police_notification_file')
                            ->label('ملف الإخطار')
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'police_notification_time' => $data['police_notification_time'],
                            'police_notification_file' => $data['police_notification_file'],
                        ]);
                        Notification::make()
                            ->success()
                            ->title('تم تسجيل إخطار الشرطة بنجاح')
                            ->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        auth()->user()->hasRole('فني التنظيم') &&
                            $record->stage === RemovalOrder::STAGE_COMPLETED &&
                            !$record->police_notification_time
                    ),

                ...$this->getWorkflowActions($user),
            ]))
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('export_excel')
                        ->label('تصدير المختار إلى إكسل')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        // يظهر لأخصائي النظم، محللي النظم، والأدمن
                        ->visible(fn() => auth()->user()->hasAnyRole(['أخصائي النظم', 'محللي النظم', 'super_admin', 'Admin']))
                        ->action(function (Collection $records) {
                            return Excel::download(
                                new RemovalOrdersExport($records),
                                'بيانات_المخالفات_' . now()->format('Y-m-d') . '.xlsx'
                            );
                        })->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasAnyRole(['super_admin', 'Admin'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function buildQuery(User $user): Builder
    {
        // الأدوار الجديدة "عرض فقط": متابعة كل القرارات والحالات
        if ($user->hasAnyRole(['النائب المختص بمتابعة المنظومة طبقا لكشف الحضور', 'رئيس المركز والمدينة'])) {
            return RemovalOrder::query();
        }

        if ($user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام'])) {
            return RemovalOrder::query();
        }

        // مدير الادارة الهندسية / مدير المتغيرات / عضو المتغيرات: قرارات مركزهم فقط،
        // ولو مفيش مركز مسجل للمستخدم -> مفيش قرارات تظهر
        if ($user->hasAnyRole(['مدير الادارة الهندسية', 'مدير المتغيرات', 'عضو المتغيرات'])) {
            return $this->applyCenterScope(RemovalOrder::query(), $user);
        }

        if ($user->hasRole('فني التنظيم')) {
            return RemovalOrder::where('stage', RemovalOrder::STAGE_CREATED)
                ->orWhere('created_by', $user->id)
                ->orWhere('stage', RemovalOrder::STAGE_COMPLETED);
        }

        if ($user->hasRole('مهندس التنظيم')) {
            return RemovalOrder::query();
        }

        if ($user->hasRole('محللي النظم')) {
            return RemovalOrder::whereIn('stage', [
                RemovalOrder::STAGE_SPATIAL_DIMENSIONED,
                RemovalOrder::STAGE_SYSTEMS_PROCESSING,
            ])->where('systems_specialist_id', $user->id);
        }
        if ($user->hasRole('أخصائي النظم')) {
            return RemovalOrder::whereIn('stage', [
                RemovalOrder::STAGE_SPATIAL_DIMENSIONED,
                RemovalOrder::STAGE_SYSTEMS_PROCESSING,
            ])->where('systems_specialist_id', $user->id);
        }

        if ($user->hasRole('مكتب المحافظ')) {
            return RemovalOrder::where('stage', RemovalOrder::STAGE_VISA_PENDING);
        }

        if ($user->hasRole('مدير الوحدة الفرعية')) {
            return RemovalOrder::query();
        }

        return RemovalOrder::where('assigned_to', $user->id);
    }

    private function applyCenterScope(Builder $query, User $user): Builder
    {
        return $user->center
            ? $query->where('center', $user->center)
            : $query->whereRaw('1 = 0');
    }

    private function getWorkflowActions(User $user): array
    {
        // 1. فني التنظيم
        if ($user->hasRole('فني التنظيم')) {
            return [
                Action::make('start_engineering')
                    ->label('بدء المراجعة الهندسية')
                    ->action(function (RemovalOrder $record) use ($user) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_ENGINEERING_REVIEW,
                            'engineering_engineer_id' => $record->engineering_engineer_id ?? $user->id,
                        ]);
                        Notification::make()->success()->title('تم بدء المراجعة الهندسية')->send();
                    })
                    ->color('warning')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_CREATED),
            ];
        }

        // 2. مهندس التنظيم
        if ($user->hasRole('مهندس التنظيم')) {
            return [
                Action::make('to_spatial')
                    ->label('تحويل للكشف المساحي')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('spatial_manager_id')
                            ->label('مدير المتغيرات')
                            ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'مدير المتغيرات'))->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Section::make('الحدود والأبعاد')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('border_east')->label('الحد الشرقي'),
                                    Forms\Components\TextInput::make('dimension_east')->label('البعد الشرقي'),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('border_west')->label('الحد الغربي'),
                                    Forms\Components\TextInput::make('dimension_west')->label('البعد الغربي'),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('border_north')->label('الحد الشمالي'),
                                    Forms\Components\TextInput::make('dimension_north')->label('البعد الشمالي'),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('border_south')->label('الحد الجنوبي'),
                                    Forms\Components\TextInput::make('dimension_south')->label('البعد الجنوبي'),
                                ]),
                            ]),
                        Forms\Components\Section::make('بيانات المهندس')
                            ->schema([
                                Forms\Components\TextInput::make('area')
                                    ->label('المساحة')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('violation_cost')
                                    ->label('التكاليف')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\Textarea::make('engineer_description')
                                    ->label('توصيف المهندس')
                                    ->required(),
                            ]),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update(array_merge($data, ['stage' => RemovalOrder::STAGE_SPATIAL_PENDING]));
                        Notification::make()->success()->title('تم التحويل للكشف المساحي')->send();
                    })

                    ->color('info')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_ENGINEERING_REVIEW),

                Action::make('approve_local_unit_from_engineer')
                    ->label('إتمام إجراءات الوحدة المحلية')
                    ->icon('heroicon-o-check-badge')
                    ->color('teal')
                    ->requiresConfirmation()
                    ->modalHeading('تصديق الوحدة المحلية')
                    ->modalDescription('بعد الانتهاء من التحليل المكاني وصدر القرار، يتم اعتماد الوحدة المحلية وتحويل القرار إلى السلطة المختصة للتصديق النهائي.')
                    ->action(function (RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_VISA_PENDING,
                        ]);
                        Notification::make()
                            ->success()
                            ->title('تم اعتماد الوحدة المحلية بنجاح')
                            ->body('القرار الآن بانتظار تصديق السلطة المختصة.')
                            ->send();
                    })
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL),
            ];
        }

        // 3. مدير المتغيرات (تعيين العضو)
        if ($user->hasRole('مدير المتغيرات')) {
            return [
                Action::make('assign_spatial')
                    ->label('تعيين عضو متغيرات')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('spatial_member_id')
                            ->label('عضو المتغيرات')
                            ->relationship(
                                name: 'spatialMember',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'عضو المتغيرات'))
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'spatial_member_id' => $data['spatial_member_id'],
                            'stage' => RemovalOrder::STAGE_SPATIAL_DIMENSIONED,
                        ]);

                        Notification::make()->success()->title('تم تعيين عضو المتغيرات بنجاح')->send();
                    })
                    ->color('primary')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_SPATIAL_PENDING),
            ];
        }

        // 4. عضو المتغيرات (التسجيل المكاني)
        if ($user->hasRole('عضو المتغيرات')) {
            return [
                Action::make('add_spatial')
                    ->label('التسجيل المكاني الميداني')
                    ->icon('heroicon-o-map')
                    ->url(fn(RemovalOrder $record) => SpatialSurvey::getUrl(['id' => $record->id]))
                    ->color('success')
                    ->openUrlInNewTab()
                    ->visible(
                        fn(RemovalOrder $record) =>
                        $record->stage === RemovalOrder::STAGE_SPATIAL_DIMENSIONED &&
                            $record->spatial_member_id === auth()->id()
                    ),
            ];
        }

        // 5. رؤساء الأقسام
        if ($user->hasRole('رؤوساء الاقسام')) {
            return [
                Action::make('assign_specialist')
                    ->label('تعيين أخصائي نظم')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('systems_specialist_id')
                            ->label('محللي النظم')
                            ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'محللي النظم'))->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'systems_specialist_id' => $data['systems_specialist_id'],
                            'stage' => RemovalOrder::STAGE_SYSTEMS_PROCESSING,
                        ]);
                        Notification::make()->success()->title('تم تعيين محلل نظم')->send();
                    })
                    ->color('primary')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_PDF_READY),

                Action::make('return_to_spatial_member')
                    ->label('إرجاع لفني المتغيرات')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إرجاع القرار لفني المتغيرات')
                    ->modalDescription('سيتم إرجاع القرار إلى فني المتغيرات لإعادة الرسم والتسجيل المكاني مرة أخرى.')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('سبب الإرجاع / ملاحظات إعادة الرسم')
                            ->placeholder('يرجى ذكر الأسباب التي تستوجب إعادة الرسم...')
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_SPATIAL_DIMENSIONED,
                            'review_notes' => $data['review_notes'],
                        ]);
                        Notification::make()
                            ->warning()
                            ->title('تم إرجاع القرار لفني المتغيرات')
                            ->body('القرار الآن في مرحلة التسجيل المكاني لإعادة الرسم.')
                            ->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        in_array($record->stage, [
                            RemovalOrder::STAGE_SPATIAL_DIMENSIONED,
                            RemovalOrder::STAGE_PDF_READY,
                        ])
                    ),

                Action::make('approve_main_center_review')
                    ->label('اعتماد مراجعة المركز الرئيسي')
                    ->icon('heroicon-o-building-office-2')
                    ->color('purple')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد المركز الرئيسي')
                    ->modalDescription('اعتماد مراجعة المركز الرئيسي وتوجيه القرار إلى محلل النظم لإجراء التحليل المكاني.')
                    ->action(function (RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_SYSTEMS_PROCESSING,
                        ]);
                        Notification::make()
                            ->success()
                            ->title('تم اعتماد مراجعة المركز الرئيسي')
                            ->body('القرار الآن في مرحلة التحليل المكاني.')
                            ->send();
                    })
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_PDF_READY),
            ];
        }

        // 6. محلل نظم
        if ($user->hasRole('محللي النظم')) {
            return [
                Action::make('upload_pdf')
                    ->label('رفع القرار')
                    ->form([
                        Forms\Components\FileUpload::make('pdf_file')
                            ->label('ملف القرار PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_PDF_READY,
                            'pdf_file' => $data['pdf_file'],
                        ]);
                        Notification::make()->success()->title('تم رفع القرار')->send();
                    })
                    ->color('warning')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_SPATIAL_DIMENSIONED),
                Action::make('upload_pdf')
                    ->label('رفع القرار')
                    ->form([
                        Forms\Components\FileUpload::make('pdf_file')
                            ->label('ملف القرار PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL,
                            'pdf_file' => $data['pdf_file'],
                        ]);
                        Notification::make()->success()->title('تم رفع القرار')->send();
                    })
                    ->color('warning')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_SYSTEMS_PROCESSING),
            ];
        }
        if ($user->hasRole('أخصائي النظم')) {
            return [
                Action::make('upload_pdf')
                    ->label('رفع القرار')
                    ->form([
                        Forms\Components\FileUpload::make('pdf_file')
                            ->label('ملف القرار PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_PDF_READY,
                            'pdf_file' => $data['pdf_file'],
                        ]);
                        Notification::make()->success()->title('تم رفع القرار')->send();
                    })
                    ->color('warning')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_SPATIAL_DIMENSIONED),

                Action::make('upload_pdf')
                    ->label('رفع القرار')
                    ->form([
                        Forms\Components\FileUpload::make('pdf_file')
                            ->label('ملف القرار PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL,
                            'pdf_file' => $data['pdf_file'],
                        ]);
                        Notification::make()->success()->title('تم رفع القرار')->send();
                    })
                    ->color('warning')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_SYSTEMS_PROCESSING),
            ];
        }

        // 7. مكتب المحافظ
        if ($user->hasRole('مكتب المحافظ')) {
            return [
                Action::make('upload_visa')
                    ->label('التصديق النهائي (قرار المحافظ)')
                    ->icon('heroicon-o-check-badge')
                    ->form([
                        // إضافة حقول بيانات القرار
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('governor_decision_number')
                                ->label('رقم قرار اعتماد المحافظ')
                                ->placeholder('أدخل رقم القرار هنا...')
                                ->required(),
                            Forms\Components\DatePicker::make('governor_decision_date')
                                ->label('تاريخ قرار الاعتماد')
                                ->default(now())
                                ->required(),
                        ]),
                        // ملف التأشيرة
                        Forms\Components\FileUpload::make('visa_file')
                            ->label('ملف التصديق النهائي PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_COMPLETED,
                            'visa_file' => $data['visa_file'],
                            'governor_decision_number' => $data['governor_decision_number'], // حفظ الرقم
                            'governor_decision_date' => $data['governor_decision_date'],   // حفظ التاريخ
                            'status' => 'تم التصديق النهائي والاعتماد', // تحديث الحالة
                        ]);

                        Notification::make()
                            ->success()
                            ->title('تم اعتماد القرار وإكمال الدورة بنجاح')
                            ->send();
                    })
                    ->color('success')
                    ->visible(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_VISA_PENDING),
            ];
        }
        // 8. مدير الإدارة الهندسية
        if ($user->hasRole('مدير الادارة الهندسية')) {
            return [
                Action::make('engineering_action')
                    ->label(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_PDF_READY ? 'استكمال مرحلة اعتماد المركز الرئيسي' : 'تعيين مهندس/فني')
                    ->color(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_PDF_READY ? 'success' : 'primary')
                    ->form(fn(RemovalOrder $record) => $record->stage === RemovalOrder::STAGE_PDF_READY ? [] : [
                        Forms\Components\Select::make('engineering_engineer_id')
                            ->label('مهندس التنظيم')
                            ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'مهندس التنظيم'))->pluck('name', 'id')),
                        Forms\Components\Select::make('assigned_to')
                            ->label('فني التنظيم')
                            ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'فني التنظيم'))->pluck('name', 'id')),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        if ($record->stage === RemovalOrder::STAGE_PDF_READY) {
                            $record->update(['stage' => RemovalOrder::STAGE_SYSTEMS_PROCESSING]);
                        } else {
                            $record->update($data);
                        }
                        Notification::make()->success()->title('تم الإجراء بنجاح')->send();
                    })
                    ->visible(fn(RemovalOrder $record) => in_array($record->stage, [RemovalOrder::STAGE_CREATED, RemovalOrder::STAGE_PDF_READY])),
            ];
        }

        return [];
    }
}
