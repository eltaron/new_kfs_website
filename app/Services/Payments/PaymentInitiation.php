<?php

namespace App\Services\Payments;

use Modules\Payments\Models\Transaction;

final class PaymentInitiation
{
    public function __construct(
        public readonly Transaction $transaction,
        public readonly GatewayRedirect $redirect,
    ) {
    }
}
