<?php

namespace Modules\Syndicate\Support;

use RuntimeException;

/** خطأ في قواعد العمل (آلة الحالات). يحمل رمزاً ثابتاً وحالة HTTP مناسبة للـ API. */
class SyndicateException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'invalid_state',
        public readonly int $status = 409,
    ) {
        parent::__construct($message);
    }

    public static function invalidState(string $message): self
    {
        return new self($message, 'invalid_state', 409);
    }

    public static function conflict(string $message, string $code = 'conflict'): self
    {
        return new self($message, $code, 409);
    }

    public static function invalid(string $message, string $code = 'invalid_request'): self
    {
        return new self($message, $code, 422);
    }
}
