<?php

namespace App\Filament\Estidama\Resources\ExamResource\RelationManagers;

use App\Filament\Estidama\Concerns\AuthorizesProgramRelation;
use Modules\Estidama\Models\ExamSubmission;
use App\Services\Lms\ExamService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Attempts of an exam: read answers, grade essays, override feedback. */
class SubmissionsRelationManager extends RelationManager
{
    use AuthorizesProgramRelation;

    protected static string $relationship = 'submissions';

    protected static ?string $modelLabel = 'محاولة';
    protected static ?string $pluralModelLabel = 'محاولات المتدربين';
    protected static ?string $title = 'المحاولات والنتائج';

    protected static function trainerActionAllowed(string $action): bool
    {
        return ! in_array($action, ['create', 'attach', 'associate'], true);
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('المتدرب')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.national_id')->label('الرقم القومي')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('attempt_number')->label('المحاولة')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => ExamSubmission::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'needs_grading' => 'warning', 'in_progress' => 'info', default => 'gray' }),
                Tables\Columns\TextColumn::make('score')->label('الدرجة')->numeric()->sortable()->suffix('%'),
                Tables\Columns\IconColumn::make('passed')->label('ناجح')->boolean(),
                Tables\Columns\TextColumn::make('submitted_at')->label('تاريخ التسليم')->dateTime('Y-m-d H:i')->sortable()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('passed')->label('النجاح'),
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(ExamSubmission::STATUSES),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('answers')
                    ->label('الإجابات')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading('إجابات المتدرب')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (ExamSubmission $record) => view('filament.estidama.attempt-answers', [
                        'attempt' => $record,
                        'questions' => app(ExamService::class)->questionsFor($record),
                    ])),
                Tables\Actions\Action::make('grade')
                    ->label('تصحيح / تقييم')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (ExamSubmission $record) => $record->isFinished())
                    ->form(function (ExamSubmission $record) {
                        $fields = [];
                        $answers = $record->answers ?? [];
                        $manual = $answers['_manual'] ?? [];
                        foreach (app(ExamService::class)->questionsFor($record)->filter->isEssay() as $question) {
                            $fields[] = Forms\Components\TextInput::make('essay.' . $question->id)
                                ->label('درجة: ' . \Illuminate\Support\Str::limit(strip_tags($question->question_text), 80))
                                ->helperText('إجابة المتدرب: ' . \Illuminate\Support\Str::limit((string) ($answers[$question->id] ?? '— لم يُجب —'), 400))
                                ->numeric()->minValue(0)->maxValue($question->points)
                                ->suffix('/ ' . $question->points)
                                ->default($manual[$question->id] ?? null)
                                ->required();
                        }
                        $fields[] = Forms\Components\Textarea::make('feedback')->label('ملاحظات للمتدرب')
                            ->default($record->feedback)->rows(3)->maxLength(2000);

                        return $fields;
                    })
                    ->action(function (ExamSubmission $record, array $data) {
                        app(ExamService::class)->gradeManually($record, $data['essay'] ?? [], $data['feedback'] ?? null, auth()->user());
                        Notification::make()->title('تم حفظ التقييم')->body('الدرجة: ' . $record->fresh()->score . '%')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }
}
