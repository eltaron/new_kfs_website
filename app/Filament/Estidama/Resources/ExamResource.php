<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;
use App\Filament\Estidama\Resources\ExamResource\Pages;
use App\Filament\Estidama\Resources\ExamResource\RelationManagers;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamSubmission;
use App\Services\Lms\LmsAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamResource extends Resource
{
    use AuthorizesEstidamaStaff;

    protected static ?string $model = Exam::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'إدارة الامتحانات';
    protected static ?string $modelLabel = 'امتحان';
    protected static ?string $pluralModelLabel = 'الامتحانات والاختبارات';
    protected static ?int $navigationSort = 1;

    protected static function trainerCan(string $action, ?Model $record, Authenticatable $user): bool
    {
        if (in_array($action, ['viewAny', 'create'], true)) {
            return true;
        }

        return $record instanceof Exam && LmsAccess::canManageProgram($user, $record->training_program_id);
    }

    public static function getEloquentQuery(): Builder
    {
        return LmsAccess::scopeToTrainer(parent::getEloquentQuery(), auth()->user());
    }

    public static function form(Form $form): Form
    {
        $trainerOnly = fn (): bool => LmsAccess::isTrainerOnly(auth()->user());

        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الامتحان')
                    ->schema([
                        Forms\Components\TextInput::make('title')->label('عنوان الامتحان')->required()->maxLength(255)->columnSpanFull(),
                        Forms\Components\Select::make('kind')->label('النوع')->options(Exam::KINDS)->default('final')->required()->native(false),
                        Forms\Components\Select::make('training_program_id')
                            ->relationship('trainingProgram', 'title', fn (Builder $query) => $trainerOnly() ? $query->where('instructor_id', auth()->id()) : $query)
                            ->label('البرنامج التدريبي')
                            ->helperText('اتركه فارغًا لامتحان عام (للمشرفين فقط).')
                            ->required($trainerOnly)
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')->label('تعليمات الامتحان')->rows(3)->maxLength(3000)->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('القواعد')
                    ->schema([
                        Forms\Components\TextInput::make('duration_minutes')->label('المدة (بالدقائق)')->required()->numeric()->minValue(1)->maxValue(600)
                            ->helperText('يُفرض المؤقت على الخادم ويتم التسليم تلقائيًا عند انتهاء الوقت.'),
                        Forms\Components\TextInput::make('passing_score')->label('درجة النجاح (%)')->required()->numeric()->minValue(1)->maxValue(100)->suffix('%'),
                        Forms\Components\TextInput::make('max_attempts')->label('الحد الأقصى للمحاولات')->numeric()->minValue(1)->maxValue(20)
                            ->helperText('اتركه فارغًا لعدد غير محدود.'),
                        Forms\Components\Toggle::make('shuffle_questions')->label('ترتيب عشوائي للأسئلة'),
                        Forms\Components\Toggle::make('shuffle_options')->label('ترتيب عشوائي للخيارات'),
                        Forms\Components\Toggle::make('allow_review')->label('السماح بمراجعة الإجابات الصحيحة بعد التسليم')->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount([
                    'questions',
                    'submissions as finished_count' => fn ($q) => $q->where('status', ExamSubmission::STATUS_SUBMITTED),
                    'submissions as passed_count' => fn ($q) => $q->where('status', ExamSubmission::STATUS_SUBMITTED)->where('passed', true),
                    'submissions as needs_grading_count' => fn ($q) => $q->where('status', ExamSubmission::STATUS_NEEDS_GRADING),
                ])
                ->withAvg(['submissions as avg_score' => fn ($q) => $q->where('status', ExamSubmission::STATUS_SUBMITTED)], 'score'))
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان الامتحان')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kind')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => Exam::KINDS[$state] ?? $state)
                    ->color(fn ($state) => $state === 'quiz' ? 'info' : 'primary'),
                Tables\Columns\TextColumn::make('trainingProgram.title')->label('البرنامج')->placeholder('امتحان عام')->limit(30)->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('المدة')->suffix(' د')->sortable(),
                Tables\Columns\TextColumn::make('questions_count')->label('الأسئلة'),
                Tables\Columns\TextColumn::make('finished_count')->label('محاولات مكتملة'),
                Tables\Columns\TextColumn::make('pass_rate')->label('نسبة النجاح')
                    ->state(fn (Exam $record) => $record->finished_count ? round($record->passed_count / $record->finished_count * 100) . '%' : '—'),
                Tables\Columns\TextColumn::make('avg_score')->label('متوسط الدرجة')
                    ->formatStateUsing(fn ($state) => $state !== null ? round((float) $state) . '%' : '—'),
                Tables\Columns\TextColumn::make('needs_grading_count')->label('بانتظار التصحيح')->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')->label('النوع')->options(Exam::KINDS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
