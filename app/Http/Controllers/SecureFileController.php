<?php

// Moved to \Modules\Identity\Http\Controllers\SecureFileController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\SecureFileController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(SecureFileController::class, false)) {
    class_alias(\Modules\Identity\Http\Controllers\SecureFileController::class, SecureFileController::class);
}
