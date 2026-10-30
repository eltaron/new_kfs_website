<?php

namespace Modules\Marketplace\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Filament\Admin\Resources\ProductResource\Pages;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource as VendorProductResource;
use Modules\Marketplace\Models\MarketCategory;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Services\OrderNotifier;

/**
 * مراجعة المنتجات — publish / reject vendor products, feature them, fix categories.
 */
class ProductResource extends Resource
{
    protected static ?string $model = MarketProduct::class;

    protected static ?string $slug = 'products';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'البائعون والمنتجات';

    protected static ?string $modelLabel = 'منتج';

    protected static ?string $pluralModelLabel = 'المنتجات';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['vendor', 'category', 'primaryImage']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('المراجعة')
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('vendor')->label('البائع')
                        ->content(fn (?MarketProduct $record) => $record?->vendor?->store_name),
                    Forms\Components\Select::make('status')->label('الحالة')->options(MarketProduct::STATUSES)->required(),
                    Forms\Components\Select::make('category_id')->label('القسم')
                        ->options(fn () => MarketCategory::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
                    Forms\Components\Toggle::make('is_featured')->label('منتج مميز في الصفحة الرئيسية'),
                    Forms\Components\Textarea::make('moderation_note')->label('ملاحظات للبائع')->rows(3)->maxLength(1000)->columnSpanFull(),
                ]),
            Forms\Components\Section::make('المحتوى')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('الاسم')->required()->maxLength(150)->columnSpanFull(),
                    Forms\Components\Textarea::make('description')->label('الوصف')->rows(5)->maxLength(5000)->columnSpanFull(),
                    Forms\Components\TextInput::make('price')->label('السعر')->numeric()->required()->minValue(1),
                    Forms\Components\TextInput::make('stock')->label('المخزون')->integer()->required()->minValue(0),
                    Forms\Components\Repeater::make('images')->label('الصور')
                        ->relationship('images')->orderColumn('sort_order')
                        ->columns(2)->columnSpanFull()
                        ->schema([
                            Forms\Components\FileUpload::make('path')->label('الصورة')->image()
                                ->disk(config('marketplace.media_disk', 'public'))->directory('marketplace/products')
                                ->visibility('public')->maxSize(3072)->required(),
                            Forms\Components\TextInput::make('alt_text')->label('النص البديل')
                                ->required()->minLength(3)->maxLength(200),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('primaryImage.path')->label('الصورة')->square()
                    ->disk(config('marketplace.media_disk', 'public'))
                    ->extraImgAttributes(fn (MarketProduct $r) => ['alt' => $r->primaryImage?->alt_text ?? $r->name]),
                Tables\Columns\TextColumn::make('name')->label('المنتج')->searchable()->wrap()->weight('bold'),
                Tables\Columns\TextColumn::make('vendor.store_name')->label('البائع')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('القسم')->toggleable(),
                Tables\Columns\TextColumn::make('price')->label('السعر')->money('EGP')->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('المخزون')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => MarketProduct::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => VendorProductResource::statusColor($state)),
                Tables\Columns\IconColumn::make('is_featured')->label('مميز')->boolean(),
                Tables\Columns\TextColumn::make('sales_count')->label('المبيعات')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(MarketProduct::STATUSES),
                Tables\Filters\SelectFilter::make('vendor_id')->label('البائع')
                    ->options(fn () => MarketVendor::query()->orderBy('store_name')->pluck('store_name', 'id'))->searchable(),
                Tables\Filters\SelectFilter::make('category_id')->label('القسم')
                    ->options(fn () => MarketCategory::query()->orderBy('name')->pluck('name', 'id')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('publish')
                    ->label('نشر')->icon('heroicon-o-check')->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (MarketProduct $r) => $r->status !== MarketProduct::STATUS_PUBLISHED && ! $r->trashed())
                    ->action(fn (MarketProduct $r) => self::moderate($r, MarketProduct::STATUS_PUBLISHED, null)),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')->icon('heroicon-o-x-mark')->color('danger')
                    ->form([Forms\Components\Textarea::make('note')->label('سبب الرفض / التعديلات المطلوبة')->required()->maxLength(1000)])
                    ->visible(fn (MarketProduct $r) => in_array($r->status, [MarketProduct::STATUS_PENDING, MarketProduct::STATUS_PUBLISHED], true) && ! $r->trashed())
                    ->action(fn (MarketProduct $r, array $data) => self::moderate($r, MarketProduct::STATUS_REJECTED, $data['note'])),
                Tables\Actions\Action::make('preview')
                    ->label('عرض')->icon('heroicon-o-eye')->color('gray')
                    ->url(fn (MarketProduct $r) => route('market.products.show', $r->slug))->openUrlInNewTab()
                    ->visible(fn (MarketProduct $r) => $r->status === MarketProduct::STATUS_PUBLISHED),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_publish')
                    ->label('نشر المحدد')->icon('heroicon-o-check')->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(fn (MarketProduct $r) => $r->status !== MarketProduct::STATUS_PUBLISHED
                            ? self::moderate($r, MarketProduct::STATUS_PUBLISHED, null, notify: false) : null);
                        Notification::make()->success()->title('تم نشر المنتجات المحددة.')->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function moderate(MarketProduct $product, string $status, ?string $note, bool $notify = true): void
    {
        abort_unless(Gate::allows('moderate', $product), 403);

        $product->forceFill(['status' => $status, 'moderation_note' => $note])->save();
        app(OrderNotifier::class)->productModerated($product);

        if ($notify) {
            Notification::make()->success()
                ->title($status === MarketProduct::STATUS_PUBLISHED ? 'تم نشر المنتج وإبلاغ البائع.' : 'تم رفض المنتج وإبلاغ البائع بالسبب.')
                ->send();
        }
    }

    public static function canCreate(): bool
    {
        return false; // products are added by vendors
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketProduct::query()->where('status', MarketProduct::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
