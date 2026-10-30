<?php

namespace Modules\Marketplace\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Marketplace\Support\MarketplaceAccess;
use Modules\Marketplace\Support\MarketSettings;

/**
 * إعدادات المتجر — commission, shipping fee, cash-on-delivery switch.
 */
class MarketSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'settings';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'إعدادات المتجر';

    protected static ?string $title = 'إعدادات المتجر';

    protected static string $view = 'marketplace::filament.admin.settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return MarketplaceAccess::isAdmin(auth()->user());
    }

    public function mount(): void
    {
        $this->form->fill([
            'default_commission_rate' => MarketSettings::commissionRate(),
            'shipping_fee_per_vendor' => MarketSettings::shippingFeePerVendor(),
            'cod_enabled' => MarketSettings::codEnabled(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('العمولة والشحن')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('default_commission_rate')
                            ->label('نسبة العمولة العامة (%)')
                            ->helperText('الافتراضي 0% دعماً للبائعين من ذوي الهمم. يمكن تحديد نسبة خاصة لكل بائع من صفحته.')
                            ->numeric()->required()->minValue(0)->maxValue(100)->step('0.01'),
                        Forms\Components\TextInput::make('shipping_fee_per_vendor')
                            ->label('رسوم الشحن لكل بائع في الطلب (ج.م)')
                            ->numeric()->required()->minValue(0)->maxValue(10000)->step('0.01'),
                    ]),
                Forms\Components\Section::make('طرق الدفع')
                    ->schema([
                        Forms\Components\Toggle::make('cod_enabled')
                            ->label('السماح بالدفع عند الاستلام')
                            ->helperText('الدفع الإلكتروني عبر e-Finance متاح دائماً.'),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('حفظ الإعدادات')->submit('save')];
    }

    public function save(): void
    {
        abort_unless(MarketplaceAccess::isAdmin(auth()->user()), 403);
        $data = $this->form->getState();

        MarketSettings::set('default_commission_rate', number_format((float) $data['default_commission_rate'], 2, '.', ''));
        MarketSettings::set('shipping_fee_per_vendor', number_format((float) $data['shipping_fee_per_vendor'], 2, '.', ''));
        MarketSettings::set('cod_enabled', ! empty($data['cod_enabled']) ? '1' : '0');

        Notification::make()->success()->title('تم حفظ الإعدادات. تُطبَّق على الطلبات الجديدة فقط.')->send();
    }
}
