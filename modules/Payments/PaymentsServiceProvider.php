<?php

namespace Modules\Payments;

use App\Support\Modules\ModuleServiceProvider;

/**
 * المدفوعات — unified e-Finance payments, transactions, fulfillment fees, receipts (published contract: App\Contracts\Payable + App\Concerns\HasPayments).
 * See docs/architecture.md.
 */
class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Payments';
    }

    protected function movedModels(): array
    {
        return [
            Models\Transaction::class,
            Models\FulfillmentFee::class,
        ];
    }
}
