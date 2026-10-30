<?php

namespace Modules\Marketplace\Services;

class InsufficientStockException extends \RuntimeException
{
    public function __construct(public string $productName)
    {
        parent::__construct("الكمية المتاحة من «{$productName}» لا تكفي لإتمام الطلب.");
    }
}
