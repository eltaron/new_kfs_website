<?php

namespace Modules\Content;

use App\Support\Modules\ModuleServiceProvider;

/**
 * المحتوى — news, posts/events, officials, governorate data, settings, hero slider,
 * statistics, partners, directory, search, exam results, reference geography.
 * See docs/architecture.md.
 */
class ContentServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Content';
    }

    protected function movedModels(): array
    {
        return [
            Models\Post::class,
            Models\Comment::class,
            Models\Category::class,
            Models\Official::class,
            Models\OfficialRole::class,
            Models\GovernorateDetail::class,
            Models\Setting::class,
            Models\HeroSlider::class,
            Models\Statistic::class,
            Models\Partner::class,
            Models\DirectoryEntry::class,
            Models\FamousPeople::class,
            Models\Image::class,
            Models\Employee::class,
            Models\Center::class,
            Models\Markaz::class,
            Models\Village::class,
            Models\Shiakha::class,
            Models\ExamResult::class,
        ];
    }
}
