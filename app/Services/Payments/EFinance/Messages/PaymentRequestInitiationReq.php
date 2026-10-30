<?php

namespace App\Services\Payments\EFinance\Messages;

/**
 * The clear-text PaymentRequestInitiationReq document that gets AES-encrypted
 * and posted (through the citizen's browser) to the e-Finance gateway.
 */
final class PaymentRequestInitiationReq
{
    /**
     * @param  array<int,SettlementAmount>  $settlementAmounts
     */
    public function __construct(
        public Sender $sender,
        public array $settlementAmounts,
        public string $senderRequestNumber,
        public string $senderInvoiceNumber,
        public string $serviceCode,
        public string $currency,
        public string $ip,
        public PaymentMechanism $paymentMechanism,
        public string $expiryDate,
        public string $paymentConfirmationUrl,
        public string $paymentConfirmationRedirectUrl,
    ) {
    }

    /** XML exactly in the shape the vendor sample produced (values are now XML-escaped). */
    public function toXml(): string
    {
        $e = static fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $settlements = '';
        foreach ($this->settlementAmounts as $s) {
            $settlements .= '<SettlementAmounts>'
                . '<SettlementAccountCode>' . $e($s->settlementAccountCode) . '</SettlementAccountCode>'
                . ($s->description === '' ? '<SettlementAmountsDescription />' : '<SettlementAmountsDescription>' . $e($s->description) . '</SettlementAmountsDescription>')
                . '<Amount>' . $e($s->amount) . '</Amount>'
                . '</SettlementAmounts>';
        }

        return '<?xml version="1.0" encoding="utf-16"?>'
            . '<PaymentRequestInitiationReq xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<Sender>'
            . '<Id>' . $e($this->sender->id) . '</Id>'
            . '<Name>' . $e($this->sender->name) . '</Name>'
            . '<RandomValue>' . $e($this->sender->randomValue) . '</RandomValue>'
            . '<Password>' . $e($this->sender->encryptedPassword) . '</Password>'
            . '</Sender>'
            . '<SettlementAmounts>' . $settlements . '</SettlementAmounts>'
            . '<SenderRequestNumber>' . $e($this->senderRequestNumber) . '</SenderRequestNumber>'
            . '<SenderInvoiceNumber>' . $e($this->senderInvoiceNumber) . '</SenderInvoiceNumber>'
            . '<ServiceCode>' . $e($this->serviceCode) . '</ServiceCode>'
            . '<RequestInitiationDescription />'
            . '<Currency>' . $e($this->currency) . '</Currency>'
            . '<IP>' . $e($this->ip) . '</IP>'
            . '<PaymentMechanism>'
            . '<Type>' . $e($this->paymentMechanism->type) . '</Type>'
            . '<MechanismType>' . $e($this->paymentMechanism->mechanismType) . '</MechanismType>'
            . ($this->paymentMechanism->isChannel() ? '<Channel><MobileNumber /><Email /></Channel>' : '<Channel />')
            . '</PaymentMechanism>'
            . '<ExpiryDate>' . $e($this->expiryDate) . '</ExpiryDate>'
            . '<PaymentConfirmationUrl>' . $e($this->paymentConfirmationUrl) . '</PaymentConfirmationUrl>'
            . '<PaymentConfirmationRedirectUrl>' . $e($this->paymentConfirmationRedirectUrl) . '</PaymentConfirmationRedirectUrl>'
            . '</PaymentRequestInitiationReq>';
    }
}
