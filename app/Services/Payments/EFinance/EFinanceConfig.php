<?php

namespace App\Services\Payments\EFinance;

use App\Services\Payments\PaymentException;

/**
 * Validated view of config('services.efinance'). No secrets have defaults:
 * a missing key fails loudly instead of silently using test credentials.
 */
final class EFinanceConfig
{
    private const REQUIRED = ['sender_id', 'sender_name', 'password', 'service_code', 'settlement_code', 'url', 'certificate_path'];

    public function __construct(private readonly array $config)
    {
    }

    public static function fromConfig(): self
    {
        return new self((array) config('services.efinance', []));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /** @throws PaymentException */
    public function assertComplete(): void
    {
        $missing = array_values(array_filter(self::REQUIRED, fn ($k) => blank($this->config[$k] ?? null)));

        if ($missing !== []) {
            throw new PaymentException('بوابة الدفع الإلكتروني غير مهيأة حالياً، يرجى المحاولة لاحقاً.', ['missing_config' => $missing]);
        }

        if (! is_readable((string) $this->config['certificate_path'])) {
            throw new PaymentException('بوابة الدفع الإلكتروني غير مهيأة حالياً، يرجى المحاولة لاحقاً.', ['certificate_unreadable' => $this->config['certificate_path']]);
        }
    }

    public function serverIp(): string
    {
        $ip = $this->config['server_ip'] ?? null;
        if (filled($ip)) {
            return (string) $ip;
        }

        $ip = $_SERVER['SERVER_ADDR'] ?? @gethostbyname((string) gethostname());

        return in_array($ip, ['127.0.0.1', '::1', '', false, null], true) ? '::1' : (string) $ip;
    }
}
