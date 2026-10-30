<?php

namespace App\Services\Payments;

use Modules\Payments\Models\Transaction;

final class CallbackResult
{
    public const COMPLETED = 'completed';      // payment confirmed now
    public const FAILED = 'failed';            // gateway reported failure / verification failed
    public const DUPLICATE = 'duplicate';      // already processed (idempotent replay)
    public const IGNORED = 'ignored';          // unknown / unverifiable request, nothing changed

    public function __construct(
        public readonly string $outcome,
        public readonly ?Transaction $transaction = null,
        public readonly ?string $reason = null,
    ) {
    }
}
