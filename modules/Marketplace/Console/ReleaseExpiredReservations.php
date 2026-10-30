<?php

namespace Modules\Marketplace\Console;

use Illuminate\Console\Command;
use Modules\Marketplace\Services\CheckoutService;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'market:release-reservations';

    protected $description = 'Release stock held by unpaid marketplace orders whose reservation expired';

    public function handle(CheckoutService $checkout): int
    {
        $count = $checkout->releaseExpiredReservations();
        $this->info("Released {$count} expired reservation(s).");

        return self::SUCCESS;
    }
}
