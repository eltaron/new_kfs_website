<?php

namespace App\Services\Payments\Fake;

use Modules\Payments\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\GatewayRedirect;
use App\Services\Payments\PaymentException;

/**
 * Test double for the e-Finance gateway (EFINANCE_DRIVER=fake; default in
 * the testing environment). No crypto, no certificate:
 *
 *  - initiate() returns plain fields
 *  - a callback's RequestObject is base64(json) of the decrypted payload,
 *    see self::encodeCallback()
 *
 * Refuses to run in production.
 */
class FakeGateway implements PaymentGateway
{
    public const SECRET = 'fake-secret';

    public function __construct()
    {
        if (app()->environment('production')) {
            throw new \LogicException('The fake payment gateway must never be used in production.');
        }
    }

    public function name(): string
    {
        return 'fake';
    }

    public function senderId(): string
    {
        return (string) (config('services.efinance.sender_id') ?: 'FAKE-SENDER');
    }

    public function initiate(Transaction $transaction, array $context): GatewayRedirect
    {
        return new GatewayRedirect(
            url: (string) (config('services.efinance.url') ?: url('/fake-efinance-gateway')),
            fields: [
                'SenderID' => $this->senderId(),
                'SenderRequestNumber' => (string) $transaction->sender_request_number,
                'Amount' => (string) $transaction->amount,
                'RedirectUrl' => $context['redirect_url'],
            ],
            secret: self::SECRET,
        );
    }

    public function decryptCallback(string $requestObject, array $secrets): array
    {
        if (! in_array(self::SECRET, $secrets, true)) {
            throw new PaymentException('تعذر التحقق من رد بوابة الدفع.', ['reason' => 'fake_secret_missing']);
        }

        $decoded = json_decode((string) base64_decode($requestObject, true), true);
        if (! is_array($decoded)) {
            throw new PaymentException('تعذر التحقق من رد بوابة الدفع.', ['reason' => 'fake_payload_invalid']);
        }

        return $decoded;
    }

    /** Helper for tests / local simulation. */
    public static function encodeCallback(array $payload): string
    {
        return base64_encode((string) json_encode($payload));
    }
}
