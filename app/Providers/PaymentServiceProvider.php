<?php

namespace App\Providers;

use Modules\Tourism\Models\Booking;
use Modules\Payments\Models\FulfillmentFee;
use Modules\Gis\Models\GisSubmission;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Tourism\Models\TourBooking;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\EFinance\EFinanceGateway;
use App\Services\Payments\Fake\FakeGateway;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the unified payment layer:
 *  - binds the gateway driver from config('services.efinance.driver')
 *  - registers morph aliases for every Payable (also the {type} of
 *    route('payments.pay')). Modules add their own aliases the same way.
 */
class PaymentServiceProvider extends ServiceProvider
{
    public const PAYABLES = [
        'service_submission' => ServiceSubmission::class,
        'gis_submission' => GisSubmission::class,
        'fulfillment_fee' => FulfillmentFee::class,
        'landmark_booking' => Booking::class,
        'tour_booking' => TourBooking::class,
        'removal_order' => RemovalOrder::class,
    ];

    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function () {
            $driver = config('services.efinance.driver', 'efinance');

            // SECURITY: the fake gateway "verifies" callbacks with a secret that is
            // public in this repo, so it must never be reachable in production.
            if ($driver === 'fake' && $this->app->environment('production')) {
                \Illuminate\Support\Facades\Log::critical('EFINANCE_DRIVER=fake ignored in production; using e-Finance.');
                $driver = 'efinance';
            }

            return match ($driver) {
                'fake' => new FakeGateway(),
                default => new EFinanceGateway(),
            };
        });
    }

    public function boot(): void
    {
        Relation::morphMap(self::PAYABLES);
    }
}
