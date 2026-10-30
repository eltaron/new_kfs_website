<?php

namespace Modules\Gis;

use App\Support\Modules\ModuleServiceProvider;

/**
 * نظم المعلومات الجغرافية — spatial services portal, GIS submissions/invoices, interactive map.
 * See docs/architecture.md.
 */
class GisServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Gis';
    }

    protected function movedModels(): array
    {
        return [
            Models\GisMarkaz::class,
            Models\GisReport::class,
            Models\GisServiceType::class,
            Models\GisShiakha::class,
            Models\GisStudyReason::class,
            Models\GisSubmission::class,
            Models\GisSubService::class,
            Models\GisVillage::class,
        ];
    }
}
