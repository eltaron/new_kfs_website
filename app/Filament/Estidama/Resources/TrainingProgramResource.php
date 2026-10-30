<?php

namespace App\Filament\Estidama\Resources;

use App\Filament\Estidama\Concerns\AuthorizesEstidamaStaff;
use App\Filament\Estidama\Resources\TrainingProgramResource\Pages;
use App\Filament\Estidama\Resources\TrainingProgramResource\RelationManagers;
use Modules\Estidama\Models\TrainingProgram;
use App\Models\User;
use App\Services\Lms\LmsAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrainingProgramResource extends Resource
{
    use AuthorizesEstidamaStaff;

    protected static ?string $model = TrainingProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'إدارة التدريب';
    protected static ?string $modelLabel = 'برنامج تدريبي';
    protected static ?string $pluralModelLabel = 'البرامج التدريبية';
    protected static ?int $navigationSort = 1;

    protected static function trainerCan(string $action, ?Model $record, Authenticatable $user): bool
    {
        if (in_array($action, ['viewAny', 'create', 'reorder'], true)) {
            return true;
        }

        return in_array($action, ['view', 'update'], true) && $record && LmsAccess::canManageProgram($user, $record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (LmsAccess::isTrainerOnly($user)) {
            $query->where('instructor_id', $user->getKey());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $isManager = fn (): bool => LmsAccess::isManager(auth()->user());

        return $form
            ->schema([
                Forms\Components\Tabs::make('MainTabs')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('تفاصيل البرنامج')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Select::make('training_center_id')
                                    ->relationship('trainingCenter', 'name')
                                    ->label('المركز التدريبي')
                                    ->searchable()->preload()->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('اسم المركز الجديد')->required()->maxLength(255),
                                    ]),

                                Forms\Components\Select::make('instructor_id')
                                    ->label('المدرب المسؤول')
                                    ->helperText('يظهر هنا المستخدمون الحاصلون على دور المدرب (estidama_trainer).')
                                    ->options(fn () => User::query()
                                        ->whereHas('roles', fn ($q) => $q->where('name', LmsAccess::TRAINER_ROLE))
                                        ->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->visible($isManager),

                                Forms\Components\TextInput::make('title')->label('عنوان البرنامج')->required()->maxLength(255)->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')->label('وصف تفصيلي للبرنامج')->required()->columnSpanFull()
                                    ->disableToolbarButtons(['attachFiles']),

                                Forms\Components\Select::make('level')->label('المستوى')->options(TrainingProgram::LEVELS)->native(false),
                                Forms\Components\TextInput::make('estimated_minutes')->label('المدة التقديرية (بالدقائق)')
                                    ->numeric()->minValue(1)->maxValue(100000)
                                    ->helperText('اتركه فارغًا ليُحسب من مجموع مدد الدروس.'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('الصورة والتواريخ')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Forms\Components\FileUpload::make('image')->label('صورة البرنامج')
                                    ->image()->disk('public')->directory('programs')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(3072)
                                    ->required()->columnSpanFull(),
                                Forms\Components\DatePicker::make('start_date')->label('تاريخ بدء البرنامج')->required()->native(false),
                                Forms\Components\DatePicker::make('end_date')->label('تاريخ انتهاء البرنامج')->required()->afterOrEqual('start_date')->native(false),
                                Forms\Components\Select::make('status')
                                    ->label('حالة البرنامج')
                                    ->options(TrainingProgram::STATUSES)
                                    ->required()->native(false)->default('open'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('التسجيل والرسوم والإتمام')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Toggle::make('requires_application')
                                    ->label('يتطلب طلب التحاق ومراجعة')
                                    ->helperText('عند الإيقاف يستطيع المواطن التسجيل مباشرة من صفحة البرنامج.')
                                    ->default(true),
                                Forms\Components\Toggle::make('sequential')
                                    ->label('إلزام إكمال الدروس بالترتيب')
                                    ->helperText('لا يُفتح الدرس التالي إلا بعد إتمام الدروس المطلوبة السابقة.'),
                                Forms\Components\Toggle::make('is_paid')
                                    ->label('برنامج مدفوع')
                                    ->live()
                                    ->visible($isManager),
                                Forms\Components\TextInput::make('price')
                                    ->label('رسوم البرنامج (ج.م)')
                                    ->numeric()->minValue(1)->maxValue(100000)
                                    ->required(fn (Forms\Get $get) => (bool) $get('is_paid'))
                                    ->visible(fn (Forms\Get $get) => $isManager() && (bool) $get('is_paid'))
                                    ->helperText('يتم السداد عبر بوابة الدفع الإلكتروني الموحدة.'),
                                Forms\Components\Toggle::make('certificate_enabled')
                                    ->label('إصدار شهادة إلكترونية تلقائيًا عند الإتمام')
                                    ->default(true),
                                Forms\Components\Select::make('completion_badge_id')
                                    ->label('شارة تُمنح عند الإتمام (اختياري)')
                                    ->relationship('completionBadge', 'name')
                                    ->searchable()->preload(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('الامتحان النهائي')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\Select::make('exam_id')
                                    ->relationship('finalExam', 'title', fn (Builder $query) => LmsAccess::isTrainerOnly(auth()->user())
                                        ? $query->whereIn('training_program_id', LmsAccess::trainerProgramIds(auth()->user()) ?: [0])
                                        : $query)
                                    ->label('الامتحان النهائي للبرنامج (اختياري)')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('يجب اجتيازه بعد إتمام الدروس للحصول على الشهادة. يمكن إدارة الأسئلة من صفحة "الامتحانات".')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')->label('عنوان الامتحان الجديد')->required()->maxLength(255),
                                        Forms\Components\TextInput::make('duration_minutes')->label('مدة الامتحان (بالدقائق)')->numeric()->minValue(1)->maxValue(600)->required(),
                                        Forms\Components\TextInput::make('passing_score')->label('درجة النجاح (%)')->numeric()->required()->minValue(1)->maxValue(100),
                                        Forms\Components\TextInput::make('max_attempts')->label('عدد المحاولات المسموح')->numeric()->minValue(1)->maxValue(20),
                                        Forms\Components\Hidden::make('kind')->default('final'),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('صورة')->disk('public')->square()->width(60),
                Tables\Columns\TextColumn::make('title')->label('عنوان البرنامج')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('trainingCenter.name')->label('المركز التدريبي')->badge(),
                Tables\Columns\TextColumn::make('instructor.name')->label('المدرب')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => TrainingProgram::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'open' => 'success', 'ongoing' => 'info', default => 'warning' }),
                Tables\Columns\TextColumn::make('price')->label('الرسوم')
                    ->state(fn (TrainingProgram $record) => $record->priceLabel())
                    ->badge()->color(fn (TrainingProgram $record) => $record->isFree() ? 'success' : 'primary'),
                Tables\Columns\TextColumn::make('lessons_count')->counts('lessons')->label('الدروس')->sortable(),
                Tables\Columns\IconColumn::make('exam_id')->label('امتحان نهائي')
                    ->boolean()->state(fn (TrainingProgram $record) => (bool) $record->exam_id),
                Tables\Columns\TextColumn::make('enrollments_count')->counts('enrollments')->label('المسجلون')->sortable(),
                Tables\Columns\TextColumn::make('start_date')->label('يبدأ في')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(TrainingProgram::STATUSES),
                Tables\Filters\SelectFilter::make('training_center_id')->relationship('trainingCenter', 'name')->label('المركز التدريبي'),
                Tables\Filters\TernaryFilter::make('is_paid')->label('مدفوع'),
            ])
            ->actions([
                Tables\Actions\Action::make('public')
                    ->label('صفحة البرنامج')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (TrainingProgram $record) => route('estidama.program.show', $record), shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProgramModulesRelationManager::class,
            RelationManagers\LessonsRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingPrograms::route('/'),
            'create' => Pages\CreateTrainingProgram::route('/create'),
            'edit' => Pages\EditTrainingProgram::route('/{record}/edit'),
        ];
    }
}
