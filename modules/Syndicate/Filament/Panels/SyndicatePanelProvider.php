<?php

namespace Modules\Syndicate\Filament\Panels;

use Filament\Panel;

/** لوحة موظفي نقابة المهندسين: /syndicate */
class SyndicatePanelProvider extends BasePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->configure($panel, 'syndicate', 'بوابة نقابة المهندسين — كفر الشيخ');
    }
}
