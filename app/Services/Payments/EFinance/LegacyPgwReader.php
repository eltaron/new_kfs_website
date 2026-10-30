<?php

namespace App\Services\Payments\EFinance;

/**
 * Read-only fallback for payments initiated by the legacy EFinanceService,
 * which wrote (publicly!) to public/pgw/{orders,logs}/{SRN}.log. Those files
 * were moved to storage/app/efinance/legacy-pgw/. Once no legacy order can
 * still be paid (gateway expiry is 24h) this class and that folder can go.
 */
class LegacyPgwReader
{
    public function __construct(private ?string $basePath = null)
    {
        $this->basePath ??= storage_path('app/efinance/legacy-pgw');
    }

    public static function isValidSenderRequestNumber(string $srn): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{1,64}$/', $srn);
    }

    /** The legacy "SenderInvoiceNumber" = ServiceSubmission id (int) or GisSubmission id (uuid). */
    public function orderId(string $srn): ?string
    {
        $value = $this->read('orders', $srn);

        return $value === null ? null : trim($value);
    }

    /** base64(key||iv) persisted by the legacy AESEncrypt(). */
    public function secret(string $srn): ?string
    {
        $value = $this->read('logs', $srn);
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) && is_string($decoded['randomSecret'] ?? null) ? $decoded['randomSecret'] : null;
    }

    private function read(string $folder, string $srn): ?string
    {
        if (! self::isValidSenderRequestNumber($srn)) {
            return null;
        }

        $path = $this->basePath . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $srn . '.log';
        if (! is_file($path)) {
            return null;
        }

        $content = @file_get_contents($path);

        return $content === false || trim($content) === '' ? null : $content;
    }
}
