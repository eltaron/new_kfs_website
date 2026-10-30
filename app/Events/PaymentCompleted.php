<?php

namespace App\Events;

use Modules\Payments\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once (after commit) when a gateway callback confirms a payment.
 */
class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Transaction $transaction)
    {
    }
}
