<?php

namespace App\Services;

use App\Services\Payments\EFinance\EFinanceGateway;

/**
 * @deprecated The 1,800-line vendor port (15 classes in one file, public/pgw
 * key files, $_SESSION, utf8_encode) was split into App\Services\Payments:
 *
 *  - Payments\PaymentService            initiate / handleCallback / expirePending
 *  - Payments\EFinance\EFinanceGateway  protocol (request XML, RSA, AES, hash)
 *  - Payments\EFinance\EFinanceCrypto   crypto primitives
 *  - Payments\EFinance\Messages\*       request DTOs
 *  - Payments\EFinance\LegacyPgwReader  read-only fallback for legacy in-flight orders
 *
 * Charge money through PaymentService::initiate(Payable) or
 * route('payments.pay', ['type' => ..., 'id' => ...]) only. This alias is kept
 * so stale type-hints keep resolving; it will be removed.
 */
class EFinanceService extends EFinanceGateway
{
}
