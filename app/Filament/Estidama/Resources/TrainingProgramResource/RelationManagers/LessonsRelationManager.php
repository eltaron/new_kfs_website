<?php

namespace App\Filament\Estidama\Resources\TrainingProgramResource\RelationManagers;

use App\Filament\Estidama\Concerns\AuthorizesProgramRelation;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ProgramLesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lessons of a program (video / PDF / rich text / external link / quiz),
 * grouped by section, with ordering, prerequisites and duration.
 */
class LessonsRelationManager extends RelationManager
{
    use AuthorizesProgramRelation;

    protected static string $relationship = 'lessons';

    protected static ?string $modelLabel = 'درس';
    protected static ?string $pluralModelLabel = 'الدروس';
    protected static ?string $title = 'الدروس';
    protected static ?string $icon = 'heroicon-o-play-circle';

    public function form(Form $form): Form
    {
        $program = $this->getOwnerRecord();

        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الدرس')
                    ->schema([
                        Forms\Components\Select::make('program_module_id')
                            ->label('الوحدة')
                            ->options(fn () => $program->programModules()->pluck('title', 'id'))
                            ->required()
                            ->native(false)
                            ->helperText('أضف الوحدات أولاً من تبويب «وحدات البرنامج».'),
                        Forms\Components\Select::make('type')
                            ->label('نوع الدرس')
                            ->options(ProgramLesson::TYPES)
                            ->required()
                            ->native(false)
                            ->default('video')
                            ->live(),
                        Forms\Components\TextInput::make('title')->label('عنوان الدرس')->required()->maxLength(255)->columnSpanFull(),
                        Forms\Components\Textarea::make('summary')->label('نبذة قصيرة')->rows(2)->maxLength(1000)->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('المحتوى')
                    ->schema([
                        // Video
                        Forms\Components\Select::make('video_provider')
                            ->label('مصدر الفيديو')
                            ->options(ProgramLesson::VIDEO_PROVIDERS)
                            ->default('youtube')
                            ->native(false)
                            ->live()
                            ->required(fn (Get $get) => $get('type') === 'video')
                            ->visible(fn (Get $get) => $get('type') === 'video'),
                        Forms\Components\TextInput::make('video_url')
                            ->label('رابط الفيديو')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->regex('#^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)/#i')
                            ->validationMessages(['regex' => 'يُسمح بروابط YouTube أو Vimeo فقط (https).'])
                            ->required(fn (Get $get) => $get('type') === 'video' && in_array($get('video_provider'), ['youtube', 'vimeo'], true))
                            ->visible(fn (Get $get) => $get('type') === 'video' && in_array($get('video_provider'), ['youtube', 'vimeo'], true))
                            ->helperText('للوصول الميسّر: فعّل الترجمة (Captions) العربية على المنصة نفسها.'),

                        // Uploaded MP4 or PDF: private disk, strict type and size
                        Forms\Components\FileUpload::make('file_path')
                            ->label(fn (Get $get) => $get('type') === 'pdf' ? 'ملف PDF' : 'ملف الفيديو (MP4)')
                            ->disk('local')
                            ->visibility('private')
                            ->directory('lms/lessons')
                            ->acceptedFileTypes(fn (Get $get) => $get('type') === 'pdf' ? ['application/pdf'] : ['video/mp4'])
                            ->rules(fn (Get $get) => [$get('type') === 'pdf' ? 'extensions:pdf' : 'extensions:mp4'])
                            ->maxSize(fn (Get $get) => $get('type') === 'pdf' ? 51200 : 512000)
                            ->required(fn (Get $get) => $get('type') === 'pdf' || ($get('type') === 'video' && $get('video_provider') === 'upload'))
                            ->visible(fn (Get $get) => $get('type') === 'pdf' || ($get('type') === 'video' && $get('video_provider') === 'upload'))
                            ->helperText(fn (Get $get) => $get('type') === 'pdf' ? 'PDF فقط، حتى 50 ميجابايت.' : 'MP4 فقط، حتى 500 ميجابايت.')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('captions_path')
                            ->label('ملف الترجمة النصية (WebVTT ‎.vtt)')
                            ->disk('local')
                            ->visibility('private')
                            ->directory('lms/captions')
                            ->acceptedFileTypes(['text/vtt', 'text/plain'])
                            ->rules(['extensions:vtt'])
                            ->maxSize(1024)
                            ->helperText('اختياري لكنه مُوصى به لإتاحة المحتوى لضعاف السمع.')
                            ->visible(fn (Get $get) => $get('type') === 'video' && $get('video_provider') === 'upload')
                            ->columnSpanFull(),

                        // Rich text
                        Forms\Components\RichEditor::make('body')
                            ->label(fn (Get $get) => $get('type') === 'text' ? 'محتوى الدرس' : 'ملاحظات مرافقة (اختياري)')
                            ->disableToolbarButtons(['attachFiles'])
                            ->required(fn (Get $get) => $get('type') === 'text')
                            ->columnSpanFull(),

                        // External link
                        Forms\Components\TextInput::make('external_url')
                            ->label('الرابط الخارجي')
                            ->url()
                            ->maxLength(500)
                            ->rule('starts_with:https://')
                            ->required(fn (Get $get) => $get('type') === 'link')
                            ->visible(fn (Get $get) => $get('type') === 'link')
                            ->columnSpanFull(),

                        // Quiz
                        Forms\Components\Select::make('exam_id')
                            ->label('الاختبار')
                            ->options(fn () => Exam::query()
                                ->where(fn (Builder $q) => $q->where('training_program_id', $program->getKey())->orWhereNull('training_program_id'))
                                ->pluck('title', 'id'))
                            ->searchable()
                            ->required(fn (Get $get) => $get('type') === 'quiz')
                            ->visible(fn (Get $get) => $get('type') === 'quiz')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('title')->label('عنوان الاختبار')->required()->maxLength(255),
                                Forms\Components\TextInput::make('duration_minutes')->label('المدة (دقيقة)')->numeric()->minValue(1)->maxValue(300)->default(10)->required(),
                                Forms\Components\TextInput::make('passing_score')->label('درجة النجاح (%)')->numeric()->minValue(1)->maxValue(100)->default(60)->required(),
                                Forms\Components\TextInput::make('max_attempts')->label('عدد المحاولات')->numeric()->minValue(1)->maxValue(20)->default(3),
                            ])
                            ->createOptionUsing(fn (array $data) => Exam::create($data + [
                                'kind' => 'quiz',
                                'training_program_id' => $program->getKey(),
                                'allow_review' => true,
                            ])->getKey())
                            ->helperText('أضف أسئلة الاختبار من صفحة «الامتحانات». يكتمل الدرس تلقائيًا عند اجتياز الاختبار.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('الترتيب وقواعد الإتاحة')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('الترتيب داخل الوحدة')
                            ->numeric()->minValue(0)->maxValue(10000)
                            ->default(fn () => (int) $program->lessons()->max('sort_order') + 1),
                        Forms\Components\TextInput::make('estimated_minutes')
                            ->label('المدة التقديرية (دقيقة)')
                            ->numeric()->minValue(1)->maxValue(1000),
                        Forms\Components\Select::make('prerequisite_lesson_id')
                            ->label('متطلب سابق')
                            ->options(fn (?ProgramLesson $record) => $program->lessons()
                                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                ->orderBy('sort_order')->pluck('title', 'id'))
                            ->searchable()
                            ->helperText('لا يُفتح هذا الدرس إلا بعد إتمام الدرس المحدد.'),
                        Forms\Components\Toggle::make('is_required')->label('مطلوب لإتمام البرنامج')->default(true),
                        Forms\Components\Toggle::make('is_preview')->label('معاينة مجانية في صفحة البرنامج'),
                        Forms\Components\Toggle::make('is_published')->label('منشور')->default(true),
                    ])->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('module'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable()->width(50),
                Tables\Columns\TextColumn::make('title')->label('الدرس')->searchable()->limit(45),
                Tables\Columns\TextColumn::make('module.title')->label('الوحدة')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => ProgramLesson::TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'video' => 'danger', 'pdf' => 'warning', 'quiz' => 'success', 'link' => 'info', default => 'gray' }),
                Tables\Columns\TextColumn::make('estimated_minutes')->label('المدة')->suffix(' د')->placeholder('—'),
                Tables\Columns\TextColumn::make('completions')
                    ->label('أتمّه')
                    ->state(fn (ProgramLesson $record) => $record->progress()->whereNotNull('completed_at')->count()),
                Tables\Columns\IconColumn::make('is_required')->label('مطلوب')->boolean(),
                Tables\Columns\ToggleColumn::make('is_published')->label('منشور'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('program_module_id')
                    ->label('الوحدة')
                    ->options(fn () => $this->getOwnerRecord()->programModules()->pluck('title', 'id')),
                Tables\Filters\SelectFilter::make('type')->label('النوع')->options(ProgramLesson::TYPES),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة درس')
                    ->disabled(fn () => $this->getOwnerRecord()->programModules()->doesntExist())
                    ->tooltip(fn () => $this->getOwnerRecord()->programModules()->doesntExist() ? 'أضف وحدة أولاً' : null),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()->label('نسخ')
                    ->excludeAttributes(['completions'])
                    ->beforeReplicaSaved(fn (ProgramLesson $replica) => $replica->title = $replica->title . ' (نسخة)'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد دروس بعد')
            ->emptyStateDescription('أضف دروس الفيديو والملفات والاختبارات لكل وحدة.');
    }
}
