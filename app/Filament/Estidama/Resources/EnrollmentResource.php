<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;
use App\Filament\Estidama\Resources\EnrollmentResource\Pages;
use Modules\Estidama\Models\Badge;
use Modules\Estidama\Models\Enrollment;
use App\Services\Lms\CertificateService;
use App\Services\Lms\LmsAccess;
use App\Services\Lms\ProgressService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EnrollmentResource extends Resource
{
    use AuthorizesEstidamaStaff;

    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'إدارة التدريب';
    protected static ?string $modelLabel = 'تسجيل';
    protected static ?string $pluralModelLabel = 'التسجيلات وتقدم المتدربين';
    protected static ?int $navigationSort = 2;

    protected static function trainerCan(string $action, ?Model $record, Authenticatable $user): bool
    {
        if ($action === 'viewAny') {
            return true;
        }

        return in_array($action, ['view', 'update'], true) && $record instanceof Enrollment
            && LmsAccess::canManageProgram($user, $record->training_program_id);
    }

    public static function canCreate(): bool
    {
        return false; // enrollments come from applications, direct enrollment or payment
    }

    public static function getEloquentQuery(): Builder
    {
        return LmsAccess::scopeToTrainer(parent::getEloquentQuery(), auth()->user());
    }

    public static function form(Form $form): Form
    {
        $isManager = fn (): bool => LmsAccess::isManager(auth()->user());

        return $form
            ->schema([
                Forms\Components\Section::make('حالة التسجيل')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة التسجيل')
                            ->options(Enrollment::STATUSES)
                            ->required()
                            ->native(false)
                            ->live(),
                        Forms\Components\Select::make('payment_status')
                            ->label('حالة السداد')
                            ->options(['free' => 'مجاني / معفى', 'unpaid' => 'بانتظار السداد'])
                            ->helperText('السداد يتم عبر بوابة الدفع فقط؛ يمكن هنا إعفاء المتدرب من الرسوم.')
                            ->native(false)
                            ->disabled(fn (?Enrollment $record) => $record?->payment_status === 'paid')
                            ->dehydrated(fn (?Enrollment $record) => $record?->payment_status !== 'paid')
                            ->visible($isManager),
                        Forms\Components\FileUpload::make('certificate_path')
                            ->label('شهادة ورقية ممسوحة (اختياري)')
                            ->helperText('تُصدر الشهادة الإلكترونية تلقائيًا؛ استخدم هذا الحقل للشهادات الحضورية فقط. PDF أو صورة حتى 5 ميجابايت.')
                            ->disk('local')
                            ->visibility('private')
                            ->directory('lms/enrollment-certificates')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->rules(['extensions:pdf,jpg,jpeg,png'])
                            ->maxSize(5120)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'completed')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات (سبب الرفض أو غيرها)')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('المتدرب والبرنامج')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')->label('المتدرب'),
                        Infolists\Components\TextEntry::make('user.national_id')->label('الرقم القومي'),
                        Infolists\Components\TextEntry::make('trainingProgram.title')->label('البرنامج'),
                        Infolists\Components\TextEntry::make('status')->label('الحالة')->badge()
                            ->formatStateUsing(fn ($state) => Enrollment::STATUSES[$state] ?? $state),
                        Infolists\Components\TextEntry::make('payment_status')->label('السداد')->badge()
                            ->formatStateUsing(fn ($state) => Enrollment::PAYMENT_STATUSES[$state ?? 'free'] ?? $state),
                        Infolists\Components\TextEntry::make('enrolled_at')->label('تاريخ التسجيل')->dateTime('Y-m-d H:i'),
                        Infolists\Components\TextEntry::make('progress_percent')->label('نسبة التقدم')->suffix('%'),
                        Infolists\Components\TextEntry::make('last_activity_at')->label('آخر نشاط')->since()->placeholder('—'),
                        Infolists\Components\TextEntry::make('certificate.serial')->label('رقم الشهادة')->placeholder('لم تصدر بعد')
                            ->url(fn (Enrollment $record) => $record->certificate ? route('lms.certificates.show', $record->certificate->serial) : null, true),
                    ])->columns(3),

                Infolists\Components\Section::make('تقدم الدروس')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('lessonProgress')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('lesson.title')->label('الدرس'),
                                Infolists\Components\TextEntry::make('started_at')->label('بدأ')->dateTime('Y-m-d H:i')->placeholder('—'),
                                Infolists\Components\TextEntry::make('completed_at')->label('أتمّ')->dateTime('Y-m-d H:i')->placeholder('لم يكتمل'),
                            ])
                            ->columns(3)
                            ->placeholder('لم يبدأ المتدرب أي درس بعد.'),
                    ])->collapsible(),

                Infolists\Components\Section::make('محاولات الاختبارات')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('attempts')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('exam.title')->label('الاختبار'),
                                Infolists\Components\TextEntry::make('attempt_number')->label('المحاولة'),
                                Infolists\Components\TextEntry::make('score')->label('الدرجة')->suffix('%'),
                                Infolists\Components\IconEntry::make('passed')->label('ناجح')->boolean(),
                            ])
                            ->columns(4)
                            ->placeholder('لا توجد محاولات.'),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'trainingProgram', 'certificate']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('اسم المتدرب')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.national_id')->label('الرقم القومي')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('trainingProgram.title')->label('البرنامج التدريبي')->searchable()->sortable()->limit(30),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => Enrollment::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'completed' => 'success', default => 'gray' }),
                Tables\Columns\TextColumn::make('progress_percent')->label('التقدم')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->label('السداد')->badge()
                    ->formatStateUsing(fn ($state) => Enrollment::PAYMENT_STATUSES[$state ?? 'free'] ?? $state)
                    ->color(fn ($state) => match ($state) { 'unpaid' => 'warning', 'paid' => 'success', default => 'gray' }),
                Tables\Columns\IconColumn::make('has_certificate')->label('شهادة')->boolean()
                    ->state(fn (Enrollment $record) => $record->certificate !== null),
                Tables\Columns\TextColumn::make('enrolled_at')->label('تاريخ التسجيل')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(Enrollment::STATUSES)->label('الحالة'),
                Tables\Filters\SelectFilter::make('payment_status')->options(Enrollment::PAYMENT_STATUSES)->label('السداد'),
                Tables\Filters\SelectFilter::make('training_program_id')
                    ->relationship('trainingProgram', 'title', fn (Builder $query) => LmsAccess::isTrainerOnly(auth()->user()) ? $query->where('instructor_id', auth()->id()) : $query)
                    ->searchable()->preload()->label('البرنامج'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->label('تغيير الحالة'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('موافقة')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Enrollment $record): bool => $record->status === 'pending')
                        ->action(fn (Enrollment $record) => $record->update(['status' => 'approved'])),
                    Tables\Actions\Action::make('recalculate')
                        ->label('إعادة حساب التقدم')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (Enrollment $record) => in_array($record->status, ['approved', 'completed'], true))
                        ->action(function (Enrollment $record) {
                            app(ProgressService::class)->refresh($record);
                            Notification::make()->title('تم تحديث التقدم')->success()->send();
                        }),
                    Tables\Actions\Action::make('issueCertificate')
                        ->label('إتمام وإصدار الشهادة')
                        ->icon('heroicon-o-document-check')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalDescription('سيتم اعتبار البرنامج مكتملاً للمتدرب وإصدار شهادة إلكترونية برقم تسلسلي ورمز QR للتحقق.')
                        ->visible(fn (Enrollment $record) => $record->certificate === null && in_array($record->status, ['approved', 'completed'], true))
                        ->action(function (Enrollment $record) {
                            $record->trainingProgram->certificate_enabled
                                ? app(CertificateService::class)->complete($record)
                                : $record->update(['status' => 'completed', 'completed_at' => now()]);
                            if (! $record->trainingProgram->certificate_enabled) {
                                Notification::make()->title('تم الإتمام (الشهادات الإلكترونية معطلة لهذا البرنامج)')->warning()->send();

                                return;
                            }
                            Notification::make()->title('تم إصدار الشهادة')->success()->send();
                        }),
                    Tables\Actions\Action::make('awardBadge')
                        ->label('منح شارة')
                        ->icon('heroicon-o-sparkles')
                        ->form([
                            Forms\Components\Select::make('badge_id')
                                ->label('الشارة')
                                ->options(fn () => Badge::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (Enrollment $record, array $data) {
                            $user = $record->user;
                            if ($user && ! $user->badges()->whereKey($data['badge_id'])->exists()) {
                                $user->badges()->attach($data['badge_id'], ['awarded_at' => now()]);
                            }
                            Notification::make()->title('تم منح الشارة')->success()->send();
                        }),
                    Tables\Actions\Action::make('certificateFile')
                        ->label('الشهادة الورقية')
                        ->icon('heroicon-o-paper-clip')
                        ->visible(fn (Enrollment $record) => filled($record->certificate_path))
                        ->url(fn (Enrollment $record) => route('lms.enrollment.certificate-file', $record), true),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('موافقة على المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each(fn (Enrollment $e) => $e->status === 'pending' ? $e->update(['status' => 'approved']) : null))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('enrolled_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'user.national_id', 'trainingProgram.title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'تسجيل: ' . ($record->user?->name ?? '—');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['للبرنامج' => $record->trainingProgram?->title ?? '—'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'view' => Pages\ViewEnrollment::route('/{record}'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
