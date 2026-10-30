<?php

namespace Modules\SiteValidity\Services;

use RuntimeException;

/** انتقال غير مسموح أو بيانات غير صالحة — الرسالة عربية وتُعرض للمستخدم مباشرة. */
class WorkflowException extends RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(string $message, public array $errors = [])
    {
        parent::__construct($message);
    }
}
