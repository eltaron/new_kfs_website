<?php

namespace App\Filament\Estidama\Pages;

use App\Filament\Exports\EnrollmentExporter; // We will create this
use Modules\Estidama\Models\Enrollment;
use Filament\Tables\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
// use Filament\Actions\Exports\ExportAction;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.estidama.pages.enrollment-report';

    protected static ?string $navigationGroup = 'التقارير والتحليلات';
    protected static ?string $navigationLabel = 'تقرير التسجيلات';
    protected static ?string $title = 'تقرير شامل بالتسجيلات';

    public static function canAccess(): bool
    {
        return \App\Services\Lms\LmsAccess::isManager(auth()->user());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Enrollment::query()->with(['user', 'trainingProgram']))
            ->striped() // Add stripes to table rows for readability

            ->headerActions([
                ExportAction::make()
                    ->exporter(EnrollmentExporter::class)
                    ->label('تصدير إلى Excel'),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('اسم المتدرب')->searchable(),
                Tables\Columns\TextColumn::make('user.national_id')->label('الرقم القومي')->searchable(),
                Tables\Columns\TextColumn::make('user.phone')->label('رقم الهاتف')->searchable(),
                Tables\Columns\TextColumn::make('trainingProgram.title')->label('البرنامج التدريبي')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'primary' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('enrolled_at')->label('تاريخ التسجيل')->date('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'قيد الانتظار', 'approved' => 'مقبول', 'rejected' => 'مرفوض', 'completed' => 'مكتمل'])
                    ->label('تصفية حسب الحالة'),

                Tables\Filters\SelectFilter::make('training_program_id')
                    ->relationship('trainingProgram', 'title')
                    ->searchable()->preload()->multiple()->label('تصفية حسب البرنامج'),

                Tables\Filters\Filter::make('enrolled_at')
                    ->form([
                        DatePicker::make('enrolled_from')->label('تاريخ التسجيل من'),
                        DatePicker::make('enrolled_until')->label('إلى تاريخ'),
                    ])
                    ->query(
                        fn(Builder $query, array $data): Builder => $query
                            ->when($data['enrolled_from'], fn(Builder $query, $date): Builder => $query->whereDate('enrolled_at', '>=', $date))
                            ->when($data['enrolled_until'], fn(Builder $query, $date): Builder => $query->whereDate('enrolled_at', '<=', $date))
                    ),
            ])
            ->actions([]) // No row actions needed for a report
            ->bulkActions([]); // No bulk actions needed for a report
    }
}
