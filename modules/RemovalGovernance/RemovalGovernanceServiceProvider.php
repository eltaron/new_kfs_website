<?php

namespace Modules\RemovalGovernance;

use App\Support\Modules\ModuleServiceProvider;

/**
 * حوكمة الإزالات — removal orders, their approval workflow and spatial exports.
 * See docs/architecture.md.
 */
class RemovalGovernanceServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'RemovalGovernance';
    }

    protected function movedModels(): array
    {
        return [
            Models\RemovalOrder::class,
        ];
    }
}
