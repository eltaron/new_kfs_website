<?php

// Moved to \Modules\Identity\Http\Controllers\TwoFactorController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\TwoFactorController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(TwoFactorController::class, false)) {
    class_alias(\Modules\Identity\Http\Controllers\TwoFactorController::class, TwoFactorController::class);
}
