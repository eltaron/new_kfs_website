<?php

namespace Modules\Syndicate\Filament\Panels;

use App\Support\Filament\KfsBranding;
use Filament\Panel;

/** لوحة مجلس المدينة / الوحدات المحلية: /council */
class CouncilPanelProvider extends BasePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->configure($panel, 'council', 'مجلس المدينة — منظومة الإشراف الهندسي', KfsBranding::GOLD);
    }
}
