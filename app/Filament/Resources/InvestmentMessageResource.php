<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentMessageResource\Pages;
use Modules\Investment\Models\InvestmentMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class InvestmentMessageResource extends Resource
{
    protected static ?string $model = InvestmentMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationLabel = 'رسائل المستثمرين';
    protected static ?string $modelLabel = 'رسالة مستثمر';
    protected static ?string $pluralModelLabel = 'بريد قطاع الاستثمار';
    protected static ?string $navigationGroup = 'الاستثمار والمشروعات';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل رسالة المستثمر')
                    ->description('هذه البيانات تم إرسالها من صفحة التواصل الخاصة بالاستثمار.')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('اسم المستثمر')->disabled(),
                        Forms\Components\TextInput::make('company_name')->label('الشركة / الجهة')->disabled(),
                        Forms\Components\TextInput::make('email')->label('البريد الإلكتروني')->disabled(),
                        Forms\Components\TextInput::make('phone')->label('رقم الهاتف')->disabled(),
                        Forms\Components\TextInput::make('subject')->label('الموضوع')->disabled()->columnSpanFull(),
                        Forms\Components\Textarea::make('message')->label('محتوى الرسالة')->disabled()->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('إجراءات الإدارة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                'new' => 'جديد (لم يقرأ)',
                                'read' => 'تحت الفحص',
                                'replied' => 'تم التواصل مع المستثمر',
                            ])
                            ->required()
                            ->native(false),

                        // يمكنك إضافة حقل لملاحظات الإدارة لو أردت تعديل الموديل مستقبلاً
                        Forms\Components\Placeholder::make('created_at')
                            ->label('تاريخ الإرسال')
                            ->content(fn($record) => $record?->created_at?->diffForHumans() ?? '—'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المستثمر')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('الشركة')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->copyable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('الحالة')
                    ->options([
                        'new' => 'جديد',
                        'read' => 'تم الاطلاع',
                        'replied' => 'تم الرد',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('فلترة حسب الحالة')
                    ->options([
                        'new' => 'رسائل جديدة',
                        'read' => 'تحت المعالجة',
                        'replied' => 'تم التواصل',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('فتح'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentMessages::route('/'),
            'create' => Pages\CreateInvestmentMessage::route('/create'),
            // 'view' => Pages\ViewInvestmentMessage::route('/{record}'),
            'edit' => Pages\EditInvestmentMessage::route('/{record}/edit'),
        ];
    }
}
