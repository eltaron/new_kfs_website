<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;

class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire-pending';

    protected $description = 'Mark abandoned pending e-Finance payment attempts (past expires_at) as expired';

    public function handle(PaymentService $payments): int
    {
        $count = $payments->expirePending();

        $this->info("Expired {$count} pending payment(s).");

        return self::SUCCESS;
    }
}
