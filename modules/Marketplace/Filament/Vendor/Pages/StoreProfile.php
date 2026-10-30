<?php

namespace Modules\Marketplace\Filament\Vendor\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * بيانات متجري — the vendor edits public store info and payout details.
 * Identity fields (national id, integrated-services card) are read-only here.
 */
class StoreProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'store-profile';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'بيانات متجري';

    protected static ?string $title = 'بيانات متجري';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'marketplace::filament.vendor.store-profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $vendor = $this->vendor();
        $payout = (array) ($vendor->payout_details ?? []);

        $this->form->fill([
            'store_name' => $vendor->store_name,
            'description' => $vendor->description,
            'logo' => $vendor->logo,
            'phone' => $vendor->phone,
            'center' => $vendor->center,
            'address' => $vendor->address,
            'payout_method' => $vendor->payout_method,
            'wallet_number' => $payout['wallet_number'] ?? null,
            'bank_name' => $payout['bank_name'] ?? null,
            'account_name' => $payout['account_name'] ?? null,
            'account_number' => $payout['account_number'] ?? null,
        ]);
    }

    public function form(Form $form): Form
    {
        $centers = (array) config('marketplace.centers', []);

        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('واجهة المتجر')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('store_name')->label('اسم المتجر')
                            ->required()->minLength(3)->maxLength(120),
                        Forms\Components\FileUpload::make('logo')->label('شعار المتجر')
                            ->image()->avatar()
                            ->disk(config('marketplace.media_disk', 'public'))
                            ->directory('marketplace/logos')->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048),
                        Forms\Components\Textarea::make('description')->label('نبذة عن المتجر')
                            ->rows(5)->maxLength(2000)->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('بيانات التواصل')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('phone')->label('رقم الهاتف')->tel()
                            ->required()->regex('/^01[0125][0-9]{8}$/'),
                        Forms\Components\Select::make('center')->label('المركز')
                            ->options(array_combine($centers, $centers))->required(),
                        Forms\Components\TextInput::make('address')->label('العنوان')->maxLength(255),
                    ]),
                Forms\Components\Section::make('استلام الأرباح')
                    ->description('تُحوَّل أرباحك إلى هذه الوسيلة. لا تظهر هذه البيانات لأي مشترٍ.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Radio::make('payout_method')->label('طريقة الاستلام')
                            ->options(MarketVendor::PAYOUT_METHODS)->required()->live()->columnSpanFull(),
                        Forms\Components\TextInput::make('wallet_number')->label('رقم المحفظة')
                            ->visible(fn (Get $get) => $get('payout_method') === 'wallet')
                            ->required(fn (Get $get) => $get('payout_method') === 'wallet')
                            ->regex('/^01[0125][0-9]{8}$/'),
                        Forms\Components\TextInput::make('bank_name')->label('اسم البنك')
                            ->visible(fn (Get $get) => $get('payout_method') === 'bank')
                            ->required(fn (Get $get) => $get('payout_method') === 'bank')->maxLength(100),
                        Forms\Components\TextInput::make('account_name')->label('اسم صاحب الحساب')
                            ->visible(fn (Get $get) => $get('payout_method') === 'bank')
                            ->required(fn (Get $get) => $get('payout_method') === 'bank')->maxLength(120),
                        Forms\Components\TextInput::make('account_number')->label('رقم الحساب / IBAN')
                            ->visible(fn (Get $get) => $get('payout_method') === 'bank')
                            ->required(fn (Get $get) => $get('payout_method') === 'bank')
                            ->maxLength(34)->regex('/^[A-Za-z0-9 ]+$/'),
                    ]),
                Forms\Components\Section::make('بيانات التحقق')
                    ->description('لتعديل هذه البيانات تواصل مع إدارة المتجر.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Placeholder::make('status')->label('حالة المتجر')
                            ->content(fn () => $this->vendor()->statusLabel()),
                        Forms\Components\Placeholder::make('card')->label('رقم بطاقة الخدمات المتكاملة')
                            ->content(fn () => '••••'.mb_substr((string) $this->vendor()->disability_card_number, -4)),
                        Forms\Components\Placeholder::make('commission')->label('عمولة المتجر على مبيعاتك')
                            ->content(fn () => rtrim(rtrim($this->vendor()->effectiveCommissionRate(), '0'), '.').'%'),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('حفظ التعديلات')->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $vendor = $this->vendor();

        $payout = $data['payout_method'] === 'wallet'
            ? ['wallet_number' => $data['wallet_number']]
            : ['bank_name' => $data['bank_name'], 'account_name' => $data['account_name'], 'account_number' => $data['account_number']];

        $vendor->fill([
            'store_name' => $data['store_name'],
            'description' => filled($data['description'] ?? null) ? strip_tags((string) $data['description']) : null,
            'logo' => $data['logo'] ?? null,
            'phone' => $data['phone'],
            'center' => $data['center'],
            'address' => $data['address'] ?? null,
            'payout_method' => $data['payout_method'],
            'payout_details' => $payout,
        ])->save();

        Notification::make()->success()->title('تم حفظ بيانات المتجر.')->send();
    }

    private function vendor(): MarketVendor
    {
        $vendor = MarketplaceAccess::currentVendor();
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
