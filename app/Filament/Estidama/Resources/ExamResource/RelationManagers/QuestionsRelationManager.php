<?php

namespace App\Filament\Estidama\Resources\ExamResource\RelationManagers;

use App\Filament\Estidama\Concerns\AuthorizesProgramRelation;
use App\Filament\Estidama\Resources\ExamQuestionResource;
use Modules\Estidama\Models\ExamQuestion;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    use AuthorizesProgramRelation;

    protected static string $relationship = 'questions';

    protected static ?string $modelLabel = 'سؤال';
    protected static ?string $pluralModelLabel = 'أسئلة الامتحان';
    protected static ?string $title = 'أسئلة الامتحان';

    public function form(Form $form): Form
    {
        return $form->schema(ExamQuestionResource::questionSchema(withExam: false))->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->width(50),
                Tables\Columns\TextColumn::make('question_text')->label('نص السؤال')
                    ->formatStateUsing(fn ($state) => strip_tags((string) $state))
                    ->limit(70)->wrap(),
                Tables\Columns\TextColumn::make('question_type')->label('النوع')->badge()
                    ->formatStateUsing(fn ($state) => ExamQuestion::TYPES[$state ?? 'mcq'] ?? $state),
                Tables\Columns\TextColumn::make('points')->label('الدرجة'),
                Tables\Columns\TextColumn::make('correct_answer')->label('الإجابة الصحيحة')->limit(30)->placeholder('تصحيح يدوي'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة سؤال')
                    ->mutateFormDataUsing(fn (array $data) => ExamQuestionResource::normalizeQuestionData($data)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(fn (array $data) => ExamQuestionResource::normalizeQuestionData($data)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }
}
