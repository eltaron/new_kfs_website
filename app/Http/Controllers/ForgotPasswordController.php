<?php

// Moved to \Modules\Identity\Http\Controllers\ForgotPasswordController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ForgotPasswordController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ForgotPasswordController::class, false)) {
    class_alias(\Modules\Identity\Http\Controllers\ForgotPasswordController::class, ForgotPasswordController::class);
}
