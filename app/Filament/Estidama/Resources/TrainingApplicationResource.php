<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;
use App\Filament\Estidama\Resources\TrainingApplicationResource\Pages;
use Modules\Estidama\Models\TrainingApplication;
use App\Services\Lms\LmsAccess;
use App\Support\SecureUploads;
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

class TrainingApplicationResource extends Resource
{
    use AuthorizesEstidamaStaff;

    protected static ?string $model = TrainingApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationGroup = 'إدارة التدريب';
    protected static ?string $modelLabel = 'طلب التحاق';
    protected static ?string $pluralModelLabel = 'طلبات الالتحاق';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false; // Applications come from the public site
    }

    protected static function trainerCan(string $action, ?Model $record, Authenticatable $user): bool
    {
        if ($action === 'viewAny') {
            return true;
        }

        return $action === 'view' && $record instanceof TrainingApplication
            && LmsAccess::canManageProgram($user, $record->training_program_id);
    }

    public static function getEloquentQuery(): Builder
    {
        return LmsAccess::scopeToTrainer(parent::getEloquentQuery(), auth()->user());
    }

    /** Approve = open the learner's enrollment (see TrainingApplication::booted). */
    public static function approve(TrainingApplication $record): void
    {
        $record->forceFill([
            'status' => 'approved',
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ])->save();

        $record->enrollment()->exists()
            ? Notification::make()->title('تم قبول الطلب وإنشاء تسجيل المتدرب')->success()->send()
            : Notification::make()->title('تم قبول الطلب')
                ->body('لا يوجد حساب على البوابة بنفس الرقم القومي؛ أنشئ التسجيل بعد تسجيل المتقدم على البوابة.')
                ->warning()->send();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Infolists\Components\TextEntry::make('trainingProgram.title')->label('مقدم على برنامج'),
                        Infolists\Components\TextEntry::make('status')->label('حالة الطلب')->badge()
                            ->formatStateUsing(fn ($state) => TrainingApplication::STATUSES[$state] ?? $state)
                            ->color(fn ($state) => match ($state) { 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray' }),
                        Infolists\Components\TextEntry::make('created_at')->label('تاريخ التقديم')->dateTime('Y-m-d H:i'),
                        Infolists\Components\TextEntry::make('enrollment.status')->label('التسجيل في البرنامج')
                            ->formatStateUsing(fn ($state) => \Modules\Estidama\Models\Enrollment::STATUSES[$state] ?? $state)
                            ->placeholder('لم يُنشأ بعد'),
                        Infolists\Components\TextEntry::make('reviewed_at')->label('تاريخ المراجعة')->dateTime('Y-m-d H:i')->placeholder('—'),
                        Infolists\Components\TextEntry::make('rejection_reason')->label('سبب الرفض')
                            ->visible(fn ($record) => $record->status === 'rejected'),
                    ])->columns(3),

                Infolists\Components\Section::make('البيانات الشخصية')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant_name')->label('الاسم'),
                        Infolists\Components\TextEntry::make('national_id')->label('الرقم القومي')->copyable(),
                        Infolists\Components\TextEntry::make('applicant_email')->label('البريد الإلكتروني')->copyable(),
                        Infolists\Components\TextEntry::make('phone')->label('رقم الهاتف')->copyable(),
                        Infolists\Components\TextEntry::make('gender')->label('الجنس'),
                        Infolists\Components\TextEntry::make('educational_qualification')->label('المؤهل الدراسي'),
                        Infolists\Components\TextEntry::make('specialization')->label('التخصص')->placeholder('—'),
                        Infolists\Components\TextEntry::make('highest_degree')->label('أعلى مؤهل')->placeholder('—'),
                    ])->columns(2),

                Infolists\Components\Section::make('معلومات العمل')
                    ->schema([
                        Infolists\Components\TextEntry::make('employment_status')->label('جهة العمل'),
                        Infolists\Components\TextEntry::make('current_position')->label('الوظيفة الحالية'),
                        Infolists\Components\TextEntry::make('job_address')->label('عنوان الوظيفة'),
                    ])->columns(2),

                Infolists\Components\Section::make('المرفقات والإجابات')
                    ->schema([
                        Infolists\Components\TextEntry::make('has_taken_previous_courses')
                            ->label('هل حصل على دورات سابقة؟')
                            ->formatStateUsing(fn ($state) => $state ? 'نعم' : 'لا'),
                        Infolists\Components\TextEntry::make('previous_courses_names')
                            ->label('أسماء الدورات السابقة')
                            ->placeholder('لم يذكر')
                            ->visible(fn ($record) => $record->has_taken_previous_courses),
                        Infolists\Components\TextEntry::make('personal_statement')->label('الخطاب الشخصي (PDF)')
                            ->state(fn ($record) => filled($record->personal_statement) ? 'فتح الملف' : null)
                            ->url(fn ($record) => SecureUploads::url($record, 'personal_statement'), true)
                            ->placeholder('لم يُرفق'),
                        Infolists\Components\ImageEntry::make('national_id_front_image')->label('صورة البطاقة (وجه أمامي)')
                            ->state(fn ($record) => SecureUploads::url($record, 'national_id_front_image'))->width('100%')->columnSpan(1),
                        Infolists\Components\ImageEntry::make('national_id_back_image')->label('صورة البطاقة (وجه خلفي)')
                            ->state(fn ($record) => SecureUploads::url($record, 'national_id_back_image'))->width('100%')->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('تغيير حالة الطلب')
                    ->helperText('عند القبول يُنشأ تسجيل المتدرب في البرنامج تلقائيًا.')
                    ->options(TrainingApplication::STATUSES)
                    ->required()->native(false)->live(),

                Forms\Components\Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->rows(4)
                    ->maxLength(2000)
                    ->required(fn (Forms\Get $get) => $get('status') === 'rejected')
                    ->visible(fn (Forms\Get $get) => $get('status') === 'rejected'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('trainingProgram'))
            ->columns([
                Tables\Columns\TextColumn::make('applicant_name')->label('اسم المتقدم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('national_id')->label('الرقم القومي')->searchable(),
                Tables\Columns\TextColumn::make('trainingProgram.title')->label('البرنامج')->searchable()->sortable()->limit(30),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => TrainingApplication::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray' }),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التقديم')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(TrainingApplication::STATUSES),
                Tables\Filters\SelectFilter::make('training_program_id')->relationship('trainingProgram', 'title')->label('البرنامج'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('قبول')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('سيتم قبول الطلب وفتح تسجيل المتدرب في البرنامج تلقائيًا.')
                    ->visible(fn (TrainingApplication $record) => $record->status !== 'approved' && static::can('update', $record))
                    ->action(fn (TrainingApplication $record) => static::approve($record)),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([Forms\Components\Textarea::make('rejection_reason')->label('سبب الرفض')->required()->maxLength(2000)])
                    ->visible(fn (TrainingApplication $record) => $record->status === 'pending' && static::can('update', $record))
                    ->action(function (TrainingApplication $record, array $data) {
                        $record->forceFill([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ])->save();
                        Notification::make()->title('تم رفض الطلب')->success()->send();
                    }),
                Tables\Actions\EditAction::make()->label('تغيير الحالة'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['applicant_name', 'national_id', 'trainingProgram.title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'طلب التحاق: ' . $record->applicant_name;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingApplications::route('/'),
            'view' => Pages\ViewTrainingApplication::route('/{record}'),
            'edit' => Pages\EditTrainingApplication::route('/{record}/edit'),
        ];
    }
}
