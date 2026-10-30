<?php

namespace App\Concerns;

use Modules\Payments\Models\Transaction;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\URL;

/**
 * Default plumbing for App\Contracts\Payable models.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPayments
{
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function completedTransaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable')
            ->where('status', Transaction::STATUS_COMPLETED)
            ->latestOfMany('completed_at');
    }

    /** Models with their own payment column override this. */
    public function isPaid(): bool
    {
        return $this->transactions()->where('status', Transaction::STATUS_COMPLETED)->exists();
    }

    /** Whether a new payment may be started right now. Models may add business rules. */
    public function canBePaid(): bool
    {
        return ! $this->isPaid() && Money::isPositive($this->payableAmount());
    }

    /** The morph alias used in payment URLs (e.g. "service_submission"). */
    public function payableType(): string
    {
        return $this->getMorphClass();
    }

    /**
     * Link that starts the payment. Guest payables (no owner) get a signed
     * URL so only whoever received the link can pay.
     */
    public function paymentUrl(): string
    {
        $params = ['type' => $this->payableType(), 'id' => $this->getKey()];

        return $this->payableOwner() === null
            ? URL::signedRoute('payments.pay', $params)
            : route('payments.pay', $params);
    }

    /**
     * Signed status page (description, amount, state, pay button) — the
     * follow-up link handed to guests such as tour companies.
     */
    public function checkoutUrl(): string
    {
        return URL::signedRoute('payments.checkout', ['type' => $this->payableType(), 'id' => $this->getKey()]);
    }

}
