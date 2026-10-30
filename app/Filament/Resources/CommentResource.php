<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use Modules\Content\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup = 'المحتوى';
    protected static ?string $modelLabel = 'تعليق';
    protected static ?string $pluralModelLabel = 'التعليقات';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التعليق الأساسية')
                    ->schema([
                        Forms\Components\Select::make('post_id')
                            ->relationship('post', 'title')
                            ->label('المقال المرتبط')
                            ->searchable()
                            ->preload() // Loads options asynchronously for better performance
                            ->required(),
                        Forms\Components\Textarea::make('content')
                            ->label('محتوى التعليق')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_approved')
                            ->label('تمت الموافقة عليه')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات كاتب التعليق')
                    ->description('سيتم ملء هذه الحقول تلقائيًا بناءً على نوع الكاتب (زائر أو مستخدم مسجل).')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('المستخدم المسجل (إن وجد)')
                            ->searchable()
                            ->placeholder('هذا التعليق من زائر'),
                        Forms\Components\TextInput::make('author_name')
                            ->label('اسم الزائر (إن وجد)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('author_email')
                            ->label('بريد الزائر (إن وجد)')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author_name')
                    ->label('الكاتب')
                    // A custom formatter to show either the registered user or the visitor name
                    ->formatStateUsing(fn($record) => $record->user?->name ?? $record->author_name)
                    ->searchable(['author_name', 'user.name']),

                Tables\Columns\TextColumn::make('post.title')
                    ->label('على مقال')
                    ->searchable()
                    ->sortable()
                    ->limit(40), // Truncate long post titles

                Tables\Columns\TextColumn::make('content')
                    ->label('مقتطف من التعليق')
                    ->limit(50),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('حالة الموافقة')
                    ->trueLabel('تمت الموافقة')
                    ->falseLabel('في انتظار الموافقة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Quick action to approve/unapprove directly from the table
                Tables\Actions\Action::make('approve')
                    ->label(fn(Comment $record) => $record->is_approved ? 'إلغاء الموافقة' : 'موافقة')
                    ->icon(fn(Comment $record) => $record->is_approved ? 'heroicon-o-x-mark' : 'heroicon-o-check')
                    ->color(fn(Comment $record) => $record->is_approved ? 'warning' : 'success')
                    ->action(function (Comment $record) {
                        $record->is_approved = !$record->is_approved;
                        $record->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve')
                        ->action(fn($records) => $records->each->update(['is_approved' => true]))
                        ->requiresConfirmation()
                        ->label('موافقة على المحدد')
                        ->icon('heroicon-o-check-circle'),
                    Tables\Actions\BulkAction::make('unapprove')
                        ->action(fn($records) => $records->each->update(['is_approved' => false]))
                        ->requiresConfirmation()
                        ->label('إلغاء موافقة المحدد')
                        ->icon('heroicon-o-x-circle'),
                ]),
            ])
            ->defaultSort('created_at', 'desc'); // Show newest comments first
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            // 'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
