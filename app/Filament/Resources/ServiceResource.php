<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use Modules\Citizen\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'الخدمات الإلكترونية';
    protected static ?string $modelLabel = 'خدمة';
    protected static ?string $pluralModelLabel = 'دليل الخدمات';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        return auth()->user()->hasRole('super_admin') ? $query : $query->where('is_highlighted', true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tabs')->tabs([
                
                Forms\Components\Tabs\Tab::make('المعلومات الأساسية')->schema([
                    Forms\Components\Select::make('parent_id')
                        ->relationship('parent', 'title')->label('الفئة الرئيسية')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('title')->label('اسم الخدمة')->required(),
                    Forms\Components\TextInput::make('icon')
                        ->label('أيقونة (مثل fas fa-ad)')
                        ->placeholder('fas fa-ad'),
                    Forms\Components\TextInput::make('link')->label('رابط خارجي للخدمة')->url(),
                    Forms\Components\RichEditor::make('description')->label('الوصف التفصيلي')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('نظام التسعير والرسوم')->schema([
                    Forms\Components\Select::make('pricing_type')
                        ->label('نوع التسعير')
                        ->options(['fixed' => 'سعر ثابت مقطوع', 'category' => 'حسب الفئة (مناطق/مراكز)'])
                        ->reactive()->default('fixed')->required(),

                    Forms\Components\TextInput::make('base_price')
                        ->label('سعر المتر الأساسي')
                        ->numeric()->prefix('EGP')->required(),

                    Forms\Components\Repeater::make('category_pricing')
                        ->label('قائمة أسعار الفئات')
                        ->visible(fn(Forms\Get $get) => $get('pricing_type') === 'category')
                        ->schema([
                            Forms\Components\TextInput::make('name')->label('اسم المنطقة (حي شرق/غرب)')->required(),
                            Forms\Components\TextInput::make('price_multiplier')->label('سعر المتر في المنطقة')->numeric()->required(),
                        ])->columns(2)->columnSpanFull(),

                    Forms\Components\TextInput::make('insurance_percentage')
                        ->label('نسبة التأمين (%)')
                        ->numeric()->suffix('%')->default(0),

                    Forms\Components\Toggle::make('has_vat')->label('تطبق ضريبة (14%)')->default(true),
                    Forms\Components\TextInput::make('martyr_stamp_fee')->label('طابع شهداء')->numeric()->default(5),
                    Forms\Components\TextInput::make('sms_fee')->label('رسوم رسائل')->numeric()->default(10),
                ])->columns(2),

               Forms\Components\Tabs\Tab::make('حقول النماذج')->schema([
                    Forms\Components\Repeater::make('form_fields')
                        ->label('حقول نموذج طلب الخدمة')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('name')->label('ID برمجي')->required(),
                                Forms\Components\TextInput::make('label')->label('عنوان الحقل')->required(),
                                Forms\Components\Select::make('type')
                                    ->label('نوع الحقل')
                                    ->options([
                                        'text' => 'نصي',
                                        'number' => 'رقم',
                                        'select' => 'قائمة',
                                        'file' => 'ملف'
                                    ])
                                    ->reactive() // ⚠️ ضروري لتحديث الواجهة فور تغيير النوع
                                    ->required(),
                            ]),
                            
                            // 👇 حقل الخيارات يظهر فقط عند اختيار "قائمة"
                            Forms\Components\Repeater::make('options')
                                ->label('خيارات القائمة')
                                ->schema([
                                    Forms\Components\TextInput::make('label')->label('اسم الخيار الظاهر')->required(),
                                    Forms\Components\TextInput::make('value')->label('قيمة الخيار البرمجية')->required(),
                                ])
                                ->visible(fn(Forms\Get $get) => $get('type') === 'select')
                                ->columns(2)
                                ->columnSpanFull()
                                ->defaultItems(1)
                                ->grid(2),
                                
                        ])->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('الخدمة')->searchable(),
                Tables\Columns\TextColumn::make('pricing_type')
                    ->label('نظام التسعير')->badge()
                    ->formatStateUsing(fn($state) => $state === 'fixed' ? 'ثابت' : 'فئات'),
                Tables\Columns\TextColumn::make('base_price')->label('السعر الأساسي')->money('EGP'),
                Tables\Columns\IconColumn::make('link')->label('رابط')->url(fn($record) => $record->link)->openUrlInNewTab()->icon('heroicon-o-link'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn() => auth()->user()->hasRole('super_admin')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}