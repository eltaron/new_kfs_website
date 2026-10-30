<?php

namespace Modules\Marketplace\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Filament\Admin\Resources\VendorResource\Pages;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Services\OrderNotifier;

/**
 * البائعون — review applications (integrated-services card), approve / reject /
 * suspend, and set a per-vendor commission override.
 */
class VendorResource extends Resource
{
    protected static ?string $model = MarketVendor::class;

    protected static ?string $slug = 'vendors';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'البائعون والمنتجات';

    protected static ?string $modelLabel = 'بائع';

    protected static ?string $pluralModelLabel = 'البائعون';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'store_name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user')->withCount('products');
    }

    public static function form(Form $form): Form
    {
        $centers = (array) config('marketplace.centers', []);

        return $form->schema([
            Forms\Components\Section::make('بيانات المتجر')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('store_name')->label('اسم المتجر')->required()->maxLength(120),
                    Forms\Components\TextInput::make('slug')->label('الرابط المختصر')->required()->maxLength(140)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')->label('النبذة')->rows(4)->maxLength(2000)->columnSpanFull(),
                    Forms\Components\TextInput::make('phone')->label('الهاتف')->required()->maxLength(20),
                    Forms\Components\Select::make('center')->label('المركز')->options(array_combine($centers, $centers)),
                    Forms\Components\TextInput::make('address')->label('العنوان')->maxLength(255)->columnSpanFull(),
                ]),
            Forms\Components\Section::make('العمولة')
                ->schema([
                    Forms\Components\TextInput::make('commission_rate')
                        ->label('نسبة عمولة خاصة بهذا البائع (%)')
                        ->helperText('اتركها فارغة لتطبيق النسبة العامة من الإعدادات (الافتراضي 0% لذوي الهمم).')
                        ->numeric()->minValue(0)->maxValue(100)->step('0.01'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->label('الشعار')->circular()
                    ->disk(config('marketplace.media_disk', 'public'))
                    ->extraImgAttributes(fn (MarketVendor $r) => ['alt' => 'شعار '.$r->store_name]),
                Tables\Columns\TextColumn::make('store_name')->label('المتجر')->searchable()->weight('bold')
                    ->description(fn (MarketVendor $r) => $r->user?->name),
                Tables\Columns\TextColumn::make('center')->label('المركز')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('products_count')->label('المنتجات'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (string $state) => MarketVendor::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => self::statusColor($state)),
                Tables\Columns\TextColumn::make('commission_rate')->label('العمولة')
                    ->formatStateUsing(fn ($state) => $state === null ? 'عامة' : $state.'%')
                    ->placeholder('عامة'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الطلب')->date('Y-m-d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(MarketVendor::STATUSES),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('مراجعة'),
                ...self::reviewActions(Tables\Actions\Action::class),
            ]);
    }

    /**
     * Approve / reject / suspend / reactivate actions (shared by table + view page).
     *
     * @param  class-string<\Filament\Actions\Action|\Filament\Tables\Actions\Action>  $actionClass
     */
    public static function reviewActions(string $actionClass): array
    {
        return [
            $actionClass::make('approve')
                ->label('اعتماد')->icon('heroicon-o-check-badge')->color('success')
                ->requiresConfirmation()
                ->modalHeading('اعتماد المتجر')
                ->modalDescription('تأكد من مراجعة بطاقة الخدمات المتكاملة قبل الاعتماد.')
                ->visible(fn (MarketVendor $record) => in_array($record->status, [MarketVendor::STATUS_PENDING, MarketVendor::STATUS_REJECTED], true)
                    && Gate::allows('review', $record))
                ->action(fn (MarketVendor $record) => self::changeStatus($record, MarketVendor::STATUS_APPROVED, null)),
            $actionClass::make('reject')
                ->label('رفض')->icon('heroicon-o-x-circle')->color('danger')
                ->form([Forms\Components\Textarea::make('notes')->label('سبب الرفض (يظهر للمتقدم)')->required()->maxLength(1000)])
                ->visible(fn (MarketVendor $record) => $record->status === MarketVendor::STATUS_PENDING && Gate::allows('review', $record))
                ->action(fn (MarketVendor $record, array $data) => self::changeStatus($record, MarketVendor::STATUS_REJECTED, $data['notes'])),
            $actionClass::make('suspend')
                ->label('إيقاف')->icon('heroicon-o-pause-circle')->color('warning')
                ->form([Forms\Components\Textarea::make('notes')->label('سبب الإيقاف (يظهر للبائع)')->required()->maxLength(1000)])
                ->visible(fn (MarketVendor $record) => $record->status === MarketVendor::STATUS_APPROVED && Gate::allows('review', $record))
                ->action(fn (MarketVendor $record, array $data) => self::changeStatus($record, MarketVendor::STATUS_SUSPENDED, $data['notes'])),
            $actionClass::make('reactivate')
                ->label('إعادة التفعيل')->icon('heroicon-o-play-circle')->color('success')
                ->requiresConfirmation()
                ->visible(fn (MarketVendor $record) => $record->status === MarketVendor::STATUS_SUSPENDED && Gate::allows('review', $record))
                ->action(fn (MarketVendor $record) => self::changeStatus($record, MarketVendor::STATUS_APPROVED, null)),
        ];
    }

    public static function changeStatus(MarketVendor $vendor, string $status, ?string $notes): void
    {
        abort_unless(Gate::allows('review', $vendor), 403);

        DB::transaction(function () use ($vendor, $status, $notes) {
            $vendor->forceFill([
                'status' => $status,
                'review_notes' => $notes,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ])->save();
            app(OrderNotifier::class)->vendorStatusChanged($vendor);
        });

        Notification::make()->success()
            ->title('تم تحديث حالة المتجر إلى: '.$vendor->statusLabel().' وإبلاغ صاحبه.')
            ->send();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('المتجر')
                ->columns(3)
                ->schema([
                    Infolists\Components\ImageEntry::make('logo')->label('الشعار')->circular()
                        ->disk(config('marketplace.media_disk', 'public'))->placeholder('—'),
                    Infolists\Components\TextEntry::make('store_name')->label('اسم المتجر'),
                    Infolists\Components\TextEntry::make('status')->label('الحالة')->badge()
                        ->formatStateUsing(fn (string $state) => MarketVendor::STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => self::statusColor($state)),
                    Infolists\Components\TextEntry::make('description')->label('النبذة')->placeholder('—')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('center')->label('المركز')->placeholder('—'),
                    Infolists\Components\TextEntry::make('address')->label('العنوان')->placeholder('—'),
                    Infolists\Components\TextEntry::make('phone')->label('الهاتف'),
                ]),
            Infolists\Components\Section::make('بيانات التحقق (سرية)')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label('اسم صاحب الحساب'),
                    Infolists\Components\TextEntry::make('user.email')->label('البريد الإلكتروني')->placeholder('—'),
                    Infolists\Components\TextEntry::make('national_id')->label('الرقم القومي')
                        ->state(fn (MarketVendor $r) => $r->national_id),
                    Infolists\Components\TextEntry::make('disability_card_number')->label('رقم بطاقة الخدمات المتكاملة')
                        ->state(fn (MarketVendor $r) => $r->disability_card_number),
                    Infolists\Components\TextEntry::make('card_file')->label('صورة البطاقة')
                        ->state(fn (MarketVendor $r) => $r->disability_card_path ? 'عرض المستند' : 'لم يُرفع مستند')
                        ->url(fn (MarketVendor $r) => $r->disability_card_path ? route('market.vendors.document', $r->getKey()) : null)
                        ->openUrlInNewTab()
                        ->color('primary'),
                    Infolists\Components\TextEntry::make('payout')->label('وسيلة استلام الأرباح')
                        ->state(fn (MarketVendor $r) => $r->maskedPayout()),
                ]),
            Infolists\Components\Section::make('المراجعة')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('reviewer.name')->label('راجعه')->placeholder('—'),
                    Infolists\Components\TextEntry::make('reviewed_at')->label('تاريخ المراجعة')->dateTime('Y-m-d H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('review_notes')->label('ملاحظات')->placeholder('—'),
                    Infolists\Components\TextEntry::make('commission')->label('العمولة المطبقة')
                        ->state(fn (MarketVendor $r) => $r->effectiveCommissionRate().'%'),
                ]),
        ]);
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            MarketVendor::STATUS_APPROVED => 'success',
            MarketVendor::STATUS_PENDING => 'warning',
            default => 'danger',
        };
    }

    public static function canCreate(): bool
    {
        return false; // citizens apply from /store/sell
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketVendor::query()->where('status', MarketVendor::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'view' => Pages\ViewVendor::route('/{record}'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
