<?php

namespace App\Filament\Estidama\Resources\TrainingProgramResource\RelationManagers;

use App\Filament\Estidama\Concerns\AuthorizesProgramRelation;
use Modules\Estidama\Models\ProgramModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Course sections ("الوحدات"). Lessons are managed in LessonsRelationManager.
 */
class ProgramModulesRelationManager extends RelationManager
{
    use AuthorizesProgramRelation;

    protected static string $relationship = 'programModules';

    protected static ?string $modelLabel = 'وحدة';
    protected static ?string $pluralModelLabel = 'وحدات البرنامج';
    protected static ?string $title = 'وحدات البرنامج (الأقسام)';
    protected static ?string $icon = 'heroicon-o-rectangle-stack';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان الوحدة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('وصف موجز للوحدة')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),

                Forms\Components\Select::make('type')
                    ->label('طبيعة الوحدة')
                    ->options([
                        'offline_lecture' => 'محاضرات (حضوري / مختلط)',
                        'video' => 'فيديو مسجل',
                        'pdf' => 'ملفات PDF',
                        'quiz' => 'اختبارات',
                    ])
                    ->required()
                    ->native(false)
                    ->default('offline_lecture')
                    ->helperText('للتصنيف فقط — محتوى الوحدة يُضاف من تبويب «الدروس».'),

                Forms\Components\TextInput::make('order')
                    ->label('ترتيب العرض')
                    ->numeric()->minValue(0)->maxValue(1000)
                    ->default(fn () => (int) $this->getOwnerRecord()->programModules()->max('order') + 1),

                Forms\Components\TextInput::make('unlock_after_days')
                    ->label('تُتاح بعد (أيام من بدء التسجيل)')
                    ->numeric()->minValue(0)->maxValue(365)
                    ->helperText('جدولة المحتوى (Drip): اتركه فارغًا لإتاحة الوحدة فورًا.'),

                Forms\Components\TextInput::make('estimated_minutes')
                    ->label('المدة التقديرية (دقيقة)')
                    ->numeric()->minValue(1)->maxValue(10000),

                Forms\Components\Toggle::make('is_published')
                    ->label('منشورة للمتدربين')
                    ->default(true),

                // Legacy single-file content (kept for old records). Private disk, strict types.
                Forms\Components\FileUpload::make('content_path')
                    ->label('ملف مرفق قديم (فيديو MP4 أو PDF)')
                    ->disk('local')
                    ->visibility('private')
                    ->directory('lms/modules')
                    ->acceptedFileTypes(['application/pdf', 'video/mp4'])
                    ->maxSize(204800)
                    ->helperText('اختياري. يُفضّل إضافة المحتوى كدروس. الحد الأقصى 200 ميجابايت.')
                    ->visible(fn (Forms\Get $get, ?ProgramModule $record) => in_array($get('type'), ['video', 'pdf'], true) && filled($record?->content_path))
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('order')
            ->reorderable('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')->label('#')->sortable()->width(50),
                Tables\Columns\TextColumn::make('title')->label('عنوان الوحدة')->searchable(),
                Tables\Columns\TextColumn::make('lessons_count')->counts('lessons')->label('عدد الدروس'),
                Tables\Columns\TextColumn::make('unlock_after_days')->label('تُتاح بعد')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' يوم' : 'فورًا')->placeholder('فورًا'),
                Tables\Columns\ToggleColumn::make('is_published')->label('منشورة'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة وحدة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('سيتم حذف جميع دروس هذه الوحدة وتقدم المتدربين فيها.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد وحدات بعد')
            ->emptyStateDescription('ابدأ بإضافة وحدة (قسم)، ثم أضف دروسها من تبويب «الدروس».');
    }
}
