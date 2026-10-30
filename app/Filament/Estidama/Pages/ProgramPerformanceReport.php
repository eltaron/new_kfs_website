<?php

namespace App\Filament\Estidama\Pages;

use App\Filament\Estidama\Widgets\ProgramPerformanceStats;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\TrainingProgram;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ProgramPerformanceReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string $view = 'filament.estidama.pages.program-performance-report';

    protected static ?string $navigationGroup = 'التقارير والتحليلات';
    protected static ?string $navigationLabel = 'تقرير أداء البرامج';
    protected static ?string $title = 'تحليل أداء البرامج التدريبية';

    public static function canAccess(): bool
    {
        return \App\Services\Lms\LmsAccess::isManager(auth()->user());
    }



    public function table(Table $table): Table
    {
        return $table
            ->query(TrainingProgram::query()->withCount(['enrollments', 'enrollments as completed_enrollments_count' => function ($query) {
                $query->where('status', 'completed');
            }]))
            ->heading('تفاصيل أداء كل برنامج تدريبي')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان البرنامج')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trainingCenter.name')
                    ->label('المركز التدريبي')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('إجمالي المسجلين')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_takers_count')
                    ->label('أدوا الامتحان')
                    ->numeric()
                    ->getStateUsing(function (TrainingProgram $record) {
                        // Check if the program has an exam linked
                        if (! $record->exam_id) {
                            return 0;
                        }
                        // Count distinct users who submitted for this exam
                        return DB::table('exam_submissions')
                            ->where('exam_id', $record->exam_id)
                            ->distinct('user_id')
                            ->count('user_id');
                    }),
                Tables\Columns\TextColumn::make('completed_enrollments_count')
                    ->label('عدد المكتملين')
                    ->numeric()
                    ->sortable(),

                // Custom calculated column for completion rate
                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('نسبة الإكمال')
                    ->formatStateUsing(function ($record) {
                        if ($record->enrollments_count === 0) {
                            return 'N/A';
                        }
                        $rate = ($record->completed_enrollments_count / $record->enrollments_count) * 100;
                        return number_format($rate, 2) . '%';
                    })
                    ->color(function ($record) {
                        if ($record->enrollments_count === 0) return 'gray';
                        $rate = ($record->completed_enrollments_count / $record->enrollments_count) * 100;
                        return $rate >= 75 ? 'success' : ($rate >= 50 ? 'warning' : 'danger');
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_center_id')
                    ->relationship('trainingCenter', 'name')
                    ->label('تصفية حسب المركز'),
            ]);
    }
    // This method provides the data for the Stats widgets at the top
    protected function getHeaderWidgets(): array
    {
        return [
            ProgramPerformanceStats::class,
        ];
    }
}
