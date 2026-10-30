<?php

namespace App\Services\Payments\EFinance\Messages;

final class Sender
{
    public function __construct(
        public string $id,
        public string $name,
        public string $randomValue,
        public string $encryptedPassword,
    ) {
    }
}
