<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\RemovalOrderResource\Pages;
use App\Models\RemovalOrder;
use App\Models\GisMarkaz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use App\Exports\RemovalOrdersExport;
use Maatwebsite\Excel\Facades\Excel;

class RemovalOrderResource extends Resource
{
    protected static ?string $model = RemovalOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?string $navigationLabel = 'كل القرارات';
    protected static ?string $modelLabel = 'قرار إزالة';
    protected static ?string $pluralModelLabel = 'قرارات الإزالة';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAnyRole([
            'super_admin',
            'Admin',
            'مدير المركز',
            'مدير الادارة الهندسية',
            'مهندس التنظيم',
            'مدير التنظيم',
            'رؤوساء الاقسام',
            'النائب المختص بمتابعة المنظومة طبقا لكشف الحضور',
            'رئيس المركز والمدينة',

        ]);
    }

    // الأدوار الجديدة "عرض فقط": تشوف القرارات والحالات بدون تعديل/حذف/طباعة
    private static function isViewOnly(): bool
    {
        return auth()->user()->hasAnyRole([
            'النائب المختص بمتابعة المنظومة طبقا لكشف الحضور',
            'رئيس المركز والمدينة',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('نوع المخالفة')
                    ->schema([
                        Forms\Components\Section::make('ملاحظات مراجعة أخصائي النظم')
                            ->description('يرجى الالتزام بالتعديلات المطلوبة أدناه')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->collapsible()
                            ->aside() // يجعله يظهر بشكل جانبي أو مميز
                            ->schema([
                                Forms\Components\Placeholder::make('review_notes')
                                    ->label('الملاحظات الحالية:')
                                    ->content(fn($record) => $record?->review_notes),
                            ])
                            ->visible(fn($record) => !empty($record?->review_notes)) // يظهر فقط لو فيه ملاحظات
                            ->columnSpanFull(),
                        Forms\Components\Select::make('violation_type')
                            ->label('نوع المخالفة')
                            ->options([
                                'new_violation' => 'مخالفة بناء بدون ترخيص',
                                'licensed_violation' => 'مخالفة شروط ترخيص',
                            ])->required()->live(),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('license_number')->label('رقم الترخيص')->visible(fn(Get $get) => $get('violation_type') === 'licensed_violation'),
                            Forms\Components\DatePicker::make('license_date')->label('تاريخ الترخيص')->visible(fn(Get $get) => $get('violation_type') === 'licensed_violation'),
                        ]),
                    ]),
                Forms\Components\Section::make('الموقع والمالك')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('center')->label('المركز')->options(GisMarkaz::cachedOptions())->required(),
                            Forms\Components\TextInput::make('local_unit')->label('الوحدة المحلية')->required(),
                            Forms\Components\TextInput::make('street')->label('الشارع')->required(),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('owner_name')->label('اسم المالك')->required(),
                            Forms\Components\TextInput::make('owner_national_id')->label('الرقم القومي')->length(14),
                            Forms\Components\TextInput::make('owner_governorate')->label('المحافظة')->default('كفر الشيخ'),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('engineer_name')->label('المهندس المسؤول'),
                            Forms\Components\TextInput::make('engineer_national_id')->label('الرقم القومي للمهندس'),
                            Forms\Components\TextInput::make('contractor_name')->label('اسم المقاول'),
                        ]),
                        Forms\Components\TextInput::make('contractor_national_id')->label('الرقم القومي للمقاول'),
                    ]),
                Forms\Components\Section::make('تفاصيل المخالفة')
                    ->schema([
                        Forms\Components\RichEditor::make('violation_works')->label('وصف الأعمال المخالفة')->required(),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('violation_plot')->label('رقم القطعة'),
                            Forms\Components\TextInput::make('violation_cost')->label('التكلفة المقدرة')->numeric(),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('violation_report_number')->label('رقم محضر المخالفة'),
                            Forms\Components\DatePicker::make('report_date')->label('تاريخ المحضر'),
                        ]),
                    ]),
                Forms\Components\Section::make('الحدود والأبعاد الهندسية')
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
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('area')->label('المساحة')->numeric()->suffix(' م²'),
                            Forms\Components\Textarea::make('engineer_description')->label('مراحعة المهندس للتوصيف')->helperText('يرجي ادخال وصف الأعمال المخالفة مضاف اليه التكلفه')->required(),
                        ]),
                    ]),
                Forms\Components\Section::make('القرارات والمرفقات')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('stop_order_number')->label('رقم قرار الإيقاف'),
                            Forms\Components\DatePicker::make('stop_order_date')->label('تاريخ قرار الإيقاف'),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('announcement_date')->label('تاريخ الإعلان'),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\FileUpload::make('sketch_file')->label('ملف الكروكي'),
                            Forms\Components\FileUpload::make('photo_file')->label('صورة المخالفة')->image(),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('owner_center')->label('مركز المالك'),
                            Forms\Components\TextInput::make('owner_unit')->label('الوحدة المحلية للمالك'),
                            Forms\Components\TextInput::make('owner_street')->label('شارع المالك'),
                        ]),
                        Forms\Components\TextInput::make('owner_district')->label('حي المالك'),
                        Forms\Components\TextInput::make('district')->label('الحي'),
                        Forms\Components\Textarea::make('licensed_works')->label('الأعمال المرخصة'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('م'),
                Tables\Columns\TextColumn::make('stop_order_number')->label('رقم القرار')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('owner_name')->label('اسم المالك')->limit(20)->searchable()->sortable(),
                Tables\Columns\TextColumn::make('center')->label('المركز')->badge()->color('info'),
                Tables\Columns\TextColumn::make('violation_type')->label('النوع')->badge()
                    ->formatStateUsing(fn($state) => $state === 'new_violation' ? 'بدون ترخيص' : 'مخالفة ترخيص')
                    ->color(fn($state) => $state === 'new_violation' ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('stage')->label('الحالة')
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
                        RemovalOrder::STAGE_GIS_PENDING => 'indigo',
                        RemovalOrder::STAGE_VISA_PENDING => 'indigo',
                        RemovalOrder::STAGE_COMPLETED => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('systemsSpecialist.name')
                    ->label('أخصائي النظم')
                    ->default('—')
                    ->searchable()
                    ->placeholder('غير معيّن')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Action::make('print_governor_visa')
                    ->label('طباعة تصديق المحافظ')
                    ->icon('heroicon-o-document-check')
                    ->color('info')
                    ->url(fn(RemovalOrder $record) => $record->visa_file ? \Illuminate\Support\Facades\Storage::url($record->visa_file) : null)
                    ->openUrlInNewTab()
                    ->visible(
                        fn(RemovalOrder $record) =>
                        auth()->user()->hasRole('رؤوساء الاقسام') && !empty($record->visa_file)
                    ),

                Action::make('approve_local_unit_2')
                    ->label(' التوجية للأعتماد   ')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('التوجية للأعتماد  ')
                    ->modalDescription('بمجرد التأكيد، سيتم تحويل القرار إلى مكتب المحافظ للتصديق النهائي.')
                    ->action(function (RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_VISA_PENDING, // تحويله لمرحلة انتظار التأشيرة
                            'status' => 'تم اعتماد الوحدة المحلية', // تحديث الحالة النصية
                        ]);

                        Notification::make()
                            ->success()
                            ->title('تم الاعتماد بنجاح')
                            ->body('القرار الآن بانتظار تصديق مكتب المحافظ.')
                            ->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        // يظهر فقط لمهندس التنظيم في مرحلة "الاعتماد من الوحدة المحلية"
                            auth()->user()->hasRole('رؤوساء الاقسام') &&
                            $record->stage === RemovalOrder::STAGE_GIS_PENDING
                    ),
                Action::make('approve_local_unit')
                    ->label(' الاعتماد من الوحدة المحلية')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الوحدة المحلية')
                    ->modalDescription('بمجرد التأكيد، سيتم تحويل القرار  للتوجية للأعتماد .')
                    ->action(function (RemovalOrder $record) {
                        $record->update([
                            'stage' => RemovalOrder::STAGE_GIS_PENDING, // تحويله لمرحلة انتظار التأشيرة
                            'status' => 'تم اعتماد الوحدة المحلية', // تحديث الحالة النصية
                        ]);

                        Notification::make()
                            ->success()
                            ->title('تم الاعتماد بنجاح')
                            ->body('القرار الآن بانتظار تصديق مكتب المحافظ.')
                            ->send();
                    })
                    ->visible(
                        fn(RemovalOrder $record) =>
                        // يظهر فقط لمهندس التنظيم في مرحلة "الاعتماد من الوحدة المحلية"
                        auth()->user()->hasRole('مهندس التنظيم') &&
                            $record->stage === RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL
                    ),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->visible(
                        fn(RemovalOrder $record) =>
                        // يظهر دائماً للآدمن
                        auth()->user()->hasAnyRole(['super_admin', 'Admin']) ||
                            // يظهر لمهندس التنظيم فقط إذا كان القرار في مرحلة "المراجعة الهندسية" (المرتجع) أو "قيد الإنشاء"
                            (auth()->user()->hasRole('مهندس التنظيم') &&
                                in_array($record->stage, [RemovalOrder::STAGE_ENGINEERING_REVIEW, RemovalOrder::STAGE_CREATED]))
                    ),
                Action::make('print_pdf_file')
                    ->label('طباعة القرار')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    // جلب رابط الملف المخزن في العمود pdf_file
                    ->url(fn(RemovalOrder $record) => $record->pdf_file ? \Illuminate\Support\Facades\Storage::url($record->pdf_file) : null)
                    ->openUrlInNewTab() // يفتح الملف في صفحة جديدة للطباعة
                    ->visible(
                        fn(RemovalOrder $record) =>
                        // يظهر لمهندس التنظيم والأدمن فقط
                        auth()->user()->hasAnyRole(['مهندس التنظيم', 'super_admin', 'Admin']) &&
                            // ويظهر فقط إذا كان هناك ملف مرفوع فعلاً
                            !empty($record->pdf_file)
                    ),
                Action::make('assign_systems_specialist')
                    ->label('تعيين محلل نظم')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    // يظهر الزر فقط لرؤوساء الأقسام
                    ->visible(fn() => auth()->user()->hasRole('رؤوساء الاقسام'))
                    ->form([
                        Forms\Components\Select::make('systems_specialist_id')
                            ->label('اختار محلل النظم')
                            ->options(User::role('محللي النظم')->pluck('name', 'id')) // تأكد أن اسم الدور في السيستم هو "محلل نظم"
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, RemovalOrder $record) {
                        $record->update([
                            'stage' => 'systems_processing', // تحويله لمرحلة "قيد المعالجة من محلل النظم"
                            'systems_specialist_id' => $data['systems_specialist_id'],
                        ]);

                        Notification::make()
                            ->title('تم تعيين محلل النظم بنجاح')
                            ->success()
                            ->send();
                    }),
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
                        fn() => auth()->user()->hasRole('رؤوساء الاقسام')
                        // fn(RemovalOrder $record) =>
                        // in_array($record->stage, [
                        //     RemovalOrder::STAGE_SYSTEMS_PROCESSING,
                        //     RemovalOrder::STAGE_PDF_READY,
                        // ])
                    ),

                Tables\Actions\DeleteAction::make(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center')->label('المركز')->options(GisMarkaz::cachedOptions()),
            ])
            ->bulkActions([
                BulkAction::make('assign_systems_specialist_bulk')
                ->label('تعيين محلل نظم للمختار')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تعيين محلل نظم للقرارات المختارة')
                ->modalDescription('سيتم تعيين محلل النظم المختار لكل القرارات المحددة وتحويلها لمرحلة المعالجة.')
                // يظهر فقط لرؤوساء الأقسام
                ->visible(fn() => auth()->user()->hasRole('رؤوساء الاقسام'))
                ->form([
                    Forms\Components\Select::make('systems_specialist_id')
                        ->label('اختر محلل النظم')
                        ->options(fn() => User::role('محللي النظم')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    $records->each(function (RemovalOrder $record) use ($data) {
                        $record->update([
                            'systems_specialist_id' => $data['systems_specialist_id'],
                            'stage' => RemovalOrder::STAGE_SYSTEMS_PROCESSING,
                        ]);
                    });

                    Notification::make()
                        ->title('تم تعيين محلل النظم لـ ' . $records->count() . ' قرار بنجاح')
                        ->success()
                        ->send();
                })
                ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('export_excel_all_data')
                        ->label('تصدير كافة بيانات المختار (Excel)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return Excel::download(
                                new RemovalOrdersExport($records),
                                'قرارات_الازالة_' . now()->format('Y-m-d_H-i') . '.xlsx'
                            );
                        }),

                    // الأكشن الجماعي لتعيين محلل النظم
                    BulkAction::make('assign_systems_specialist_bulk')
                        ->label('تعيين محلل نظم للمختار')
                        ->icon('heroicon-o-user-group')
                        ->color('success')
                        ->requiresConfirmation()
                        // يظهر فقط لرؤوساء الأقسام
                        ->visible(fn() => auth()->user()->hasRole('رؤوساء الاقسام'))
                        ->form([
                            Forms\Components\Select::make('systems_specialist_id')
                                ->label('اختر محلل النظم')
                                ->options(User::role('محللي النظم')->pluck('name', 'id')) // جلب الموظفين
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            // تحديث جميع السجلات المختارة مرة واحدة
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'systems_specialist_id' => $data['systems_specialist_id'],
                                ]);
                            });

                            Notification::make()
                                ->title('تم تعيين محلل النظم لـ ' . $records->count() . ' قرار بنجاح')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(), // إلغاء تحديد المربعات بعد الانتهاء

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->recordUrl(null)    // يمنع تحويل الصف إلى رابط
            ->recordAction(null);
    }

    // ── Authorization: bypass Shield so that canAccess() is the gate ──
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
    public static function canCreate(): bool
    {
        return static::canAccess() && !static::isViewOnly();
    }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess() && !static::isViewOnly();
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // مهندس التنظيم يشوف فقط قرارات مركزه
        if ($user->hasRole('مهندس التنظيم') && $user->center) {
            $query->where('center', $user->center);
        }

        // مدير الادارة الهندسية / مدير المتغيرات / عضو المتغيرات:
        // يشوفوا فقط قرارات مركزهم، ولو مفيش مركز مسجل للدور ده -> مفيش قرارات تظهر
        if ($user->hasAnyRole(['مدير الادارة الهندسية', 'مدير المتغيرات', 'عضو المتغيرات'])) {
            if (!$user->center) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('center', $user->center);
            }
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRemovalOrders::route('/'),
            // 'create' => Pages\CreateRemovalOrder::route('/create'),
            // 'view' => Pages\ViewRemovalOrder::route('/{record}'),
            'edit' => Pages\EditRemovalOrder::route('/{record}/edit'),
        ];
    }
}
