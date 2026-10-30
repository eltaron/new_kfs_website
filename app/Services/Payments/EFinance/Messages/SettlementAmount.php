<?php

namespace App\Services\Payments\EFinance\Messages;

final class SettlementAmount
{
    public function __construct(
        public string $settlementAccountCode,
        public string $amount,
        public string $description = '',
    ) {
    }
}
