<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Payment errors whose message is safe (Arabic) to show to the citizen.
 * Technical details go to the log via $context.
 */
class PaymentException extends RuntimeException
{
    public function __construct(string $message, public readonly array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
