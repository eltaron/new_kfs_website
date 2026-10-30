<?php

// Moved to \Modules\Payments\Http\Controllers\ReceiptController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ReceiptController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ReceiptController::class, false)) {
    class_alias(\Modules\Payments\Http\Controllers\ReceiptController::class, ReceiptController::class);
}
