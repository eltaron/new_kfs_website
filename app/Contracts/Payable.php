<?php

namespace App\Contracts;

use Modules\Payments\Models\Transaction;
use App\Models\User;

/**
 * Anything a citizen can pay for through the unified e-Finance gateway.
 *
 * Implementations should `use \App\Concerns\HasPayments` and be registered
 * in the morph map (see App\Providers\PaymentServiceProvider) so that
 * route('payments.pay', ['type' => <alias>, 'id' => <key>]) can resolve them.
 */
interface Payable
{
    /** Amount due, server-side computed, as a decimal string with 2 places (e.g. "125.50"). */
    public function payableAmount(): string;

    /** Short Arabic description shown on the gateway, receipts and reports. */
    public function payableDescription(): string;

    /**
     * The user who owns (and may pay) this item. Null for guest payables
     * (e.g. tour company bookings) which are paid through a signed link.
     */
    public function payableOwner(): ?User;

    /** Called (inside the callback DB transaction, row locked) once the gateway confirms payment. */
    public function markPaid(Transaction $transaction): void;

    /** Called when the gateway reports a failed attempt. Must never undo an existing paid state. */
    public function markPaymentFailed(Transaction $transaction): void;

    /** Where the citizen is sent after viewing the payment result. */
    public function paymentReturnUrl(Transaction $transaction): string;
}
