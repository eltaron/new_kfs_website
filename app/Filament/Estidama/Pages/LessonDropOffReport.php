<?php

namespace App\Filament\Estidama\Pages;

use Modules\Estidama\Models\LessonProgress;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\TrainingProgram;
use App\Services\Lms\LmsAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Funnel per lesson: how many learners opened it, finished it, and where they
 * stop (drop-off = opened but never completed).
 */
class LessonDropOffReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static string $view = 'filament.estidama.pages.program-analytics-report';
    protected static ?string $navigationGroup = 'التقارير والتحليلات';
    protected static ?string $navigationLabel = 'التسرب حسب الدرس';
    protected static ?string $title = 'تحليل التسرب ومعدلات الإتمام لكل درس';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return LmsAccess::isStaff(auth()->user());
    }

    public function table(Table $table): Table
    {
        $query = ProgramLesson::query()
            ->with(['trainingProgram', 'module'])
            ->withCount([
                'progress as started_count',
                'progress as completed_count' => fn (Builder $q) => $q->whereNotNull('completed_at'),
            ])
            ->addSelect([
                'learners_count' => \Modules\Estidama\Models\Enrollment::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('enrollments.training_program_id', 'program_lessons.training_program_id')
                    ->whereIn('enrollments.status', ['approved', 'completed']),
            ]);

        if (LmsAccess::isTrainerOnly(auth()->user())) {
            $query->whereIn('training_program_id', LmsAccess::trainerProgramIds(auth()->user()) ?: [0]);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('trainingProgram.title')->label('البرنامج')->limit(30)->sortable(),
                Tables\Columns\TextColumn::make('module.title')->label('الوحدة')->limit(25),
                Tables\Columns\TextColumn::make('title')->label('الدرس')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => ProgramLesson::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('learners_count')->label('المتدربون'),
                Tables\Columns\TextColumn::make('started_count')->label('فتحوا الدرس')->sortable(),
                Tables\Columns\TextColumn::make('completed_count')->label('أتمّوه')->sortable(),
                Tables\Columns\TextColumn::make('reach_rate')->label('نسبة الوصول')
                    ->state(fn (ProgramLesson $record) => $record->learners_count ? round($record->started_count / $record->learners_count * 100) . '%' : '—'),
                Tables\Columns\TextColumn::make('drop_off')->label('التسرب داخل الدرس')
                    ->state(fn (ProgramLesson $record) => $record->started_count ? round(($record->started_count - $record->completed_count) / $record->started_count * 100) : null)
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : $state . '%')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 50 ? 'danger' : ($state >= 25 ? 'warning' : 'success'))),
                Tables\Columns\TextColumn::make('avg_minutes')->label('متوسط زمن الإتمام')
                    ->state(function (ProgramLesson $record) {
                        $avg = LessonProgress::where('program_lesson_id', $record->id)->whereNotNull('completed_at')->whereNotNull('started_at')
                            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as m')->value('m');

                        return $avg === null ? '—' : round((float) $avg) . ' د';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_program_id')
                    ->label('البرنامج')
                    ->options(fn () => TrainingProgram::query()
                        ->when(LmsAccess::isTrainerOnly(auth()->user()), fn ($q) => $q->where('instructor_id', auth()->id()))
                        ->pluck('title', 'id')),
            ])

            ->defaultSort('sort_order');
    }
}
