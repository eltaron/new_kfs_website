<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;
use App\Filament\Estidama\Resources\ExamQuestionResource\Pages;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamQuestion;
use App\Services\Lms\LmsAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamQuestionResource extends Resource
{
    use AuthorizesEstidamaStaff;

    protected static ?string $model = ExamQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'إدارة الامتحانات';
    protected static ?string $modelLabel = 'سؤال';
    protected static ?string $pluralModelLabel = 'بنك الأسئلة';
    protected static ?int $navigationSort = 2;

    protected static function trainerCan(string $action, ?Model $record, Authenticatable $user): bool
    {
        if (in_array($action, ['viewAny', 'create', 'reorder'], true)) {
            return true;
        }

        return $record instanceof ExamQuestion && LmsAccess::canManageProgram($user, $record->exam?->training_program_id);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (LmsAccess::isTrainerOnly(auth()->user())) {
            $query->whereHas('exam', fn (Builder $q) => $q->whereIn('training_program_id', LmsAccess::trainerProgramIds(auth()->user()) ?: [0]));
        }

        return $query;
    }

    /** Question fields shared with ExamResource\QuestionsRelationManager. */
    public static function questionSchema(bool $withExam = true): array
    {
        return array_values(array_filter([
            $withExam ? Forms\Components\Select::make('exam_id')
                ->label('ينتمي إلى امتحان')
                ->options(fn () => Exam::query()
                    ->when(LmsAccess::isTrainerOnly(auth()->user()), fn ($q) => $q->whereIn('training_program_id', LmsAccess::trainerProgramIds(auth()->user()) ?: [0]))
                    ->pluck('title', 'id'))
                ->required()
                ->searchable() : null,

            Forms\Components\Select::make('question_type')
                ->label('نوع السؤال')
                ->options(ExamQuestion::TYPES)
                ->default('mcq')
                ->required()
                ->native(false)
                ->live(),

            Forms\Components\RichEditor::make('question_text')
                ->label('نص السؤال')
                ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                ->required()
                ->columnSpanFull(),

            Forms\Components\Repeater::make('options')
                ->label('الخيارات')
                ->schema([
                    Forms\Components\TextInput::make('option_text')->label('نص الخيار')->required()->maxLength(500),
                ])
                ->addActionLabel('أضف خيارًا')
                ->minItems(2)
                ->maxItems(8)
                ->defaultItems(4)
                ->reorderableWithDragAndDrop()
                ->live()
                ->visible(fn (Get $get) => ($get('question_type') ?? 'mcq') === 'mcq')
                ->columnSpanFull(),

            Forms\Components\Select::make('correct_answer')
                ->label('الإجابة الصحيحة')
                ->options(function (Get $get) {
                    if ($get('question_type') === 'true_false') {
                        return array_combine(ExamQuestion::TRUE_FALSE_OPTIONS, ExamQuestion::TRUE_FALSE_OPTIONS);
                    }

                    return collect($get('options') ?? [])->pluck('option_text')->filter(fn ($v) => filled($v))
                        ->mapWithKeys(fn ($v) => [$v => $v])->all();
                })
                ->required(fn (Get $get) => $get('question_type') !== 'essay')
                ->visible(fn (Get $get) => $get('question_type') !== 'essay')
                ->native(false)
                ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                    if ($get('question_type') !== 'mcq') {
                        return;
                    }
                    $options = collect($get('options') ?? [])->pluck('option_text')->all();
                    if (! in_array($value, $options, true)) {
                        $fail('الإجابة الصحيحة يجب أن تكون أحد الخيارات المتاحة.');
                    }
                }),

            Forms\Components\TextInput::make('points')->label('الدرجة')->numeric()->minValue(1)->maxValue(100)->default(1)->required(),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->minValue(0)->maxValue(10000)->default(0),

            Forms\Components\Textarea::make('explanation')
                ->label('شرح الإجابة (يظهر للمتدرب عند المراجعة)')
                ->rows(2)->maxLength(2000)
                ->columnSpanFull(),
        ]));
    }

    /** Normalises stored options per question type. */
    public static function normalizeQuestionData(array $data): array
    {
        $type = $data['question_type'] ?? 'mcq';
        if ($type === 'true_false') {
            $data['options'] = array_map(fn ($o) => ['option_text' => $o], ExamQuestion::TRUE_FALSE_OPTIONS);
        } elseif ($type === 'essay') {
            $data['options'] = [];
            $data['correct_answer'] = null;
        }

        return $data;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema(static::questionSchema())->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('exam'))
            ->columns([
                Tables\Columns\TextColumn::make('question_text')->label('نص السؤال')
                    ->formatStateUsing(fn ($state) => strip_tags((string) $state))
                    ->limit(60)->wrap()->searchable(),
                Tables\Columns\TextColumn::make('exam.title')->label('الامتحان')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('question_type')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => ExamQuestion::TYPES[$state ?? 'mcq'] ?? $state),
                Tables\Columns\TextColumn::make('points')->label('الدرجة')->sortable(),
                Tables\Columns\TextColumn::make('correct_answer')->label('الإجابة الصحيحة')->limit(30)->placeholder('تصحيح يدوي'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_id')->relationship('exam', 'title')->label('الامتحان'),
                Tables\Filters\SelectFilter::make('question_type')->label('النوع')->options(ExamQuestion::TYPES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamQuestions::route('/'),
            'create' => Pages\CreateExamQuestion::route('/create'),
            'edit' => Pages\EditExamQuestion::route('/{record}/edit'),
        ];
    }
}
