<?php

namespace App\Filament\Estidama\Resources\TrainingProgramResource\RelationManagers;

use App\Filament\Estidama\Concerns\AuthorizesProgramRelation;
use App\Filament\Estidama\Resources\EnrollmentResource;
use Modules\Estidama\Models\Enrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Learners of the program with their progress (trainers: read + status). */
class EnrollmentsRelationManager extends RelationManager
{
    use AuthorizesProgramRelation;

    protected static string $relationship = 'enrollments';

    protected static ?string $modelLabel = 'تسجيل';
    protected static ?string $pluralModelLabel = 'المسجلون في البرنامج';
    protected static ?string $title = 'المتدربون وتقدمهم';
    protected static ?string $icon = 'heroicon-o-user-group';

    protected static function trainerActionAllowed(string $action): bool
    {
        return ! in_array($action, ['create', 'attach', 'associate'], true);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(Enrollment::STATUSES)
                    ->native(false)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'lastLesson']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('اسم المتدرب')->searchable(),
                Tables\Columns\TextColumn::make('user.national_id')->label('الرقم القومي')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => Enrollment::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'completed' => 'success', default => 'gray' }),
                Tables\Columns\TextColumn::make('progress_percent')->label('التقدم')->suffix('%')->sortable()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'info' : 'gray')),
                Tables\Columns\TextColumn::make('lastLesson.title')->label('آخر درس')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('payment_status')->label('السداد')->badge()
                    ->formatStateUsing(fn ($state) => Enrollment::PAYMENT_STATUSES[$state ?? 'free'] ?? $state)
                    ->color(fn ($state) => match ($state) { 'unpaid' => 'warning', 'paid' => 'success', default => 'gray' }),
                Tables\Columns\TextColumn::make('last_activity_at')->label('آخر نشاط')->since()->sortable()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(Enrollment::STATUSES),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('التفاصيل')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Enrollment $record) => EnrollmentResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make()->label('الحالة'),
            ])
            ->bulkActions([])
            ->defaultSort('last_activity_at', 'desc');
    }
}
