<?php

namespace Modules\Investment;

use App\Support\Modules\ModuleServiceProvider;

/**
 * الاستثمار — investments, projects, plans, Hayah Karima, center guides, investor messages, advertising guide.
 * See docs/architecture.md.
 */
class InvestmentServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Investment';
    }

    protected function movedModels(): array
    {
        return [
            Models\Investment::class,
            Models\InvestmentMessage::class,
            Models\InvestmentPlan::class,
            Models\Project::class,
            Models\HayahKarimaProject::class,
            Models\CenterGuide::class,
            Models\Advertisement::class,
        ];
    }
}
