<?php

namespace Modules\Marketplace\Filament\Vendor\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource\Pages;
use Modules\Marketplace\Models\MarketCategory;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * منتجاتي — a vendor manages only their own products. Publishing is done by
 * marketplace admins: the vendor saves a draft or submits it for review.
 */
class ProductResource extends Resource
{
    protected static ?string $model = MarketProduct::class;

    protected static ?string $slug = 'products';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $modelLabel = 'منتج';

    protected static ?string $pluralModelLabel = 'منتجاتي';

    protected static ?string $navigationLabel = 'منتجاتي';

    protected static ?int $navigationSort = 1;

    /** Statuses a vendor may choose. */
    public const VENDOR_STATUSES = [
        MarketProduct::STATUS_DRAFT => 'حفظ كمسودة (غير ظاهر للمشترين)',
        MarketProduct::STATUS_PENDING => 'إرسال للمراجعة والنشر',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', MarketplaceAccess::currentVendorId())
            ->with(['category', 'primaryImage']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المنتج')
                ->description('اكتب اسماً واضحاً ووصفاً بسيطاً يساعد المشتري على فهم المنتج.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('اسم المنتج')
                        ->required()->minLength(3)->maxLength(150)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('category_id')
                        ->label('القسم')
                        ->options(fn () => MarketCategory::query()->active()->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('حالة المنتج')
                        ->options(fn (?MarketProduct $record) => $record?->status === MarketProduct::STATUS_PUBLISHED
                            ? [MarketProduct::STATUS_PUBLISHED => 'منشور (يبقى منشوراً)'] + self::VENDOR_STATUSES
                            : self::VENDOR_STATUSES)
                        ->default(MarketProduct::STATUS_PENDING)
                        ->required()
                        ->live()
                        ->helperText('المنتجات الجديدة تظهر في المتجر بعد موافقة إدارة المتجر.'),
                    Forms\Components\Textarea::make('description')
                        ->label('وصف المنتج')
                        ->rows(6)->maxLength(5000)
                        ->helperText('اذكر الخامة والمقاس وطريقة الاستخدام.')
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('moderation_note_view')
                        ->label('ملاحظات إدارة المتجر')
                        ->content(fn (?MarketProduct $record) => $record?->moderation_note)
                        ->visible(fn (?MarketProduct $record) => filled($record?->moderation_note))
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('السعر والمخزون')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('السعر (ج.م)')
                        ->numeric()->required()->minValue(1)->maxValue(1000000)->step('0.01'),
                    Forms\Components\TextInput::make('compare_at_price')
                        ->label('السعر قبل الخصم (اختياري)')
                        ->numeric()->minValue(0)->maxValue(1000000)->step('0.01')
                        ->gt('price'),
                    Forms\Components\TextInput::make('stock')
                        ->label('الكمية المتاحة')
                        ->integer()->required()->minValue(0)->maxValue(100000)->default(1),
                ]),

            Forms\Components\Section::make('صور المنتج')
                ->description('أضف حتى 8 صور. يجب كتابة وصف نصي لكل صورة ليقرأه قارئ الشاشة للمكفوفين.')
                ->schema([
                    Forms\Components\Repeater::make('images')
                        ->label('الصور')
                        ->relationship('images')
                        ->orderColumn('sort_order')
                        ->reorderableWithButtons()
                        ->maxItems(8)
                        ->defaultItems(0)
                        ->minItems(fn (Get $get) => $get('status') === MarketProduct::STATUS_DRAFT ? 0 : 1)
                        ->validationMessages(['min' => 'أضف صورة واحدة على الأقل قبل إرسال المنتج للمراجعة.'])
                        ->addActionLabel('إضافة صورة')
                        ->columns(2)
                        ->schema([
                            Forms\Components\FileUpload::make('path')
                                ->label('الصورة')
                                ->image()
                                ->disk(config('marketplace.media_disk', 'public'))
                                ->directory('marketplace/products')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(3072)
                                ->imageResizeMode('contain')
                                ->imageResizeTargetWidth('1600')
                                ->required(),
                            Forms\Components\TextInput::make('alt_text')
                                ->label('وصف الصورة (نص بديل)')
                                ->placeholder('مثال: سجادة يدوية بألوان الأزرق والذهبي على أرضية خشبية')
                                ->helperText('مطلوب لإتاحة المنتج للمكفوفين وضعاف البصر.')
                                ->required()->minLength(3)->maxLength(200),
                        ]),
                ]),

            Forms\Components\Section::make('خيارات المنتج (اختياري)')
                ->description('مثل المقاس أو اللون. سيختار المشتري من هذه الخيارات قبل الشراء.')
                ->collapsible()
                ->collapsed(fn (?MarketProduct $record) => empty($record?->variants))
                ->schema([
                    Forms\Components\Repeater::make('variants')
                        ->label('الخيارات')
                        ->maxItems(3)
                        ->addActionLabel('إضافة خيار')
                        ->columns(2)
                        ->defaultItems(0)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('اسم الخيار')->placeholder('المقاس')
                                ->required()->maxLength(40),
                            Forms\Components\TagsInput::make('options')
                                ->label('القيم المتاحة')->placeholder('اكتب القيمة ثم اضغط Enter')
                                ->required()
                                ->nestedRecursiveRules(['string', 'max:40']),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('primaryImage.path')
                    ->label('الصورة')
                    ->disk(config('marketplace.media_disk', 'public'))
                    ->square()
                    ->extraImgAttributes(fn (MarketProduct $record) => ['alt' => $record->primaryImage?->alt_text ?? $record->name]),
                Tables\Columns\TextColumn::make('name')->label('المنتج')->searchable()->wrap()->weight('bold'),
                Tables\Columns\TextColumn::make('category.name')->label('القسم')->toggleable(),
                Tables\Columns\TextColumn::make('price')->label('السعر')->money('EGP')->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('المخزون')->sortable()
                    ->description(fn (MarketProduct $r) => $r->reserved_stock > 0 ? 'محجوز: '.$r->reserved_stock : null)
                    ->color(fn (MarketProduct $r) => $r->availableStock() < 1 ? 'danger' : null),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => MarketProduct::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => self::statusColor($state)),
                Tables\Columns\TextColumn::make('sales_count')->label('المبيعات')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('rating_avg')->label('التقييم')->toggleable()
                    ->formatStateUsing(fn ($state, MarketProduct $r) => $r->rating_count ? number_format((float) $state, 1).' / 5' : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(MarketProduct::STATUSES),
                Tables\Filters\Filter::make('out_of_stock')->label('نفدت الكمية')
                    ->query(fn (Builder $query) => $query->whereRaw('stock <= reserved_stock')),
            ])
            ->actions([
                Tables\Actions\Action::make('view_public')
                    ->label('عرض في المتجر')
                    ->icon('heroicon-o-eye')
                    ->url(fn (MarketProduct $r) => route('market.products.show', $r->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (MarketProduct $r) => $r->status === MarketProduct::STATUS_PUBLISHED),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('لا توجد منتجات بعد')
            ->emptyStateDescription('ابدأ بإضافة أول منتج لمتجرك.');
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            MarketProduct::STATUS_PUBLISHED => 'success',
            MarketProduct::STATUS_PENDING => 'warning',
            MarketProduct::STATUS_REJECTED => 'danger',
            default => 'gray',
        };
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketProduct::query()->where('vendor_id', MarketplaceAccess::currentVendorId())
            ->where('status', MarketProduct::STATUS_REJECTED)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
