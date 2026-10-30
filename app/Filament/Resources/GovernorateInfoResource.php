<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GovernorateInfoResource\Pages;
use Modules\Content\Models\GovernorateDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GovernorateInfoResource extends Resource
{
    protected static ?string $model = GovernorateDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationGroup = 'عن المحافظة';
    protected static ?string $modelLabel = 'قسم معلوماتي';
    protected static ?string $pluralModelLabel = 'بيانات عن المحافظة';
    protected static ?int $navigationSort = 1;
    public static function canAccess(): bool
    {
        // يمكنك هنا وضع أسماء الأدوار التي يحق لها دخول هذه الصفحة تحديداً
        return auth()->user()->hasAnyRole([
           'super_admin', 
           'إدارة اﻻﺣﺼﺎء واﻟﺘﻘﺎرﻳﺮ والنشر اﻹﻟﻜﱰوﻧﻲ'
        ]);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تعديل محتوى الصفحة التعريفية')
                    ->description('تنبيه: المعرف البرمجي لا يجب تغييره لأنه يستخدم لربط البيانات بالواجهة.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان القسم')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('icon')
                            ->label('كود الأيقونة (مثلاً: fas fa-history)')
                            ->placeholder('يمكنك استخدام FontAwesome')
                            ->maxLength(100),

                        Forms\Components\RichEditor::make('content')
                            ->label('المحتوى التفصيلي (النصوص)')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'undo',
                            ]),

                        Forms\Components\TextInput::make('key')
                            ->label('المعرف البرمجي (Read-only)')
                            ->readOnly() // لا نسمح بتعديله للحفاظ على ربط الـ Blade
                            ->helperText('يستخدم في الكود البرمجي لاستدعاء هذا القسم حصراً.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('المعرف (Key)')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGovernorateInfos::route('/'),
            'create' => Pages\CreateGovernorateInfo::route('/create'),
            'edit' => Pages\EditGovernorateInfo::route('/{record}/edit'),
        ];
    }
}
