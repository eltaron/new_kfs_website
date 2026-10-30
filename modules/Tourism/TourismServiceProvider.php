<?php

namespace Modules\Tourism;

use App\Support\Modules\ModuleServiceProvider;

/**
 * السياحة — landmarks, locations, landmark/tour bookings, city guide.
 * See docs/architecture.md.
 */
class TourismServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Tourism';
    }

    protected function movedModels(): array
    {
        return [
            Models\Landmark::class,
            Models\Location::class,
            Models\Booking::class,
            Models\TourBooking::class,
            Models\CityGuideCategory::class,
            Models\CityVillage::class,
        ];
    }
}
