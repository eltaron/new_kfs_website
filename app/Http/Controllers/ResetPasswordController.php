<?php

// Moved to \Modules\Identity\Http\Controllers\ResetPasswordController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ResetPasswordController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ResetPasswordController::class, false)) {
    class_alias(\Modules\Identity\Http\Controllers\ResetPasswordController::class, ResetPasswordController::class);
}
