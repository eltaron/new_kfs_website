<?php

namespace App\Filament\Estidama\Pages;

use Modules\Estidama\Models\ExamSubmission;
use Modules\Estidama\Models\TrainingProgram;
use App\Services\Lms\LmsAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/** Completion rates, progress and final-exam results per program. */
class ProgramAnalyticsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static string $view = 'filament.estidama.pages.program-analytics-report';
    protected static ?string $navigationGroup = 'التقارير والتحليلات';
    protected static ?string $navigationLabel = 'تحليلات إتمام البرامج';
    protected static ?string $title = 'تحليلات الإتمام والأداء لكل برنامج';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return LmsAccess::isStaff(auth()->user());
    }

    public function table(Table $table): Table
    {
        $query = TrainingProgram::query()
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn ($q) => $q->where('status', 'approved'),
                'enrollments as completed_enrollments_count' => fn ($q) => $q->where('status', 'completed'),
                'enrollments as started_enrollments_count' => fn ($q) => $q->whereIn('status', ['approved', 'completed'])->where('progress_percent', '>', 0),
                'certificates',
            ])
            ->withAvg(['enrollments as avg_progress' => fn ($q) => $q->whereIn('status', ['approved', 'completed'])], 'progress_percent')
            ->addSelect([
                'exam_takers_count' => ExamSubmission::query()
                    ->selectRaw('COUNT(DISTINCT exam_submissions.user_id)')
                    ->whereColumn('exam_submissions.exam_id', 'training_programs.exam_id')
                    ->where('exam_submissions.status', ExamSubmission::STATUS_SUBMITTED),
                'exam_passers_count' => ExamSubmission::query()
                    ->selectRaw('COUNT(DISTINCT exam_submissions.user_id)')
                    ->whereColumn('exam_submissions.exam_id', 'training_programs.exam_id')
                    ->where('exam_submissions.status', ExamSubmission::STATUS_SUBMITTED)
                    ->where('exam_submissions.passed', true),
            ]);

        if (LmsAccess::isTrainerOnly(auth()->user())) {
            $query->where('instructor_id', auth()->id());
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('البرنامج')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('enrollments_count')->label('إجمالي التسجيلات')->sortable(),
                Tables\Columns\TextColumn::make('started_enrollments_count')->label('بدأوا التعلم')->sortable(),
                Tables\Columns\TextColumn::make('completed_enrollments_count')->label('أتمّوا')->sortable(),
                Tables\Columns\TextColumn::make('completion_rate')->label('نسبة الإتمام')
                    ->state(function (TrainingProgram $record) {
                        $base = $record->active_enrollments_count + $record->completed_enrollments_count;

                        return $base ? round($record->completed_enrollments_count / $base * 100) : null;
                    })
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : $state . '%')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 75 ? 'success' : ($state >= 40 ? 'warning' : 'danger'))),
                Tables\Columns\TextColumn::make('avg_progress')->label('متوسط التقدم')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : round((float) $state) . '%'),
                Tables\Columns\TextColumn::make('exam_takers_count')->label('أدّوا الامتحان')->numeric(),
                Tables\Columns\TextColumn::make('exam_pass_rate')->label('نسبة النجاح في الامتحان')
                    ->state(fn (TrainingProgram $record) => $record->exam_takers_count ? round($record->exam_passers_count / $record->exam_takers_count * 100) . '%' : '—'),
                Tables\Columns\TextColumn::make('certificates_count')->label('شهادات صادرة'),
                Tables\Columns\TextColumn::make('top_students')
                    ->label('المتفوقون')
                    ->html()
                    ->state(fn (TrainingProgram $record) => $this->topStudents($record)),
            ])
            ->defaultSort('enrollments_count', 'desc');
    }

    /** Top 3 learners by best final-exam score. */
    protected function topStudents(TrainingProgram $program): string
    {
        if (! $program->exam_id) {
            return '<span class="text-gray-400">لا يوجد امتحان</span>';
        }

        $rows = DB::table('exam_submissions')
            ->join('users', 'users.id', '=', 'exam_submissions.user_id')
            ->where('exam_submissions.exam_id', $program->exam_id)
            ->where('exam_submissions.status', ExamSubmission::STATUS_SUBMITTED)
            ->groupBy('exam_submissions.user_id', 'users.name')
            ->selectRaw('users.name as name, MAX(exam_submissions.score) as best')
            ->orderByDesc('best')
            ->limit(3)
            ->get();

        if ($rows->isEmpty()) {
            return '<span class="text-gray-400">—</span>';
        }

        return '<ol class="list-decimal ps-4 text-sm">' . $rows->map(fn ($r) => '<li>' . e($r->name) . ' (' . (int) $r->best . '%)</li>')->implode('') . '</ol>';
    }
}
