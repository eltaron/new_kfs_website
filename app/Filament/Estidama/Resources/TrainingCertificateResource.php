<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Resources\TrainingCertificateResource\Pages;
use Modules\Estidama\Models\TrainingCertificate;
use App\Services\Lms\LmsAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Issued e-certificates: print, verify, revoke / reinstate. */
class TrainingCertificateResource extends Resource
{
    protected static ?string $model = TrainingCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'إدارة التدريب';
    protected static ?string $modelLabel = 'شهادة';
    protected static ?string $pluralModelLabel = 'الشهادات الصادرة';
    protected static ?int $navigationSort = 4;

    protected static function trainerCan(string $action, ?Model $record, Authenticatable $user): bool
    {
        return $action === 'viewAny'
            || ($action === 'view' && $record instanceof TrainingCertificate && LmsAccess::canManageProgram($user, $record->training_program_id));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Certificates have no Shield policy: only managers and (read-only) trainers. */
    public static function can(string $action, ?Model $record = null): bool
    {
        $user = auth()->user();
        if (LmsAccess::isManager($user)) {
            return true;
        }

        return LmsAccess::isTrainerOnly($user) && static::trainerCan($action, $record, $user);
    }

    public static function getEloquentQuery(): Builder
    {
        return LmsAccess::scopeToTrainer(parent::getEloquentQuery(), auth()->user());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('trainingProgram'))
            ->columns([
                Tables\Columns\TextColumn::make('serial')->label('الرقم التسلسلي')->searchable()->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('holder_name')->label('اسم الحاصل')->searchable(),
                Tables\Columns\TextColumn::make('program_title')->label('البرنامج')->limit(35)->searchable(),
                Tables\Columns\TextColumn::make('final_score')->label('الدرجة')->suffix('%')->placeholder('—'),
                Tables\Columns\TextColumn::make('issued_at')->label('تاريخ الإصدار')->date('Y-m-d')->sortable(),
                Tables\Columns\IconColumn::make('valid')->label('سارية')->boolean()
                    ->state(fn (TrainingCertificate $record) => $record->isValid()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_program_id')->relationship('trainingProgram', 'title')->label('البرنامج'),
                Tables\Filters\TernaryFilter::make('revoked_at')->label('ملغاة')->nullable(),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (TrainingCertificate $record) => route('lms.certificates.show', $record->serial), true),
                Tables\Actions\Action::make('verify')
                    ->label('صفحة التحقق')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->url(fn (TrainingCertificate $record) => $record->verificationUrl(), true),
                Tables\Actions\Action::make('revoke')
                    ->label('إلغاء الشهادة')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (TrainingCertificate $record) => $record->isValid() && LmsAccess::isManager(auth()->user()))
                    ->form([Forms\Components\TextInput::make('reason')->label('سبب الإلغاء')->required()->maxLength(255)])
                    ->action(function (TrainingCertificate $record, array $data) {
                        $record->update(['revoked_at' => now(), 'revocation_reason' => $data['reason']]);
                        Notification::make()->title('تم إلغاء الشهادة')->success()->send();
                    }),
                Tables\Actions\Action::make('restore')
                    ->label('إعادة التفعيل')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (TrainingCertificate $record) => ! $record->isValid() && LmsAccess::isManager(auth()->user()))
                    ->action(fn (TrainingCertificate $record) => $record->update(['revoked_at' => null, 'revocation_reason' => null])),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingCertificates::route('/'),
        ];
    }
}
