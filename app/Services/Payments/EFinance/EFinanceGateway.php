<?php

namespace App\Services\Payments\EFinance;

use Modules\Payments\Models\Transaction;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\GatewayRedirect;
use App\Services\Payments\Money;
use App\Services\Payments\PaymentException;
use App\Services\Payments\EFinance\Messages\PaymentMechanism;
use App\Services\Payments\EFinance\Messages\PaymentRequestInitiationReq;
use App\Services\Payments\EFinance\Messages\Sender;
use App\Services\Payments\EFinance\Messages\SettlementAmount;

/**
 * e-Finance "Internet Payment" web-transfer gateway (Egyptian government
 * e-payment). Replaces the legacy App\Services\EFinanceService.
 *
 * Unlike the legacy service nothing is written to public/pgw: the AES key
 * material is returned to PaymentService which stores it (encrypted) on the
 * Transaction row.
 */
class EFinanceGateway implements PaymentGateway
{
    public function __construct(
        private readonly EFinanceCrypto $crypto = new EFinanceCrypto(),
        private ?EFinanceConfig $config = null,
    ) {
    }

    private function config(): EFinanceConfig
    {
        return $this->config ??= EFinanceConfig::fromConfig();
    }

    public function name(): string
    {
        return 'efinance';
    }

    public function senderId(): string
    {
        return (string) $this->config()->get('sender_id', '');
    }

    public function initiate(Transaction $transaction, array $context): GatewayRedirect
    {
        $config = $this->config();
        $config->assertComplete();

        $certificate = (string) $config->get('certificate_path');

        $request = new PaymentRequestInitiationReq(
            sender: new Sender(
                id: (string) $config->get('sender_id'),
                name: (string) $config->get('sender_name'),
                randomValue: $this->crypto->randomHex(32),
                encryptedPassword: $this->crypto->encryptByCertificate((string) $config->get('password'), $certificate),
            ),
            settlementAmounts: [
                new SettlementAmount((string) $config->get('settlement_code'), Money::format($transaction->amount)),
            ],
            senderRequestNumber: (string) $transaction->sender_request_number,
            senderInvoiceNumber: (string) $transaction->transactionable_id,
            serviceCode: (string) $config->get('service_code'),
            currency: (string) $config->get('currency', '818'),
            ip: $context['server_ip'] ?? $config->serverIp(),
            paymentMechanism: new PaymentMechanism(),
            expiryDate: ($transaction->expires_at ?? now()->addDay())->format('Y-m-d'),
            paymentConfirmationUrl: $context['confirmation_url'],
            paymentConfirmationRedirectUrl: $context['redirect_url'],
        );

        $aes = $this->crypto->aesEncrypt($request->toXml());

        // Values are urlencoded exactly like the vendor sample (the gateway
        // expects them that way inside the form POST).
        return new GatewayRedirect(
            url: (string) $config->get('url'),
            fields: [
                'SenderID' => urlencode($request->sender->id),
                'RandomSecret' => urlencode($this->crypto->encryptByCertificate($aes['secret'], $certificate)),
                'RequestObject' => urlencode($aes['encrypted']),
                'HasedRequestObject' => urlencode($this->crypto->hashSha256Hex($aes['encrypted'])),
            ],
            secret: $aes['secret'],
        );
    }

    public function decryptCallback(string $requestObject, array $secrets): array
    {
        $candidates = array_values(array_unique(array_filter([
            $requestObject,
            str_replace(' ', '+', $requestObject),
            rawurldecode($requestObject),
            urldecode($requestObject),
        ])));

        // Most recent secret first.
        foreach (array_reverse(array_filter($secrets, 'is_string')) as $secret) {
            foreach ($candidates as $candidate) {
                $xml = $this->crypto->aesDecrypt($candidate, $secret);
                if ($xml === null) {
                    continue;
                }

                $parsed = $this->parseXml($xml);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        throw new PaymentException('تعذر التحقق من رد بوابة الدفع.', ['reason' => 'decrypt_failed', 'secrets_tried' => count($secrets)]);
    }

    /** @return array<string,mixed>|null */
    private function parseXml(string $xml): ?array
    {
        // The gateway answers with a UTF-16 declaration over UTF-8 bytes.
        $xml = preg_replace('/(<\?xml[^?]*?)utf-16/i', '$1utf-8', $xml) ?? $xml;

        $previous = libxml_use_internal_errors(true);
        try {
            $node = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($node === false) {
            return null;
        }

        $array = json_decode((string) json_encode($node), true);

        return is_array($array) ? $array : null;
    }
}
