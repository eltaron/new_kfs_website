<?php

namespace App\Filament\Estidama\Widgets;

use App\Filament\Estidama\Resources\EnrollmentResource;
use Modules\Estidama\Models\Enrollment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingEnrollments extends BaseWidget
{
    public static function canView(): bool
    {
        return \App\Services\Lms\LmsAccess::isManager(auth()->user());
    }

    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;

    public static function getHeading(): string
    {
        return 'أحدث طلبات التسجيل (قيد الانتظار)';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Enrollment::query()
                    ->where('status', 'pending')
                    ->latest('enrolled_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('اسم المتقدم'),

                Tables\Columns\TextColumn::make('trainingProgram.title')
                    ->label('البرنامج'),

                Tables\Columns\TextColumn::make('enrolled_at')
                    ->label('تاريخ التسجيل')
                    ->since(),
            ])
            ->paginated(false) // No need for pagination in a small widget
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('مراجعة الطلب')
                    ->icon('heroicon-o-eye')
                    // Take the admin directly to the edit page for this enrollment
                    ->url(fn(Enrollment $record): string => EnrollmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('لا توجد طلبات تسجيل جديدة في انتظار المراجعة حاليًا.');
    }
}
