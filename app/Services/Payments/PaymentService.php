<?php

namespace App\Services\Payments;

use App\Contracts\Payable;
use App\Events\PaymentCompleted;
use Modules\Gis\Models\GisSubmission;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Payments\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\EFinance\EFinanceConfig;
use App\Services\Payments\EFinance\LegacyPgwReader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The single entry point for charging money on the portal.
 *
 *   initiate(Payable)        -> pending Transaction + gateway hand-off
 *   handleCallback($input)   -> verifies + settles the Transaction (idempotent)
 *   expirePending()          -> housekeeping for abandoned attempts
 */
class PaymentService
{
    private const MAX_SECRETS = 5;

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PayableResolver $resolver,
        private readonly LegacyPgwReader $legacy,
    ) {
    }

    public function gateway(): PaymentGateway
    {
        return $this->gateway;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Initiation
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param  Model&Payable  $payable
     *
     * @throws PaymentException
     */
    public function initiate(Payable $payable): PaymentInitiation
    {
        /** @var Model&Payable $payable */
        $type = $payable->getMorphClass();

        if ($this->resolver->classFor($type) === null) {
            throw new PaymentException('نوع العنصر المطلوب دفعه غير مدعوم.', ['type' => $type]);
        }

        try {
            return DB::transaction(function () use ($payable, $type) {
                /** @var Model&Payable|null $locked */
                $locked = $this->resolver->find($type, $payable->getKey(), lock: true);

                if ($locked === null) {
                    throw new PaymentException('الطلب غير موجود.');
                }
                if (method_exists($locked, 'canBePaid') && ! $locked->canBePaid()) {
                    throw new PaymentException('لا يمكن دفع هذا الطلب الآن (قد يكون مدفوعاً مسبقاً أو لا توجد رسوم مستحقة).');
                }

                $amount = Money::format($locked->payableAmount());
                if (! Money::isPositive($amount)) {
                    throw new PaymentException('لا توجد رسوم مستحقة على هذا الطلب.');
                }

                $transaction = $this->reusableTransaction($locked, $amount) ?? $this->newTransaction($locked, $amount);

                $redirect = $this->gateway->initiate($transaction, [
                    'confirmation_url' => route('efinance.callback'),
                    'redirect_url' => route('payments.return', $transaction),
                    'description' => $transaction->description,
                    'server_ip' => EFinanceConfig::fromConfig()->serverIp(),
                ]);

                if ($redirect->secret !== null) {
                    $secrets = array_values(array_filter((array) ($transaction->aes_secret ?? []), 'is_string'));
                    $secrets[] = $redirect->secret;
                    $transaction->aes_secret = array_slice(array_values(array_unique($secrets)), -self::MAX_SECRETS);
                }
                $transaction->payment_gateway = $this->gateway->name();
                $transaction->save();

                return new PaymentInitiation($transaction, $redirect);
            });
        } catch (PaymentException $e) {
            Log::warning('Payment initiation refused', ['type' => $type, 'id' => $payable->getKey(), 'error' => $e->getMessage()] + $e->context);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Payment initiation failed', ['type' => $type, 'id' => $payable->getKey(), 'error' => $e->getMessage()]);
            throw new PaymentException('تعذر الاتصال ببوابة الدفع حالياً، يرجى المحاولة لاحقاً.', [], $e);
        }
    }

    /** An open (pending, unexpired) attempt for the same amount is reused instead of piling up rows. */
    private function reusableTransaction(Model $payable, string $amount): ?Transaction
    {
        $open = Transaction::query()
            ->where('transactionable_type', $payable->getMorphClass())
            ->where('transactionable_id', (string) $payable->getKey())
            ->where('status', Transaction::STATUS_PENDING)
            ->lockForUpdate()
            ->orderByDesc('created_at')
            ->get();

        $reuse = null;
        foreach ($open as $transaction) {
            $usable = $reuse === null
                && Money::equals($transaction->amount, $amount)
                && ($transaction->expires_at === null || $transaction->expires_at->isFuture());

            if ($usable) {
                $reuse = $transaction;
                continue;
            }

            // Stale or amount changed: close it so it can no longer be settled silently.
            $transaction->forceFill([
                'status' => Transaction::STATUS_EXPIRED,
                'failure_reason' => 'superseded',
                'failed_at' => now(),
            ])->save();
        }

        return $reuse;
    }

    private function newTransaction(Model&Payable $payable, string $amount): Transaction
    {
        return Transaction::create([
            'user_id' => $payable->payableOwner()?->getKey(),
            'transactionable_type' => $payable->getMorphClass(),
            'transactionable_id' => (string) $payable->getKey(),
            'amount' => $amount,
            'description' => Str::limit($payable->payableDescription(), 250, ''),
            'status' => Transaction::STATUS_PENDING,
            'payment_gateway' => $this->gateway->name(),
            'sender_request_number' => $this->newSenderRequestNumber(),
            'expires_at' => now()->addHours((int) config('services.efinance.expiry_hours', 24)),
        ]);
    }

    private function newSenderRequestNumber(): string
    {
        do {
            $srn = bin2hex(random_bytes(8)); // 16 hex chars, same shape as the legacy generator
        } while (Transaction::where('sender_request_number', $srn)->exists());

        return $srn;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Silent callback
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed>  $input  raw POST fields from the gateway
     */
    public function handleCallback(array $input): CallbackResult
    {
        $srn = trim((string) ($input['SenderRequestNumber'] ?? ''));
        $requestObject = (string) ($input['RequestObject'] ?? '');
        $postedSender = trim((string) ($input['SenderID'] ?? ''));

        if ($srn === '' || $requestObject === '' || ! LegacyPgwReader::isValidSenderRequestNumber($srn)) {
            Log::warning('Payment callback: missing/invalid fields', ['srn' => Str::limit($srn, 70)]);

            return new CallbackResult(CallbackResult::IGNORED, null, 'invalid_request');
        }

        if ($postedSender === '' || ! hash_equals($this->gateway->senderId(), $postedSender)) {
            Log::warning('Payment callback: SenderID mismatch', ['srn' => $srn, 'sender' => Str::limit($postedSender, 40)]);

            return new CallbackResult(CallbackResult::IGNORED, null, 'sender_mismatch');
        }

        $transaction = Transaction::where('sender_request_number', $srn)->first() ?? $this->importLegacyOrder($srn);
        if ($transaction === null) {
            Log::warning('Payment callback: unknown SenderRequestNumber', ['srn' => $srn]);

            return new CallbackResult(CallbackResult::IGNORED, null, 'unknown_transaction');
        }

        $result = DB::transaction(function () use ($transaction, $requestObject, $input, $srn) {
            /** @var Transaction $tx */
            $tx = Transaction::whereKey($transaction->getKey())->lockForUpdate()->first();

            if ($tx->status === Transaction::STATUS_COMPLETED) {
                return new CallbackResult(CallbackResult::DUPLICATE, $tx, 'already_completed');
            }

            try {
                $payload = $this->gateway->decryptCallback($requestObject, (array) ($tx->aes_secret ?? []));
            } catch (PaymentException $e) {
                // Could be forged: never change state on an unverifiable payload.
                Log::warning('Payment callback: payload could not be decrypted', ['transaction' => $tx->id] + $e->context);

                return new CallbackResult(CallbackResult::IGNORED, $tx, 'decrypt_failed');
            }

            $innerSrn = $this->pick($payload, 'SenderRequestNumber');
            $innerSender = $this->pick($payload, 'Id') ?? $this->pick($payload, 'SenderID');
            if (($innerSrn !== null && $innerSrn !== $srn) || ($innerSender !== null && $innerSender !== $this->gateway->senderId())) {
                Log::warning('Payment callback: inner payload does not match envelope', ['transaction' => $tx->id]);

                return new CallbackResult(CallbackResult::IGNORED, $tx, 'payload_mismatch');
            }

            $authorization = $this->pick($payload, 'AuthorizationCode');
            $responseCode = $this->pick($payload, 'ResponseCode') ?? $this->scalar($input['ResponseCode'] ?? null);
            $confirmed = $this->pick($payload, 'IsConfirmed') ?? $this->scalar($input['IsConfirmed'] ?? null);
            $gatewayAmount = $this->pick($payload, 'PaymentRequestTotalAmount') ?? $this->pick($payload, 'CustomerAuthorizationAmount');
            $fees = $this->pick($payload, 'CollectionFeesAmount');
            $gatewayTxNumber = $this->pick($payload, 'TransactionNumber');

            $tx->fill([
                'raw_response' => $payload,
                'payment_request_number' => $this->pick($payload, 'PaymentRequestNumber') ?? $tx->payment_request_number,
                'authorization_code' => $authorization ?? $tx->authorization_code,
                'gateway_amount' => $gatewayAmount !== null ? Money::format($gatewayAmount) : $tx->gateway_amount,
                'collection_fees' => $fees !== null ? Money::format($fees) : $tx->collection_fees,
            ]);
            if ($gatewayTxNumber !== null && ! Transaction::where('gateway_transaction_id', $gatewayTxNumber)->whereKeyNot($tx->getKey())->exists()) {
                $tx->gateway_transaction_id = $gatewayTxNumber;
            }

            $gatewaySaysPaid = $authorization !== null
                && ($responseCode === null || in_array($responseCode, ['000', '0', '00'], true))
                && ($confirmed === null || ! in_array(strtolower($confirmed), ['false', '0', 'no'], true));

            if (! $gatewaySaysPaid) {
                return $this->fail($tx, 'gateway_declined' . ($responseCode ? ':' . $responseCode : ''));
            }

            if (! $this->amountMatches($tx, $gatewayAmount, $fees)) {
                Log::critical('Payment callback: AMOUNT MISMATCH — manual review required', [
                    'transaction' => $tx->id, 'expected' => (string) $tx->amount, 'gateway_amount' => $gatewayAmount, 'fees' => $fees,
                ]);

                return $this->fail($tx, 'amount_mismatch');
            }

            return $this->complete($tx);
        });

        if ($result->outcome === CallbackResult::COMPLETED) {
            Log::info('Payment completed', ['transaction' => $result->transaction?->id]);
        }

        return $result;
    }

    private function amountMatches(Transaction $tx, ?string $gatewayAmount, ?string $fees): bool
    {
        if ($gatewayAmount === null || ! is_numeric(str_replace(',', '', $gatewayAmount))) {
            return false;
        }

        $expected = Money::cents($tx->amount);
        $total = Money::cents($gatewayAmount);

        // The gateway total may or may not include its collection fees.
        return $total === $expected || ($fees !== null && $total - Money::cents($fees) === $expected);
    }

    private function complete(Transaction $tx): CallbackResult
    {
        /** @var Model&Payable|null $payable */
        $payable = $this->resolver->find($tx->transactionable_type, $tx->transactionable_id, lock: true);

        $metadata = (array) ($tx->metadata ?? []);
        if ($payable === null) {
            $metadata['orphaned'] = true;
            Log::critical('Payment completed for a missing payable — manual review required', ['transaction' => $tx->id]);
        } elseif (method_exists($payable, 'isPaid') && $payable->isPaid()) {
            // Paid twice (e.g. two gateway tabs): keep the record, flag for refund.
            $metadata['duplicate_payment'] = true;
            Log::critical('Duplicate payment received — refund review required', ['transaction' => $tx->id]);
        } else {
            $payable->markPaid($tx);
        }

        $tx->forceFill([
            'status' => Transaction::STATUS_COMPLETED,
            'completed_at' => now(),
            'failure_reason' => null,
            'metadata' => $metadata ?: null,
        ])->save();

        DB::afterCommit(fn () => event(new PaymentCompleted($tx->fresh())));

        return new CallbackResult(CallbackResult::COMPLETED, $tx);
    }

    private function fail(Transaction $tx, string $reason): CallbackResult
    {
        $tx->forceFill([
            'status' => Transaction::STATUS_FAILED,
            'failure_reason' => Str::limit($reason, 250, ''),
            'failed_at' => now(),
        ])->save();

        /** @var Model&Payable|null $payable */
        $payable = $this->resolver->find($tx->transactionable_type, $tx->transactionable_id, lock: true);
        if ($payable !== null && ! (method_exists($payable, 'isPaid') && $payable->isPaid())) {
            $payable->markPaymentFailed($tx);
        }

        return new CallbackResult(CallbackResult::FAILED, $tx, $reason);
    }

    /** Depth-first, case-insensitive lookup of a scalar value in the decrypted payload. */
    private function pick(array $payload, string $key): ?string
    {
        foreach ($payload as $k => $v) {
            if (is_string($k) && strcasecmp($k, $key) === 0) {
                $scalar = $this->scalar($v);
                if ($scalar !== null) {
                    return $scalar;
                }
            }
        }
        foreach ($payload as $v) {
            if (is_array($v) && ($found = $this->pick($v, $key)) !== null) {
                return $found;
            }
        }

        return null;
    }

    private function scalar(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            $value = trim((string) $value);

            return $value === '' ? null : $value;
        }

        return null; // empty XML elements decode to []
    }

    /**
     * In-flight orders created by the legacy EFinanceService have no
     * Transaction row. Rebuild one from storage/app/efinance/legacy-pgw.
     */
    private function importLegacyOrder(string $srn): ?Transaction
    {
        $orderId = $this->legacy->orderId($srn);
        $secret = $this->legacy->secret($srn);
        if ($orderId === null || $secret === null) {
            return null;
        }

        // Legacy GIS orders used UUIDs, generic service orders integer ids.
        $payable = Str::isUuid($orderId) ? GisSubmission::find($orderId)
            : (ctype_digit($orderId) ? ServiceSubmission::find((int) $orderId) : null);

        if ($payable === null) {
            return null;
        }

        Log::info('Payment callback: importing legacy pgw order', ['srn' => $srn, 'type' => $payable->getMorphClass(), 'id' => $orderId]);

        return Transaction::create([
            'user_id' => $payable->payableOwner()?->getKey(),
            'transactionable_type' => $payable->getMorphClass(),
            'transactionable_id' => (string) $payable->getKey(),
            'amount' => Money::format($payable->payableAmount()),
            'description' => Str::limit($payable->payableDescription(), 250, ''),
            'status' => Transaction::STATUS_PENDING,
            'payment_gateway' => 'efinance',
            'sender_request_number' => $srn,
            'aes_secret' => [$secret],
            'metadata' => ['legacy_pgw' => true],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Housekeeping
    // ─────────────────────────────────────────────────────────────────────

    /** Marks abandoned pending attempts as expired. Returns how many rows changed. */
    public function expirePending(): int
    {
        return Transaction::query()
            ->where('status', Transaction::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => Transaction::STATUS_EXPIRED,
                'failure_reason' => 'expired',
                'failed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
