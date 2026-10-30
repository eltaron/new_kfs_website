<?php

namespace App\Filament\Estidama\Pages;

use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamSubmission;
use App\Services\Lms\LmsAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/** Attempts, pass rate, scores and timing for every exam / quiz. */
class ExamStatsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.estidama.pages.program-analytics-report';
    protected static ?string $navigationGroup = 'التقارير والتحليلات';
    protected static ?string $navigationLabel = 'إحصاءات الامتحانات';
    protected static ?string $title = 'إحصاءات الامتحانات والاختبارات';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return LmsAccess::isStaff(auth()->user());
    }

    public function table(Table $table): Table
    {
        $finished = fn ($q) => $q->where('status', ExamSubmission::STATUS_SUBMITTED);

        $query = Exam::query()
            ->with('trainingProgram')
            ->withCount([
                'questions',
                'submissions as attempts_count' => $finished,
                'submissions as passed_count' => fn ($q) => $finished($q)->where('passed', true),
                'submissions as pending_count' => fn ($q) => $q->where('status', ExamSubmission::STATUS_NEEDS_GRADING),
            ])
            ->withAvg(['submissions as avg_score' => $finished], 'score')
            ->withMax(['submissions as max_score' => $finished], 'score')
            ->addSelect([
                'learners_count' => ExamSubmission::query()
                    ->selectRaw('COUNT(DISTINCT exam_submissions.user_id)')
                    ->whereColumn('exam_submissions.exam_id', 'exams.id'),
                'avg_minutes' => ExamSubmission::query()
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, submitted_at)) / 60')
                    ->whereColumn('exam_submissions.exam_id', 'exams.id')
                    ->whereNotNull('started_at')->whereNotNull('submitted_at'),
            ]);

        if (LmsAccess::isTrainerOnly(auth()->user())) {
            $query->whereIn('training_program_id', LmsAccess::trainerProgramIds(auth()->user()) ?: [0]);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('الامتحان')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('kind')->label('النوع')->badge()->formatStateUsing(fn ($state) => Exam::KINDS[$state] ?? $state),
                Tables\Columns\TextColumn::make('trainingProgram.title')->label('البرنامج')->limit(25)->placeholder('عام'),
                Tables\Columns\TextColumn::make('questions_count')->label('الأسئلة'),
                Tables\Columns\TextColumn::make('learners_count')->label('متدربون'),
                Tables\Columns\TextColumn::make('attempts_count')->label('محاولات مكتملة')->sortable(),
                Tables\Columns\TextColumn::make('pass_rate')->label('نسبة النجاح')
                    ->state(fn (Exam $record) => $record->attempts_count ? round($record->passed_count / $record->attempts_count * 100) : null)
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : $state . '%')
                    ->badge()->color(fn ($state) => $state === null ? 'gray' : ($state >= 70 ? 'success' : ($state >= 40 ? 'warning' : 'danger'))),
                Tables\Columns\TextColumn::make('avg_score')->label('متوسط الدرجة')->formatStateUsing(fn ($state) => $state === null ? '—' : round((float) $state) . '%'),
                Tables\Columns\TextColumn::make('max_score')->label('أعلى درجة')->formatStateUsing(fn ($state) => $state === null ? '—' : $state . '%'),
                Tables\Columns\TextColumn::make('avg_minutes')->label('متوسط زمن الحل')->formatStateUsing(fn ($state) => $state === null ? '—' : round((float) $state, 1) . ' د'),
                Tables\Columns\TextColumn::make('pending_count')->label('بانتظار التصحيح')->badge()->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('hardest')->label('أصعب سؤال')
                    ->state(fn (Exam $record) => $this->hardestQuestion($record))->wrap(),
            ])
            ->defaultSort('attempts_count', 'desc');
    }

    /** Question with the lowest share of correct answers among finished attempts. */
    protected function hardestQuestion(Exam $exam): string
    {
        if (! $exam->attempts_count) {
            return '—';
        }

        $answers = DB::table('exam_submissions')->where('exam_id', $exam->id)
            ->where('status', ExamSubmission::STATUS_SUBMITTED)->limit(500)->pluck('answers')
            ->map(fn ($json) => json_decode((string) $json, true) ?: []);

        $worst = null;
        foreach ($exam->questions()->where('question_type', '!=', 'essay')->get() as $question) {
            $correct = $answers->filter(fn ($a) => $question->isCorrect($a[$question->id] ?? null))->count();
            $rate = $correct / max(1, $answers->count());
            if ($worst === null || $rate < $worst[1]) {
                $worst = [$question, $rate];
            }
        }

        return $worst
            ? \Illuminate\Support\Str::limit(strip_tags($worst[0]->question_text), 45) . ' (' . round($worst[1] * 100) . '% صحيح)'
            : '—';
    }
}
