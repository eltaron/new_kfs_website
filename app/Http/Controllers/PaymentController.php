<?php

// Moved to \Modules\Payments\Http\Controllers\PaymentController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\PaymentController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(PaymentController::class, false)) {
    class_alias(\Modules\Payments\Http\Controllers\PaymentController::class, PaymentController::class);
}
