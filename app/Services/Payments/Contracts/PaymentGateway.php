<?php

namespace App\Services\Payments\Contracts;

use Modules\Payments\Models\Transaction;
use App\Services\Payments\GatewayRedirect;

interface PaymentGateway
{
    /** Short machine name stored in transactions.payment_gateway. */
    public function name(): string;

    /** The SenderID we expect the gateway to echo back on callbacks. */
    public function senderId(): string;

    /**
     * Build the browser hand-off (auto-submitted form) for a pending transaction.
     *
     * @param  array{confirmation_url:string, redirect_url:string, description:string, server_ip:string}  $context
     *
     * @throws \App\Services\Payments\PaymentException
     */
    public function initiate(Transaction $transaction, array $context): GatewayRedirect;

    /**
     * Decrypt the RequestObject of a silent (server-to-server) callback.
     *
     * @param  array<int,string>  $secrets  candidate key material, most recent last
     * @return array<string,mixed>
     *
     * @throws \App\Services\Payments\PaymentException when it cannot be decrypted with any secret
     */
    public function decryptCallback(string $requestObject, array $secrets): array;
}
